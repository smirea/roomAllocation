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
    <link rel="stylesheet" type="text/css" href="css/jquery.qtip.css" />
    <link rel="stylesheet" type="text/css" href="css/floorPlan.css" />
    <link rel="stylesheet" type="text/css" href="css/roomAllocation.css" />
    <link rel="stylesheet" type="text/css" href="css/admin.css" />

    <script src="js/jquery.js"></script>
    <script src="js/jquery-ui.js"></script>
    <script src="js/jquery.qtip.js"></script>
  </head>
  
  <body>
    
    <div id="main-admin">
      <?php 
        require_once 'login.php'; 
        if( !IS_ADMIN ) 
          exit( "<b style=\"color:red\">You do not have permissions to access this page</b>" );
        
        $people = array();  // maps: eid      -> personal info
        $rooms  = array();  // maps: room     -> group_id
        $choic  = array();  // maps: room     -> choice
        $groups = array();  // maps: group_id -> eid
        $points = array();  // maps: group_id -> points
          
        $q = "SELECT * FROM ".TABLE_APARTMENT_CHOICES." WHERE college='Mercator'";
        $rooms_tmp  = sqlToArray( mysql_query($q) );
        foreach( $rooms_tmp as $room ){
          $rooms[$room['number']][] = $room['group_id'];
        }
        
        $q = "SELECT i.group_id, p.* FROM ".TABLE_PEOPLE." p, ".TABLE_IN_GROUP." i
                    WHERE i.eid=p.eid ORDER BY i.group_id";
        $groups_tmp = sqlToArray( mysql_query($q) );
        
        foreach( $groups_tmp as $v ){
          $people[$v['eid']] = $v;
          $groups[$v['group_id']][] = $v['eid'];
        }
        
        foreach( $groups as $group_id => $group ){
          $points[$group_id] = get_points( $group );
        }
        
        $classes    = array();
        $arguments  = array();
        foreach( $rooms as $number => $room ){
          $classes[$number] = 'chosen';
          $arguments[$number]['country'] = array(
            'fname'   => '', 
            'lname'   => '', 
            'country' => ''
          );
        }
        
        echo renderMap( $Mercator, $classes, $arguments );
      ?>
    </div>
    
  </body>
</html>