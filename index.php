<?php
  require_once 'config.php';
  require_once 'utils.php';
?>
<html>
  <head>
    <link rel="stylesheet" type="text/css" href="css/html5cssReset.css" />
    <link rel="stylesheet" type="text/css" href="css/jquery-ui/jquery-ui.css" />
    <link rel="stylesheet" type="text/css" href="css/messages.css" />
    <link rel="stylesheet" type="text/css" href="css/gh-buttons.css" />
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
        ?>
        <div style="float:left;width:50%;" class="content">
          <div class="wrapper">
            <h3>Profile</h3>
            <?php
                $q = "select eid,fname,lname,country,year from ".TABLE_PEOPLE." where account='".USERNAME."'";
                $info = sqlToArray( mysql_query( $q ) );
                echo getFaceHTML( $info[0] );
            ?>
            
            <h3>Add a new roommate</h3>
            <form id="searchBox" method="post">
              <input type="hidden" name="eid" id="roommate-eid" />
              <input type="text" id="search" placeholder="start typing your roommate's name..." />
              <input type="submit" id="addRoommate" value="Add" />
            </form>
          </div>
        </div>
        
        <div style="float:right;width:50%;" class="content">
          <div class="wrapper">
            <h3>Current Roommates</h3>
            <div id="current-roommates">
              <?php
                $q      = "SELECT p.* FROM ".TABLE_PEOPLE." p, ".TABLE_ROOMMATES." m WHERE m.eid_a='${_SESSION[eid]}' AND p.eid=m.eid_b";
                $res    = sqlToArray( mysql_query( $q ) );
                $hidden = count( $res ) == 1 ? 'display:none' : '';
                echo '<div class="none" style="text-indent:10px;'.$hidden.'">none so far...</div>';
                foreach( $res as $person ){
                  echo getFaceHTML( $person );
                }
              ?>
            </div>
            
            <br />
            <h3>Requests received</h3>
            <div id="requests-received">
              <?php
                $q      = "SELECT p.* FROM People p, Requests r WHERE r.eid_to='${_SESSION[eid]}' AND p.eid=r.eid_from";
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
                $q      = "SELECT p.* FROM People p, Requests r WHERE r.eid_from='${_SESSION[eid]}' AND p.eid=r.eid_to";
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