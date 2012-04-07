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
    <link rel="stylesheet" type="text/css" href="css/html5cssReset.css" />
    <link rel="stylesheet" type="text/css" href="css/jquery-ui/jquery-ui.css" />
    <link rel="stylesheet" type="text/css" href="css/messages.css" />
    <link rel="stylesheet" type="text/css" href="css/gh-buttons.css" />
    <link rel="stylesheet" type="text/css" href="css/floorPlan.css" />
    <link rel="stylesheet" type="text/css" href="css/roomAllocation.css" />

    <script src="js/jquery.js"></script>
    <script src="js/jquery-ui.js"></script>
    <script src="js/roomAllocation.js"></script>
  </head>
  
  <body>
    <div id="main">
      <?php require_once 'login.php'; ?>
      
      <div id="message-info" class="info message"><div class="content"></div></div>
      <div id="message-error" class="error message"><div class="content"></div></div>
      <div id="message-warning" class="warning message"><div class="content"></div></div>
      <div id="message-success" class="success message"><div class="content"></div></div>
      
      <div id="wrapper">
        <?php
          if( LOGGED_IN ){
            
            /** GET ALL YOUR ROOMMATES */
            $group  = group_info( $_SESSION['info']['eid'] );
            $eid    = $_SESSION['info']['eid'];
            $q      = "SELECT p.* FROM ".TABLE_PEOPLE." p, ".TABLE_IN_GROUP." i 
                        WHERE i.group_id='${group['group_id']}' 
                        AND p.eid=i.eid AND i.eid<>'$eid' ";
            $roommates = sqlToArray( mysql_query( $q ) );
            $hidden = count( $roommates ) == 1 ? 'display:none' : '';
          
        ?>
        <div style="float:left;width:50%;" class="content">
          <div class="wrapper">
            <h3>Profile</h3>
            <?php
                $q = "SELECT * FROM ".TABLE_PEOPLE." WHERE eid='${_SESSION['eid']}'";
                $info = mysql_fetch_assoc( mysql_query( $q ) );
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
                
                echo print_score( array_merge( array($info), $roommates ) );
            ?>
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
                $q      = "SELECT p.* FROM People p, Requests r WHERE r.eid_to='${_SESSION['eid']}' AND p.eid=r.eid_from";
                $res    = sqlToArray( mysql_query( $q ) );
                $hidden = count( $res ) == 1 ? 'display:none' : '';
                echo '<div class="none" style="text-indent:10px;'.$hidden.'">none so far...</div>';
                foreach( $res as $person ){
                  echo getFaceHTML_received( $person );
                }
              ?>
            </div>
            
            <br />
            <h3>Requests sent</h3>
            <div id="requests-sent">
              <?php
                $q      = "SELECT p.* FROM People p, Requests r WHERE r.eid_from='${_SESSION['eid']}' AND p.eid=r.eid_to";
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
        <div class="content" id="floorPlan">
          <div class="wrapper">
            <h3>Room Choices</h3>
            <div class="hint">*
              <b>Note:</b> you can use the floor-plan bellow to choose your rooms more easily.<br />
              Just click on the apartment you want and then select it as what choice you want it to be
            </div>
            <ol class="room-choices">
              <?php
                for( $i=0; $i<MAX_ROOM_CHOICES; ++$i ){
                  echo '<li><input type="text" id="input-room-choice-'.$i.'" name="choice[]" /></li>';
                }
                echo '<li style="list-style-type:none"><input type="submit" value="Apply!" id="choose_rooms" /></li>';
              ?>
            </ol>
            
            <br />
            <h3>Floor Plan</h3>
            <?php
              
              $q = "SELECT eid,room,college FROM ".TABLE_ALLOCATIONS." WHERE eid='${_SESSION['eid']}'";
              $d = mysql_fetch_assoc( mysql_query( $q ) );
              
              if( $d['college'] ){
                $q_taken = "SELECT * FROM ".TABLE_ALLOCATIONS." WHERE college='${d['college']}'";
                $taken = sqlToArray( mysql_query( $q ) );
                $taken = array_map(function($v){ return $v['room']; }, $taken);
                switch( $d['college'] ){
                  case 'Krupp':       echo renderMap( $Krupp, $taken ); break;
                  case 'Mercator':    echo renderMap( $Mercator, $taken ); break;
                  case 'College-III': echo renderMap( $College3, $taken ); break;
                  case 'Nordmetall':  echo renderMap( $Nordmetall, $taken ); break;
                  default:            echo "Unknown college: <b>${d['college']}<br />";
                }
              } else {
                echo 'You are not assigned to any college';
              }
            ?>
          </div>
        </div>
        <?php
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
        Designed and developed by Stefan Mirea
      </div>
    </div>
  </body>
</html>
