<?php
/***************************************************************************\
    This file is part of RoomAllocation.

    RoomAllocation is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    RoomAllocation is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with RoomAllocation.  If not, see <http://www.gnu.org/licenses/>.
\***************************************************************************/
?>
<?php
  function create_floorPlan( $college, $Map, $classes = array() ){
    $people   = array();  // maps: eid                      -> personal info
    $rooms    = array();  // maps: room_number              -> array( group_id )
    $groups   = array();  // maps: group_id                 -> array( eid )
    $choice   = array();  // maps: [group_id][room_number]  -> choice_number
    $points   = array();  // maps: group_id                 -> points
    $total    = array();  // maps: group_id                 -> $points[$k]['total']

    $people_in_group = array(); // maps: group_id -> array( people[group[$k]] )

    /** select the room choices */
    $q = "SELECT * FROM ".TABLE_APARTMENT_CHOICES." 
            WHERE college='$college'
            ORDER BY college,number,group_id,choice";
    $rooms_tmp  = sqlToArray( mysql_query($q) );
    foreach( $rooms_tmp as $room ){
      $rooms[$room['number']][] = $room['group_id'];
      if( !isset($choice[$room['group_id']]) ){
        $choice[$room['group_id']] = array();
      }
      $choice[$room['group_id']][$room['number']] = (int)$room['choice'];
    }
    
    /** properly sort the choices map */
    ksort( $choice );
    foreach( $choice as $group_id => $room_choices ){
      uksort($choice[$group_id], function($a,$b) use ($choice,$group_id){
        if( $choice[$group_id][$a] == $choice[$group_id][$b] ){
          return strcmp( $a, $b );
        } else {
          return $choice[$group_id][$a] < $choice[$group_id][$b] ? -1 : 1;
        }
      });
    }
    
    /** select and make the groups */
    $q = "SELECT i.group_id, p.* FROM ".TABLE_PEOPLE." p, ".TABLE_IN_GROUP." i
                WHERE i.eid=p.eid ORDER BY i.group_id";
    $people_tmp = sqlToArray( mysql_query($q) );
    foreach( $people_tmp as $v ){
      $people[$v['eid']] = $v;
      $groups[$v['group_id']][] = $v['eid'];
      $people_in_group[$v['group_id']][] = $people[$v['eid']];
    }
    
    /** conpute the total number of points for each group */
    foreach( $groups as $group_id => $group ){
      $points[$group_id]  = get_points( $people_in_group[$group_id] );
      $total[$group_id]   = $points[$group_id]['total'];
    }

    /** sort rooms by total number of points and by choice */
    foreach( $rooms as $number => $value ){
      usort( $rooms[$number], function($a, $b) use ($total,$choice,$number){ 
        if( $total[$a] == $total[$b] ){
          if( $choice[$a][$number] == $choice[$b][$number] )
            return 0;
          else 
            return $choice[$a][$number] < $choice[$b][$number] ? -1 : 1;
        } else {
          return $total[$a] > $total[$b] ? -1 : 1; 
        }
      });
    }
    
    /** compute final result */
    list( $allocations, $random ) = allocate_rooms( $rooms, $choice, $total );
    $new_allocations = array();
    if( C('allocation.allocateRandom') ){
      $new_allocations = allocate_random_rooms( $college, $allocations, $random, $groups, $Map );
      $allocations = array_merge( $allocations, $new_allocations );
    } else {
      //$new_allocations = $random;
    }
    
    /** determine ambiguous */
    $arguments  = array();
    foreach( $rooms as $number => $gids ){
      $max = -1;
      foreach( $gids as $group_id ){
        $max = $total[$group_id] > $max ? $total[$group_id] : $max;
      }
      $max_groups = array();
      foreach( $total as $k => $v ){
        if( in_array( $k, $gids ) && $v == $max ){
          $max_groups[] = $k;
        }
      }
      
      $classes[$number]   = array_map(function($v){return "group-$v";}, $gids);
      $classes[$number][] = count($max_groups) > 1 ? 'ambiguous' : 'chosen';
      $classes[$number]   = implode(' ', $classes[$number]);
      
      $arguments[$number]['country'] = array(
        'fname'   => '', 
        'lname'   => '', 
        'country' => ''
      );
    }
    
    /** RETURN HTML **/
    $h = '';
    
    /** Print groups */
    foreach( $groups as $group_id => $eids ){
      $faces = array();
      $emails = array();
      foreach( $eids as $eid ){
        $faces[]  = getFaceHTML( $people[$eid] );
        $emails[] = $people[$eid]['email'];
      }
      $h .= '
        <div class="group" id="group-'.$group_id.'" style="display:none">
          <h4>
            Total: <b>'.$total[$group_id].'</b> 
            Country: '.$points[$group_id]['country'].'
            World:'.$points[$group_id]['world'].'
          </h4>
          '.implode("\n", $faces).'
          <div class="actions" style="padding:3px;border-top:1px solid #999;background:#fff;text-align:center;display:none;">
            <div class="gh-button-group">
              <a href="javascript:void(0)" class="gh-button pill primary icon user">View chosen rooms</a>
              <a href="mailto:'.implode(',', $emails).'" class="gh-button pill icon mail">send email</a>
            </div>
          </div>
        </div>
      ';
    }

    $table        = allocation_table( $rooms, $groups, $people, $points, $total );
    $final_table  = allocation_table( $allocations, $groups, $people, $points, $total );

    $h .= '
      <table cellspacing="0" cellpadding="0" id="allocation-table-'.$college.'" class="allocation-table display-floorPlan view" style="display:none;">
        '.$table.'
      </table>
    ';
    
    $unallocated = "";
    if( !C('allocation.allocateRandom') ){
      $unallocated = allocation_table($random,$groups,$people,$points,$total);
    }
    
    $h .= '
      <div class="display-final view" style="display:none;text-align:center;" id="final-allocation-table-'.$college.'">
        <table cellspacing="0" cellpadding="0" class="allocation-table" style="float:left">
          '.$final_table.'
        </table>
        <div>
          <h3>Unallocated this round</h3>
          <table class="allocation-table" style="margin:5px auto;">
            '.$unallocated.'
          </table>
        </div>
      </div>
      <div class="clearBoth"></div>
    ';
    
    $h .= '<div class="college-floorPlan view" id="floorPlan-'.$college.'">';
    if( $college != 'Nordmetall' ){
      $h .=  renderMap( $Map, $classes, $arguments );
    } else {
      $h .= '<div class="view college-floorPlan">
              No visual floor-plan available for Nordmetall, sorry
            </div>';
    }
    $h .= '</div>';
    
    return array(
      'html'            => $h,
      'allocations'     => $allocations,
      'random'          => $random,
      'new_allocations' => $new_allocations,
      'people'          => $people,
      'rooms'           => $rooms,
      'groups'          => $groups,
      'choice'          => $choice,
      'points'          => $points,
      'total'           => $total
    );
  }
  
  function allocation_table( $rooms, $groups, $people, $points, $total ){
    $table = array();
    $i = 0;
    foreach( $rooms as $number => $gr ){
      $h = array();
      $h[] = '<tr class="'.($i%2==0? 'even' : 'odd').'">';
      $h[] = '<td style="width:50px;text-align:center;font-weight:bold;">'.$number.'</td>';
      $h[] = '<td>';
      if( !is_array( $gr ) ) 
        $gr = array( $gr );
      foreach( $gr as $g_id ){
        $h[] = '<span class="small-group">';
        $h[] = '<span class="total">'.$total[$g_id].'</span>';
        foreach( $groups[$g_id] as $p_id ){
          $person = $people[$p_id];
          $d              = 3-((2000+(int)$person['year'])-(int)date("Y"));
          $year_of_study  = $d."<sup>".($d==1?'st':($d==2?'nd':($d==3?'rd':'th')))."</sup>";
          $h[] = '<span class="person">
                    <img height="18" class="county" src="'.flagURL( $person['country'] ).'" />
                    <span class="year">'.$year_of_study.'</span>
                    <span class="name">'.$person['fname'].', '.$person['lname'].'</span>
                  </span>';
        }
        $h[] = '</span>';
      }
      $h[] = '</td>';
      $h[] = '</tr>';
      $table[$i] = implode("\n", $h);
      ++$i;
    }
    return implode("\n", $table);
  }
  
  /**
   * @brief Assign the groups that did not get any of their choices to a random apartment
   * @warning This function is based on the fact that ALL groups in a round have the same size
   * @param {string} $college
   * @param {array} $allocations
   * @param {array} $groups
   * @param {array} $random
   * @param {array} $Map
   * @returns {array} the new assigned rooms to the $random groups
   */
  function allocate_random_rooms( $college, array $allocations, array $random, $groups, array $Map ){
    $rooms = map_to_list( $Map );
    // delete the taken rooms from the list
    foreach( $allocations as $room_number => $group_number ){
      unset( $rooms[$room_number] );
    }
    $new_allocations = array();
    $break_limit = 10000;
    foreach( $random as $group_number ){
      $size = count( $groups[$group_number] );
      $counter = 0;
      $fn_apartment = $college != 'Nordmetall' ? 'get_apartment' : 'get_apartment_NM';
      while( ($apartment = $fn_apartment(array_rand($rooms))) && count($apartment) != $size ){
        if( ++$counter > $break_limit ) {
          echo '<div style="color:red">Loop limit exceeded in '.__FILE__.':'.__LINE__.'</div>';
          break;
        }
      }
      foreach( $apartment as $room_number ){
        $new_allocations[$room_number] = $group_number;
        unset( $rooms[$room_number] );
      }
    }
    return $new_allocations;
  }
  
  /**
   * @brief Takes all the rooms from a map and puts them into a list (bitset)
   * @param {array} $Map
   * @returns {array}
   */
  function map_to_list( array $Map ){
    $list = array();
    foreach( $Map as $block_name => $block ){
      foreach( $block as $floor ){
        foreach( $floor as $side ){
          foreach( $side as $room ){
            if( $room[0] && $room[0] != '' ){
              $list["$block_name-${room[0]}"] = true;
            }
          }
        }
      }
    }
    return $list;
  }
  
  /**
   * @brief Returns all room numbers in the apartment with the given room
   * @param {string} $number  The given room number
   * @returns {array}
   */
  function get_apartment( $number ){
    list( $block, $number ) = explode('-', $number);
    $number = (int)$number;
    if( $number <= 103 ){
      return array( "$block-101", "$block-102", "$block-103" );
    } else if( $number % 2 == 0 ){
      return array( "$block-$number", "$block-".($number+1) );
    } else {
      return array( "$block-$number", "$block-".($number-1) );
    }
  }
  
  /**
   * @brief Returns all room numbers in the apartment with the given room
   * @param {string} $number  The given room number
   * @returns {array}
   */
  function get_apartment_NM( $number ){
    list( $block, $number ) = explode('-', $number);
    $number = (int)$number;
    if( $number % 2 == 0 ){
      return array( "$block-$number", "$block-".($number+1) );
    } else {
      return array( "$block-$number", "$block-".($number-1) );
    }
  }
  
  /**
   * @brief
   *
   */
  function allocate_rooms( array $rooms, array $choice, array $total ){
    $allocated    = array();   // maps: room_number -> group_id
    $unallocated  = array();
    
    $total_points_compare = function($a,$b) use ($total){
      return $total[$a] == $total[$b] ? 0 : ( $total[$a] < $total[$b] ? -1 : 1 );
    };
    
    //sort the group_ids in rooms by the total number of points
    foreach( $rooms as $number => $eids ){
      uasort($rooms[$number], $total_points_compare);
    }
    
    //sort the groups' choices by the total number of points
    uksort($choice, $total_points_compare);
    
    // pick one group at a time
    foreach( $choice as $group_id => $room_choices ){
      $curr_choice  = -1;
      $curr_points  = $total[$group_id];
      $got_room     = false;
      // iterate through all their room choices in order
      foreach( $room_choices as $room_number => $choice_number ){
        // only take apartments, not rooms (skip rooms with same option)
        if( $curr_choice == $choice_number ) continue;
        $curr_choice = $choice_number;
        $can_take = true;
        
        // take all groups applying for that room
        // try to see if the current group can take that room
        foreach( $rooms[$room_number] as $gid_key => $new_gid ){
          
          // only compare 2 different groups
          if( $new_gid == $group_id ) continue;
          
          // if the new group already has a room
          if( array_search( $new_gid, $allocated ) !== false ) continue;
          
          // continue if you have more points
          if( $curr_points > $total[$new_gid] ) continue;
          
          // test whether you can keep the room
          if(
            // lose if the room is already taken
            isset( $allocated[$room_number] )
            // lose room if you have less points
            || ($curr_points < $total[$new_gid])
            // lose if you have the same number of points AND:
            || ($curr_points == $total[$new_gid] && (
                  // the choice number is higher
                  $curr_choice > $choice[$new_gid][$room_number]
                  // if the choice numbers are equal, 50% chances to lose the room
                  || (($curr_choice == $choice[$new_gid][$room_number]) && !rand(0,1))
                  )
                )
          ){
            $can_take = false;
            break;
          }
        }
        if( $can_take ){
          $new_rooms = array_keys( $choice[$group_id], $curr_choice );
          foreach( $new_rooms as $new_number ){
            $allocated[$new_number] = $group_id;
            //echo "<div>$group_id >> $new_number</div>";
          }
          $got_room = true;
          break;
        }
      }
      if( !$got_room ){
        $unallocated[] = $group_id;
        //echo '<div style="color:red;">Group <b>'.$group_id.'</b> could not get a room. Try refreshing to re-allocate!</div>';
      }
    }

    ksort( $allocated );
    //v_export( $allocated );
    ksort( $unallocated );
    return array( $allocated, $unallocated );
  }
  
?>