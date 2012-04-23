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
  require_once 'config.php';
  require_once 'utils.php';
  require_once 'floorPlan/utils.php';
  require_once 'floorPlan/Mercator.php';
  require_once 'floorPlan/Krupp.php';
  require_once 'floorPlan/College3.php';
  require_once 'floorPlan/Nordmetall.php';
?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    
    <link rel="stylesheet" type="text/css" href="css/html5cssReset.css" />
    <link rel="stylesheet" type="text/css" href="css/jquery-ui/jquery-ui.css" />
    <link rel="stylesheet" type="text/css" href="css/messages.css" />
    <link rel="stylesheet" type="text/css" href="css/gh-buttons.css" />
    <link rel="stylesheet" type="text/css" href="css/jquery.qtip.css" />
    <link rel="stylesheet" type="text/css" href="css/floorPlan.css" />
    <link rel="stylesheet" type="text/css" href="css/roomAllocation.css" />

    <script src="js/jquery.js"></script>
    <script src="js/jquery-ui.js"></script>
    <script src="js/jquery.qtip.js"></script>
    <script src="js/lib.js"></script>
    <script src="js/roomAllocation.js"></script>
  </head>
  
  <body>
    <div id="main">
      <?php require_once 'login.php'; ?>
      
      <div class="message-holder">
        <div id="message-info" class="info message"><div class="content"></div></div>
        <div id="message-error" class="error message"><div class="content"></div></div>
        <div id="message-warning" class="warning message"><div class="content"></div></div>
        <div id="message-success" class="success message"><div class="content"></div></div>
      </div>
      
      <div id="wrapper">
        <?php
          if( LOGGED_IN ){
            
            /** Set some info */
            $eid        = $_SESSION['info']['eid'];
            $group      = group_info( $eid );
            $roommates  = get_roommates( $eid, $group['group_id'] );
            $info       = $_SESSION['info'];
            $points     = get_points( array_merge( array($info), $roommates ) );
            
            $q_allocation = "SELECT * FROM ".TABLE_ALLOCATIONS." WHERE eid='$eid'";
            $allocation   = mysql_fetch_assoc( mysql_query( $q_allocation ) );
            define( 'HAS_ROOM', $allocation['room'] != null );
        ?>
        <div style="float:left;width:50%;" class="content">
          <div class="wrapper">
            <h3>Profile</h3>
            <?php
                echo getFaceHTML( $info );
            ?>
            
            <br />
            <h3>Add a new roommate</h3>
            <form id="searchBox" method="post">
              <input type="hidden" name="eid" id="roommate-eid" />
              <input type="text" id="search" placeholder="start typing your roommate's name..." />
              <input type="submit" id="addRoommate" value="Add" />
            </form>
            <br />
            
            <h3>Points</h3>
            <div id="total-points">
              <?php
                  /**
                  * @brief Check if all the countries in the database are assigned to a world region
                  *
                  $countries = "SELECT DISTINCT country FROM ".TABLE_PEOPLE;
                  $countries = sqlToArray( mysql_query( $countries ) );
                  $countries = array_map( function($v){ return $v['country']; }, $countries );
                  foreach( $countries as $v ){
                    if( !$WorldRegions_Inv[ $v ] ){
                      echo "<div style=\"color:red\">$v</div>";
                    }
                  }
                  */
                  echo print_score( array_merge( array($info), $roommates ), $points );
              ?>
            </div>
          </div>
        </div>
        
        <div style="float:right;width:50%;" class="content">
          <div class="wrapper">
            <h3>Current Roommates</h3>
            <div id="current-roommates">
              <?php
                $hidden = count( $roommates ) > 0 ? 'display:none' : '';
                echo '<div class="none" style="text-indent:10px;'.$hidden.'">none so far...</div>';
                foreach( $roommates as $person ){
                  echo getFaceHTML( $person );
                }
              ?>
            </div>
            
            <br />
            <h3>Requests received</h3>
            <div id="requests-received">
              <?php
                $q = "SELECT p.* FROM ".TABLE_PEOPLE." p, ".TABLE_REQUESTS." r 
                        WHERE r.eid_to='$eid' AND p.eid=r.eid_from";
                $res    = sqlToArray( mysql_query( $q ) );
                $hidden = '';
                $requests = array();
                if( count($res) > 0 ){
                  $requests [] = $res;
                  //TODO: Would be very nice but has a lot of problems
                  //you really need to merge groups in forder for this to work
                  //so, for now, i'll leave the above think, which, of course,
                  //is a HACK!!!
                  /**
                  $eids = extract_column( 'eid', $res );
                  foreach( $eids as $v ){
                      $q = "SELECT p.* FROM ".TABLE_PEOPLE." p, ".TABLE_IN_GROUP." i
                              WHERE i.group_id=(SELECT j.group_id FROM ".TABLE_IN_GROUP." j WHERE j.eid='$v')
                                AND p.eid=i.eid";
                      $requests[] = sqlToArray( mysql_query( $q ) );
                  }
                  **/
                  $hidden = 'display:none';
                }
                echo '<div class="none" style="text-indent:10px;'.$hidden.'">none so far...</div>';
                foreach( $requests as $group ){
                  foreach( $group as $person ){
                    echo getFaceHTML_received( $person );
                  }
                }
              ?>
            </div>
            
            <br />
            <h3>Requests sent</h3>
            <div id="requests-sent">
              <?php
                $q      = "SELECT p.* FROM People p, Requests r WHERE r.eid_from='$eid' AND p.eid=r.eid_to";
                $res    = sqlToArray( mysql_query( $q ) );
                $hidden = count( $res ) == 1 ? 'display:none' : '';
                echo '<div class="none" style="text-indent:10px;'.$hidden.'">none so far...</div>';
                foreach( $res as $person ){
                  echo getFaceHTML_sent( $person );
                }
              ?>
            </div>
          </div>
        </div>
        
        <div class="clearBoth"></div>
        
        <?php
          if( HAS_ROOM ){
            $q = "SELECT p.fname,p.lname,a.* 
                  FROM ".TABLE_ALLOCATIONS." a, ".TABLE_PEOPLE." p
                  WHERE a.eid IN ($eid,".implode(',',extract_column('eid',$roommates)).")
                    AND p.eid=a.eid ORDER BY a.room";
            $tmp_rooms = sqlToArray( mysql_query( $q ) );

            $h = '';
            foreach( $tmp_rooms as $info ){
              $options = '';
              foreach( $tmp_rooms as $v ){
                $selected = ($v['eid'] == $info['eid']) ? 'selected="selected"' : '';
                $options .= ' <option value="'.$v['eid'].'" '.$selected.'>'.
                                $v['fname'].' '.$v['lname'].
                              '</option>';
              }
              $tag = 'room-'.$info['room'];
              $h .= '<label for="'.$tag.'">'.
                      $info['room'].':
                      <select name="'.$tag.'" id="'.$tag.'">
                        '.$options.'
                      </select>
                    </label><br />';
            }
            
            echo '
              <div class="content">
                <form class="wrapper" id="select-rooms" action="ajax.php" method="get">
                  <h3>Final Step: Choose Rooms</h3>
                  '.$h.'
                  <input type="submit" value="Change Rooms" />
                </form>
              </div>
            ';
        ?>
          
          
          
        <?php
          } else {
        ?>
        
        <div class="content" id="floorPlan">
          <div class="wrapper">
            <h3>Apartment Choices</h3>
            <?php 
              if( $points['total'] < MIN_POINTS || $points['total'] > MAX_POINTS ){
                echo '<div style="color:red">
                        You need to have between '.MIN_POINTS.' and '.MAX_POINTS.'
                        total points in order to be eligible for this round
                      </div>';
              } else if( count($roommates) < MIN_ROOMMATES ){
                echo '<div style="color:red">
                        You need to have between '.MIN_ROOMMATES.' and '.MAX_ROOMMATES.'
                        roommates in order to be eligible for this round
                      </div>';
              } else if( count($roommates) <= MAX_ROOMMATES ){ 
            ?>
              <div class="content" style="float:left;width:35%;">
                <ol class="room-choices">
                  <?php
                    $q_choices = "SELECT * FROM ".TABLE_APARTMENT_CHOICES." 
                                    WHERE group_id='${group['group_id']}'";
                    $choices            = array_fill( 0, MAX_ROOM_CHOICES, array() );
                    $apartment_choices  = sqlToArray( mysql_query( $q_choices ) );
                    foreach( $apartment_choices as $row ){
                      $choices[(int)$row['choice']][] = $row['number'];
                    }
                    foreach( $choices as $number => $value ){
                      sort( $choices[$number] );
                    }
                    if( $info['college'] == 'Nordmetall' ){
                      $hash       = function($v){ sort($v); return implode(',', $v); };
                      $nm         = $Nordmetall_apartments;
                      $nm         = array_map($hash, $nm);
                      $choice_map = array_flip( array_map($hash, $choices) );
                      for( $i=0; $i<MAX_ROOM_CHOICES; ++$i ){
                        echo '<li>';
                        echo '<select name="choice[]" id="input-room-choice-'.$i.'">';
                        echo '<option></option>';
                        foreach( $nm as $apartment ){
                          $selected = '';
                          if( isset($choice_map[$apartment]) && $choice_map[$apartment] == $i ){
                            $selected = 'selected="selected"';
                          }
                          echo '<option '.$selected.'>'.$apartment.'</option>';
                        }
                        //    <input type="text" id="input-room-choice-'.$i.'" name="choice[]" value="'.implode(',',$choices[$i]).'" />
                        echo '</select>';
                        echo '</li>';
                      }
                    } else {
                      for( $i=0; $i<MAX_ROOM_CHOICES; ++$i ){
                        echo '<li>';
                        echo '<input type="text" id="input-room-choice-'.$i.'" name="choice[]" value="'.implode(',',$choices[$i]).'" />';
                        echo '</li>';
                      }
                    }
                    echo '<li style="list-style-type:none"><input type="submit" value="Save Changes!" id="choose_rooms" /></li>';
                  ?>
                </ol>
              </div>
              
              <div class="content" style="float:right;width:65%;">
                <h3>How it works</h3>
                <ul>
                  <li>Decide on the apartments you want</li>
                  <?php if( $info['college'] != 'Nordmetall' ){ ?>
                  <li>
                    Fill in the fields with all the rooms that belong 
                    to the apartment( 1, 2 or 3 rooms depending on the 
                    apartment type ), <b style="color:red">separated with a comma</b>
                  </li>
                  <li>You can use the floor-plan bellow to choose your rooms more easily</li>
                  <li>
                    Just click on the apartment you want 
                    and then select it as what choice you want it to be
                  </li>
                  <?php } ?>
                  <li>
                    Make sure to fill as many options as possible, 
                    because if you do not get assigned any room, 
                    you will get one at random
                  </li>
                  <li>Also, you can change the rooms at any time while the round is open</li>
                  <li><b>Don't forget</b> to hit the <b>Save Changes!</b> button!</li>
                </ul>
              </div>
              
              <div class="clearBoth"></div>
              
              <?php if($info['college'] != 'Nordmetall'){ ?>
                <br />
                <h3>Floor Plan</h3>
                <?php
                  
                  $q = "SELECT eid,room,college FROM ".TABLE_ALLOCATIONS." WHERE eid='$eid'";
                  $d = mysql_fetch_assoc( mysql_query( $q ) );
                  if( $d['college'] ){
                    $classes = array();
                    
                    $q_taken = "SELECT * FROM ".TABLE_ALLOCATIONS." 
                                WHERE college='${d['college']}' AND room IS NOT NULL";
                    $taken = sqlToArray( mysql_query( $q_taken ) );

                    add_class( 'available', $allowed_rooms[$d['college']], $classes );
                    add_class( 'taken', extract_column( 'room', $taken ), $classes );
                    add_class( 'chosen', extract_column( 'number', $apartment_choices ), $classes );
                    
                    $classes = array_map(function($v){return implode(' ',$v);}, $classes);
                    
                    switch( $d['college'] ){
                      case 'Krupp':       echo renderMap( $Krupp, $classes ); break;
                      case 'Mercator':    echo renderMap( $Mercator, $classes ); break;
                      case 'College-III': echo renderMap( $College3, $classes ); break;
                      //case 'Nordmetall':  echo renderMap( $Nordmetall, $classes ); break;
                      default:            echo "Unknown college: <b>${d['college']}<br />";
                    }
                  } else {
                    echo 'You are not assigned to any college';
                  }
                  
                ?>
              <?php } ?>
              
            <?php 
              } else {
                echo '<div style="color:red">You need to have "'.MAX_ROOMMATES.'" 
                        roommate(s) for this phase in order to be eligible to apply for a room';
              }
            ?>
          </div>
        </div>
        <?php
            }
          } else {
        ?>
          <div class="content">
            <div class="wrapper">
              Waiting for your credentials :)
            </div>
          </div>
          
        <?php
          }
        ?>
        
      </div>
      
      <div id="footer" class="message info">
        <span style="float:left">(C) 2012 code4fun.de</span>
        Designed and developed by 
        <a title="contact me if anything..." href="mailto:s.mirea@jacobs-university.de">Stefan Mirea</a>
      </div>
      
    </div>
  </body>
</html>
