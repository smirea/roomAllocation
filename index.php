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
  require_once 'models/Apartment_Choice_Model.php';
  require_once 'models/Allocation_Model.php';


  $Allocation_Model = new Allocation_Model();
  $Apartment_Choice_Model = new Apartment_Choice_Model();
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
    <link rel="stylesheet" id="themestyle" href="css/jquery-tour/theme4/style.css" />

    <script src="js/jquery.js"></script>
    <script src="js/jquery-ui.js"></script>
    <script src="js/jquery.qtip.js"></script>
    <script src="js/jTour.min.js"></script>
    <script src="js/lib.js"></script>

    <script src="js/roomAllocation.js"></script>

  </head>

  <body>

    <div id="main">
      <?php require_once 'login.php'; ?>
      <?php
        if (LOGGED_IN) {
          echo '<script src="js/tour_roomphase.js"></script>';
        }
      ?>
      <div id="wrapper">
        <?php
          if( LOGGED_IN ){

            /** Set some info */
            $eid        = $_SESSION['info']['eid'];
            $group      = group_info( $eid );
            $roommates  = get_roommates( $eid, $group['group_id'] );
            $info       = $_SESSION['info'];
            $points     = get_points( array_merge( array($info), $roommates ) );

            $allocation = $Allocation_Model->get_allocation($eid);
            define( 'HAS_ROOM', $allocation['room'] != null );

            if( !HAS_ROOM ){
              $rooms_taken  = extract_column('room', $Allocation_Model->get_all_rooms_from_college($allocation['college']));
              $rooms_locked = array_map( 'trim', explode( ',', C("disabled.${info['college']}") ) );
            }

        ?>
          <?php 
            $message_types = array('info', 'warning', 'error');
            foreach ($message_types as $message_name) {
              $message = C("message.$message_name");
              if (!empty($message)) {
                echo '<div class="clerafix message '.$message_name.'">
                  <div class="content">'.$message.'</div>
                </div>';
              }
            }
          ?>
        <div style="float:left;width:50%;" class="content">
          <div class="wrapper">
            <h3>Profile</h3>
            <?php
                echo getFaceHTML( $info, '', 'my-profile' );
            ?>

            <br />
            <h3>Add a new roommate</h3>
            <form id="searchBox" method="post">
              <input type="hidden" name="eid" id="roommate-eid" />
              <input type="text" id="search" placeholder="start typing your roommate's name..." />
              <input type="submit" id="addRoommate" value="Add" />
            </form>
            <div id="options">
              <?php
                if (C('roommates.freshman')) {
                  $checked = '';
                  if (count($roommates) == 1 && $roommates[0]['eid'] == FRESHMAN_EID) {
                    $checked =  'checked="checked"';
                  }
                  echo '
                    <label for="toggle_freshman" style="display:block;margin:4px 0;">
                      <input type="checkbox" name="toggle_freshman" id="toggle_freshman" '.$checked.' />
                      Choose a freshman as a roommate
                    </label>
                  ';
                }
                echo '
                  <label for="toggle_absent" style="display:block;margin:4px 0;">
                    <input type="checkbox" name="toggle_absent" id="toggle_absent" '.($info['absent'] ? 'checked="checked"' : '').' />
                    I am going to be absent for a semester
                  </label>
                ';
              ?>
            </div>

            <br />

            <h3>Points</h3>
            <div id="total-points">
              <?php
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
                  echo getFaceHTML( $person, '', 'roommate-profile');
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
                  $hidden = 'display:none';
                }
                echo '<div class="none" style="text-indent:10px;'.$hidden.'">none so far...</div>';
                foreach( $requests as $group ){
                  foreach( $group as $person ){
                    echo getFaceHTML_received( $person, '', 'request-recieved-profile' );
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
                  echo getFaceHTML_sent( $person, '', 'request-sent-profile' );
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
              $h .= '<tr>
                      <td style="vertical-align:middle;"><label for="'.$tag.'">'.$info['room'].':</label> </td>
                      <td>
                        <select name="'.$tag.'" id="'.$tag.'">
                          '.$options.'
                        </select>
                      </td>
                    </tr>';
            }

            echo '
              <div class="content">
                <form class="wrapper" id="select-rooms" action="ajax.php" method="get">
                  <h3>Final Step: Choose Rooms</h3>
                  <table>
                    '.$h.'
                    <tr>
                      <td colspan="2" style="text-align:right">
                        <input type="submit" value="Change Rooms" />
                      </td>
                    </tr>
                  </table>
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
                    $choices            = array_fill( 0, MAX_ROOM_CHOICES, array() );
                    $apartment_choices  = $Apartment_Choice_Model->get_all_choices($group['group_id']);
                    foreach( $apartment_choices as $row ){
                      $choices[(int)$row['choice']][] = $row['number'];
                    }
                    foreach( $choices as $number => $value ){
                      sort( $choices[$number] );
                    }
                    if( $allocation['college'] == 'Nordmetall' ){
                      $nm = $Nordmetall_apartments;
                      if (C('round.restrictions')) {
                        $tmp_apts = array();
                        foreach ($allowed_rooms['Nordmetall'] as $room_number) {
                          $apt = get_apartment_NM($room_number);
                          if (count($apt) <= 1) {
                            continue;
                          }
                          $apt = implode(',', $apt);
                          $tmp_apts[$apt] = true;
                        }
                        $nm = array_map(function ($v) { return explode(',',$v); }, array_keys($tmp_apts));
                      }
                      $taken = array_merge( $rooms_locked, $rooms_taken );
                      // remove all rooms that are already taken/disabled
                      foreach( $Nordmetall_apartments as $key => $apartment ){
                        foreach( $apartment as $room ){
                          if( in_array( $room, $taken ) ){
                            unset( $nm[$key] );
                            break;
                          }
                        }
                      }
                      $hash       = function($v){ sort($v); return implode(',', $v); };
                      // $nm         = array_map($hash, $nm);
                      $choice_map = array_flip( array_map($hash, $choices) );
                      for( $i=0; $i<MAX_ROOM_CHOICES; ++$i ){
                        echo '<li>';
                        echo '<select name="choice[]" id="input-room-choice-'.$i.'">';
                        $options = array();
                        $has_default = false;
                        foreach( $nm as $apartment ){
                          $selected = '';
                          $apt_hash = $hash($apartment);
                          if( isset($choice_map[$apt_hash]) && $choice_map[$apt_hash] == $i ){
                            $selected = 'selected="selected"';
                            $has_default = true;
                          }
                          $tall = is_tall_apartment($apartment) ? ' [tall]' : '';
                          $options[] = '<option value="'.$apt_hash.'" '.$selected.'>'.$apt_hash.$tall.'</option>';
                        }
                        array_unshift($options, '<option '.($has_default ? '' : 'selected="selected"').'></option>');
                        echo implode(' ', $options);
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
                    echo '<li style="list-style-type:none; text-align:center; margin-top:5px;"><div id="choose_rooms" class="gh-button">Save Changes!</div></li>';
                  ?>
                </ol>
              </div>

              <div class="content" style="float:right;width:65%;">
                <h3>How it works</h3>
                <ul>
                  <li>Decide on the apartments you want</li>
                  <?php if( $allocation['college'] != 'Nordmetall' ){ ?>
                  <li>You can use the floor-plan bellow to choose your rooms more easily</li>
                  <li>
                    Just click on the apartment you want and then select it as what choice you want it to be
                  </li>
                  <?php } ?>
                  <li>
                    Make sure to fill as many options as possible, because if you do not get assigned any room, you will get one at random
                  </li>
                  <li>Also, you can change the rooms at any time while the round is open</li>
                  <li style="color:red"><b>Don't forget</b> to hit the <b>Save Changes!</b> button!</li>
                </ul>
                <div id="beginTour" class="gh-button">Start Tour</div>
              </div>

              <div class="clearBoth"></div>

              <?php if($allocation['college'] != 'Nordmetall'){ ?>
                <br />
                <h3>Floor Plan</h3>
                <?php
                  if ($allocation['college']) {
                    $college_map = null;
                    switch( $allocation['college'] ){
                      case 'Mercator': 
                        $college_map = $Mercator; 
                        break;
                      case 'Krupp': 
                        $college_map = $Krupp; 
                        break;
                      case 'College-III': 
                        $college_map = $College3; 
                        break;
                      default: 
                        $college_map = null;
                    }
                    $classes = array();
                    $college_rooms = get_floorplan_rooms($college_map);
                    if( C('round.restrictions') ) {
                      add_class(
                        'taken', 
                        array_diff($college_rooms, $allowed_rooms[$allocation['college']]),
                        $classes
                      );
                    }
                    $tall_rooms = array();
                    foreach ($college_rooms as $room) {
                      $ap = get_apartment($room);
                      if (is_tall_apartment($ap)) {
                        $tall_rooms = array_merge($tall_rooms, $ap);
                      }
                    }
                    add_class('tall', $tall_rooms, $classes);
                    add_class('taken', $rooms_taken, $classes);
                    add_class('taken', $rooms_locked, $classes);
                    add_class('chosen', extract_column( 'number', $apartment_choices ), $classes);


                    $classes = array_map(function($v){return implode(' ',$v);}, $classes);
                    echo renderMap( $college_map, $classes );
                  } else {
                    echo 'You are not assigned to any college';
                  }

                ?>
              <?php } ?>

            <?php
                // End IF ($college != 'Nordmetall')
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
        <span style="float:left">(C) 2013 code4fun.de</span>
        Designed and developed by
        <a title="contact me if anything..." href="mailto:s.mirea@jacobs-university.de">Stefan Mirea</a>
      </div>

    </div>
  </body>
</html>
