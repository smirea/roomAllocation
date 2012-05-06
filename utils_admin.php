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
            ORDER BY number,group_id,choice";
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
    $q = "SELECT i.group_id, p.* FROM ".TABLE_PEOPLE." p, ".TABLE_IN_GROUP." i, ".TABLE_ALLOCATIONS." a
                WHERE a.college='$college' AND a.eid=i.eid AND i.eid=p.eid ORDER BY i.group_id";
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
    list( $allocations, $random, $allocation_log ) = new_allocate_rooms( $rooms, $choice, $total, $people, $groups, $college );
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
    
    foreach( $rooms as $number => $gids ){
      uasort( $rooms[$number], function( $a, $b )use($number, $total, $choice){
        return $total[$a] == $total[$b] ? ($choice[$a][$number] - $choice[$b][$number]) : ( $total[$a] - $total[$b] );
      });
    }
    $table        = allocation_table( $rooms, $groups, $people, $points, $total, $choice );
    $final_table  = allocation_table( $allocations, $groups, $people, $points, $total, $choice );

    $h .= '
      <table cellspacing="0" cellpadding="0" id="allocation-table-'.$college.'" class="allocation-table display-floorPlan view" style="display:none;">
        '.$table.'
      </table>
    ';
    
    $unallocated = "";
    if( !C('allocation.allocateRandom') ){
      $unallocated = allocation_table($random,$groups,$people,$points,$total);
    }
    
    $g_sorted = array_keys( $choice );
    ksort( $g_sorted );
    $log_prepend = array_map(function($v) use ($total,$people,$groups){
      return '<div>'.generate_small_group( $groups[$v], $people, $v, $total[$v] ).'</div>';
    }, $g_sorted);
    $log_prepend    = '<div class="legend">'.implode("\n",$log_prepend).'</div>';
    $allocation_log = $log_prepend . $allocation_log;
    $log_id       = 'allocation-log-'.$college;
    $h .= '
      <div class="display-final view" style="display:none;text-align:center;" id="final-allocation-table-'.$college.'">
        <div style="text-align:right;border-bottom:1px solid #ccc;padding:2px 0;">
          <a href="javascript:void(0)" style="font-weight:bold" onclick="$(\'#'.$log_id.'\').toggle()">Toggle '.$college.'\'s allocation log</a>
        </div>
        <div class="log" id="'.$log_id.'" style="display:none">
          '.$allocation_log.'
        </div>
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
      'total'           => $total,
      'log'             => $allocation_log
    );
  }
  
  function allocation_table( $rooms, $groups, $people, $points, $total, $choices = null ){
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
        if( $choices && isset($choices[$g_id][$number]) )
          $h[] = '<div>'.generate_small_group( $groups[$g_id], $people, $g_id, $total[$g_id], $choices[$g_id][$number] ).'</div>';
        else
          $h[] = '<div>'.generate_small_group( $groups[$g_id], $people, $g_id, $total[$g_id] ).'</div>';
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
      return array( "$block-".($number-1), "$block-$number" );
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
      return array( "$block-".($number-1), "$block-$number" );
    }
  }
  
  /**
   * @brief Allocates all the rooms to all the groups and returns a thorough result
   * @param {array} $rooms
   * @param {array} $choice
   * @param {array} $total
   * @param {array} $people
   * @param {array} $groups
   * @param {string} $college
   * @returns {array mixed}
   */
  function new_allocate_rooms( array $rooms, array $choice, array $total, array $people, array $groups, $college ){

    $fn_get_apartment = $college !== 'Nordmetall' ? 'get_apartment' : 'get_apartment_NM';
    $no_choices       = C('apartment.choices');
    
    // deflate apartments from 'rooms' into one room representative 
    //    (A-101, A-102, A-103) becomes A-101 - it is just for efficiency
    $all_rooms = $rooms;
    foreach( $all_rooms as $room_number => $gids ){
      $apartment = $fn_get_apartment( $room_number );
      for( $i=1; $i<count($apartment); ++$i ){
        unset($rooms[$apartment[$i]]);
      }
    }

    // create the new total points which includes choices in the points
    $room_points = array();
    foreach( $choice as $group_id => $tmp ){
      $room_points[$room_number] = array();
      foreach( $tmp as $room_number => $choice_number ){
        $room_points[$room_number][$group_id] = $total[$group_id] * 100 + ($no_choices - $choice_number);
      }
    }
    
    // sort people applying for rooms by the room_points
    foreach( $rooms as $room_number => $applicants ){
      usort( $rooms[$room_number], function($a,$b)use($room_points,$room_number){
        return $room_points[$room_number][$a] - $room_points[$room_number][$b];
      });
    }
    
    /** do the actual allocation **/
    $allocated    = array();
    $unallocated  = array_combine( array_keys( $choice ), array_fill(0,count($choice),true) );
    $log          = "";
    // do MAX_NUMBER_OF_CHOICES iterations
    for( $i=0; $i<$no_choices; ++$i ){
      $current_round_allocations = array();
      $log .= _log('<div style="background:lightblue">Taking all rooms with groups that have their choice <= <b>'.$i.'</b> </div>');
      //go through each room
      foreach( $rooms as $room_number => $applicants ){
        if( count($unallocated) == 0 ){
          break;
        }
        $log_status = 'Trying to allocate <b>'.$room_number.'</b>: ';
        if( isset( $allocated[ $room_number ] ) ){
          //$log .= _log( $log_status . ' already allocated to <b>'.$allocated[$room_number].'</b>' );
          continue;
        }
        if( count($rooms[$room_number]) == 0 ){
          //$log .= _log( $log_status . ' no applicants remaining or all applicants have been previously allocated' );
          continue;
        }
        // pick all people with highest score
        list($contestants, $max_points) = get_contestants( $i, $room_number, $room_points, $rooms, $choice );
        if( count($contestants) == 0 ){
          if( $i == $no_choices-1 )
            $log .= _log( $log_status . 'Nobody got this room' );
          else {
            //$log .= _log( 'No valid applicants available for this room at this iteration' );
          }
          continue;
        }
        $log .= _log( $log_status . 'contestants:'.implode(', ', array_map('generate_group_id',$rooms[$room_number])) );
        if( count($contestants) > 1 ){
          // random between all of them
          $winner = $contestants[ rand(0, count($contestants)-1) ];
          $log .= _log( 'Randomize between all valid applicants with 
                          <span class="small-group"><span class="total">'.$total[$contestants[0]].'p</span></span>: 
                          '.implode(',', array_map('generate_group_id',$contestants)), 1 );
        } else {
          $winner = $contestants[0];
          $log .= _log( 'Only one valid applicant for this room: '.generate_group_id($winner), 1 );
        }
        $allocated[ $room_number ] = $winner;
        delete_from_rooms( $winner, $rooms );
        unset( $unallocated[$winner] );

        $log .= _log( 'Allocated to '.generate_small_group($groups[$winner], $people, $winner, $total[$winner], $choice[$winner][$room_number]).'
                            with a chance of <b>'.round(100/count($contestants),2).'%</b>', 1 );
      }
      if( count($unallocated) == 0 ){
        $log .= _log_info('Finished allocating everyone!');
        break;
      }
    }
    
    // deflate allocated
    foreach( $allocated as $room_number => $g_id ){
      $apartment = $fn_get_apartment( $room_number );
      foreach( $apartment as $new_room ){
        $allocated[$new_room] = $g_id;
      }
    }
    $unallocated = array_keys( $unallocated );
    ksort( $allocated );
    sort( $unallocated );
//    v_export( $allocated, $unallocated );
    
    return array( $allocated, $unallocated, $log );
  }
  
  function _log( $msg, $level=0, $classes = '' ){ 
    return '<div class="msg '.$classes.'" style="margin-left:'.($level*10).'px;">'.$msg.'</div>';
  }
  function _log_info( $msg, $level=0 ){ return _log( $msg, $level, 'info' ); }
  function _log_warn( $msg, $level=0 ){ return _log( $msg, $level, 'warn' ); }
  function _log_err( $msg, $level=0 ){ return _log( $msg, $level, 'err' ); }
  
  function delete_from_rooms( $group_id, array &$rooms ){
    foreach( $rooms as $room_number => $gids ){
      foreach( $gids as $k => $g_id ){
        if( $g_id == $group_id )
          unset( $rooms[$room_number][$k] );
      }
    }
  }
  
  function get_contestants( $choice_number, $room_number, array $room_points, array $rooms, array $choice ){
    $result = array();
    $max = max( array_map(function($v)use($room_points,$room_number){ return $room_points[$room_number][$v]; }, $rooms[$room_number]) );
    foreach( $rooms[$room_number] as $g_id ){
      if( $choice[$g_id][$room_number] <= $choice_number && $room_points[$room_number][$g_id] == $max )
        $result[] = $g_id;
    }
    return array($result, $max);
  }
    

  
  /**
   * @brief Allocates all the rooms to all the groups and returns a thorough result
   * @param {array} $rooms
   * @param {array} $choice
   * @param {array} $total
   * @param {array} $people
   * @param {array} $groups
   * @param {string} $college
   * @returns {array mixed}
   */
  function allocate_rooms( array $rooms, array $choice, array $total, array $people, array $groups, $college ){
    $allocated    = array();    // maps: room_number          -> group_id
    $unallocated  = array();    // maps: group_id             -> bool
    $already_lost = $choice;    // maps: group_id,room_number -> bool
    
    // Catch all output
    ob_start();
    
    foreach( $already_lost as $group_id => $v ){
      foreach( $v as $room_number => $v2 ){
        $already_lost[$group_id][$room_number] = false;
      }
    }
    
    $fn_get_apartment = $college !== 'Nordmetall' ? 'get_apartment' : 'get_apartment_NM';
    
    $total_points_compare = function($a,$b) use ($total){
      return $total[$a] == $total[$b] ? 0 : ( $total[$a] < $total[$b] ? -1 : 1 );
    };
    
    //sort the group_ids in rooms by the total number of points
    foreach( $rooms as $number => $eids ){
      uasort($rooms[$number], $total_points_compare);
    }

    //sort the groups' choices by the total number of points
    uksort($choice, $total_points_compare);
    $choice = array_reverse( $choice, true );
    foreach( $choice as $group_id => $room_choices ){
      asort( $choice[$group_id] );
    }
    
    // pick one group at a time
    foreach( $choice as $group_id => $room_choices ){
      $curr_choice  = -1;
      $curr_points  = $total[$group_id];
      $got_room     = false;
      asort($room_choices);
      echo "<div style=\"background:lightblue;\"><b>".
              generate_small_group( $groups[$group_id], $people, $group_id, $total[$group_id] ).
              "</b>'s turn. (".implode(',',array_keys($room_choices)).").</div>";
      // iterate through all their room choices in order
      foreach( $room_choices as $room_number => $choice_number ){
        // only take apartments, not rooms (skip rooms with same option)
        if( $curr_choice == $choice_number ) continue;
        $curr_choice = $choice_number;
        $can_take = true;
        
        echo "<div style=\"font-weight:bold\">- Trying to get (".implode(',',$fn_get_apartment($room_number)).") as choice number $choice_number .</div>";
        echo "<div>--- Opponents: ".implode(',', array_map(generate_group_id, $rooms[$room_number]))."</div>";
        
        // the room is already taken
        if( isset( $allocated[$room_number] ) ){
          $can_take = false;
          echo "<div>==> Room already allocated to ".$allocated[$room_number]."</div>";
          continue;
        }
        
        // the room is already taken
        if( $already_lost[$group_id][$room_number] ){
          $can_take = false;
          echo "<div style=\"color:red;font-weight:bold;\">==> You already lost this room 
                  to random against ".generate_group_id($already_lost[$group_id][$room_number])."</div>";
          continue;
        }
        
        // take all groups applying for that room
        // try to see if the current group can take that room
        foreach( $rooms[$room_number] as $gid_key => $new_gid ){
          
          $html_new_gid = generate_group_id($new_gid);
          
          // only compare 2 different groups
          if( $new_gid == $group_id ) continue;
          
          echo "<div>--- Compare to $html_new_gid</div>";
          
          // skip test if the other group already has a room
          if( ($key = array_search( $new_gid, $allocated )) !== false ) {
            echo "<div>-----> $html_new_gid already have a room: <b>".implode(', ', $fn_get_apartment($key) )."</b> </div>";
            continue;
          }
          
          // skip test if the other group already attempted to get a room
          if( isset($unallocated[$new_gid]) ) {
            echo "<div>-----> $html_new_gid attempted to get a room before. Skipping! </div>";
            continue;
          }
          
          if( $curr_points > $total[$new_gid] ) {
            echo "<div>-----> More points than $html_new_gid </div>";
            continue;
          }
          
          // groups loses the room if any of the following occurs:
          
          // has less points
          if($curr_points < $total[$new_gid]){
            echo "<div style=\"color:red\">==> Less points than $html_new_gid( $curr_points < ".$total[$new_gid]." ) </div>";
            $can_take = false;
            break;
          } else if( $curr_points == $total[$new_gid] ){ // has the same number of points
            // and has worst choice
            if($curr_choice > $choice[$new_gid][$room_number]){
              echo "<div style=\"color:red\">==> Worse choice than $html_new_gid( $curr_choice > ". $choice[$new_gid][$room_number]." ) </div>";
              $can_take = false;
              break;
            } else if($curr_choice == $choice[$new_gid][$room_number]){
              // and has same choice, but is unlucky (50%)
              if( rand(0,1) === 1 ){
                echo "<div style=\"color:red;font-weight:bold;\">==> Lost to random</div>";
                $can_take = false;
                break;
              } else {
                echo "<div style=\"color:green;font-weight:bold;\">==>Won to random</div>";
                $already_lost[$new_gid][$room_number] = $group_id;
              }
            } else {
              echo "<div>-----> Better choice than $html_new_gid ( $curr_choice < ". $choice[$new_gid][$room_number]." ) </div>";
            }
          } else {
            echo "<div>-----> More points than than $html_new_gid</div>";
          }
          
        }
        if( $can_take ){
          $new_rooms = array_keys( $choice[$group_id], $curr_choice );
          foreach( $new_rooms as $new_number ){
            $allocated[$new_number] = $group_id;
          }
          $got_room = true;
          echo "<div style=\"color:blue; font-weight:bold\">==> Assigned to ".implode(',',$new_rooms)."!</div>";
          break;
        }
      }
      if( !$got_room ){
        $unallocated[$group_id] = true;
        echo "<div style=\"color:red; font-weight:bold;\">==> Unallocated! </div>";
        //echo '<div style="color:red;">Group <b>'.$group_id.'</b> could not get a room. Try refreshing to re-allocate!</div>';
      }
    }
    
    $unallocated = array_keys( $unallocated );
    ksort( $allocated );
    ksort( $unallocated );
    $log = ob_get_contents();
    ob_end_clean();
    return array( $allocated, $unallocated, $log );
  }
  
  /**
   *
   */
  function generate_group_id($group_id){
    return '<span group_id="'.$group_id.'" class="small-group small-group-id"><span class="group_id">['.$group_id.']</span></span>';
  };
  
  /**
   *
   */
  function generate_small_group( $group, $people, $group_id = null, $total = null, $choice = null ){
    $h    = array();
    $id   = $group_id !== null ? ' id="small-group-'.$group_id.'"' : '';
    $h[]  = '<span class="small-group" '.$id.'>';
    
    if( $group_id !== null )
      $h[] = '<span class="group_id">['.$group_id.']</span>';
    if( $total !== null )
      $h[] = '<span class="total">'.$total.'p</span>';
    if( $choice !== null )
      $h[] = '<span class="choice">'.$choice.'</span>';
    
    foreach( $group as $eid ){
      $person = $people[$eid];
      $d      = 3-((2000+(int)$person['year'])-(int)date("Y"));
      if( $person['status'] == 'foundation-year' )
        $year_of_study  = 'fy';
      else
        $year_of_study  = $d."<sup>".($d==1?'st':($d==2?'nd':($d==3?'rd':'th')))."</sup>";
      $h[] = '<span class="person">
                <img height="18" class="county" src="'.flagURL( $person['country'] ).'" />
                <span class="year">'.$year_of_study.'</span>
                <span class="name">'.$person['fname'].', '.$person['lname'].'</span>
              </span>';
    }
    $h[] = '</span>';
    return implode("\n",$h);
  }
  
?>