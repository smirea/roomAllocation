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
  require_once 'models/Person_Model.php';
  require_once 'models/College_Choice_Model.php';

  $Allocation_Model = new Allocation_Model();
  $Apartment_Choice_Model = new Apartment_Choice_Model();
  $Person_Model = new Person_Model();
  $College_Choice_Model = new College_Choice_Model();
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
    <link rel="stylesheet" id="themestyle" href="css/jquery-tour/theme2/style.css" />

    <script src="js/jquery.js"></script>
    <script src="js/jquery-ui.js"></script>
    <script src="js/jquery.qtip.js"></script>
    <script src="js/lib.js"></script>
    <script src="js/roomAllocation.js"></script>
    <script src="js/jTour.min.js"></script>
  </head>

  <body>
    <div id="main">
      <?php require_once 'login.php'; ?>

      <div class="message-holder">
        <div id="message-info" class="info message">
          <div class="content"></div>
          <a href="javascript:void(0)" class="close">X</a>
        </div>
        <div id="message-error" class="error message">
          <div class="content"></div>
          <a href="javascript:void(0)" class="close">X</a>
        </div>
        <div id="message-warning" class="warning message">
          <div class="content"></div>
          <a href="javascript:void(0)" class="close">X</a>
        </div>
        <div id="message-success" class="success message">
          <div class="content"></div>
          <a href="javascript:void(0)" class="close">X</a>
        </div>
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

            $allocation = $Allocation_Model->get_allocation($eid);
            define( 'HAS_ROOM', $allocation['room'] != null );

            if( !HAS_ROOM ){
              $rooms_taken  = extract_column('room', $Allocation_Model->get_all_rooms_from_college($info['college']));
              $rooms_locked = array_map( 'trim', explode( ',', C("disabled.${info['college']}") ) );
            }

        ?>
        <div style="float:left;width:50%;" class="content">
          <div class="wrapper">
            <h3>Profile</h3>
            <?php
                echo getFaceHTML( $info );
            ?>

            <br />
            <?php if (C('round.active') && C('round.type') === 'college') { ?>
            <h3>College Choice</h3>
            <ul id="college_choices_sort">
            <?php   
              function makeCollege ($c, $position) {
                return '<li id="choice_'.$c.'" class="ui-state-default college-choice"><span class="number">'.$position.'</span><span class="name">'.$c.'</span></li>';
              }

              $choice_colleges = $College_Choice_Model->get_choices($_SESSION['eid']);
              if (count($choice_colleges) == 0 || !$choice_colleges) {
                  $remaining_colleges = array("College-III", "Mercator", "Nordmetall", "Krupp");
                  $info = $Person_Model->get($_SESSION['eid']);
                  $college = $info['college'];
                  $pos_college = array_search($college, $remaining_colleges);
                  array_splice($remaining_colleges, $pos_college, 1);
                  $initial_choice = array('eid' => $_SESSION['eid']);
                  echo makeCollege($college, 1);
                  $initial_choice['choice_0'] = $college;
                  for ($i=0; $i<count($remaining_colleges); ++$i) {
                    echo makeCollege($remaining_colleges[$i], $i + 2);
                    $initial_choice['choice_'.($i+1)] = $remaining_colleges[$i];
                  }
                  $initial_choice['exchange'] = 0;
                  $initial_choice['quiet'] = 0;
                  $College_Choice_Model->set_choices($initial_choice);
              } else {
                for ($i=0; $i < 4; ++$i) {
                  echo makeCollege($choice_colleges['choice_' . $i], $i + 1);
                }

              } 

            ?>
            </ul>    
            <div id="further_infos_college">    
              <div id="exchange" class="college-additional-options">    
                <?php if ($choice_colleges['exchange'] == 1) { ?>
                  <input id="exchange_checkbox" type="checkbox" name="exchange" checked>
                <?php } else { ?>
                  <input id="exchange_checkbox" type="checkbox" name="exchange">
                <?php } ?>
                <label for="exchange_checkbox">I won't be on campus next semester.</label>
              </div>
              <div id="quiet_zone" class="college-additional-options">
                <?php if ($choice_colleges['quiet'] == 1) { ?>
                  <input id="quiet_zone_checkbox" type="checkbox" name="quiet-zone" checked>
                <?php } else { ?>
                  <input id="quiet_zone_checkbox" type="checkbox" name="quiet-zone">
                <?php } ?>
                <label for="quiet_zone_checkbox">I would consider living on a quiet floor.</label>
              </div>
            </div>

            <?php } ?>
            
          </div>
        </div>

        <?php if (C('round.active') && C('round.type') === 'college') { ?>
        <div style="float:right;width:50%;" class="content">
          <div class="wrapper">
            <h3>Info</h3>
            <p>Welcome to the college-phase in the process of <b>Room Allocation 2013</b>. <br /><br />
            We tried to make the approach as straightforward as possible. Simply drag and drop the different college names in the order in which you prefer them as your college next year. Hereby the college on position <b>1.</b> represents your <b>most</b> favorite college for next year and position <b>4.</b> the <b>least</b> favorite choice. <br /><br />
            For a step by step guide please click here: </p>
            <div id="beginTour" class="gh-button">Start Tour</div>
            <br />
            

            
          </div>
        </div>

        <?php } else { ?>

          <div style="float:right;width:50%;" class="content">
          <div class="wrapper">
            <h3 style="color: red;">No Round!</h3>
            <p style="color: red;">No round is currently active. Please wait until the next round.</p>
            <br />
            
          </div>
        </div>
        <?php } ?>

        <div class="clearBoth"></div>

        <?php } ?>

      </div>

      <div id="footer" class="message info">
        <span style="float:left">(C) 2012 code4fun.de</span>
        Designed and developed by
        <a title="contact me if anything..." href="mailto:s.mirea@jacobs-university.de">Stefan Mirea</a>
      </div>

    </div>
  </body>
</html>
