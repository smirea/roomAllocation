<?php
  
  require_once 'config.php';
  require_once 'utils.php';
  require_once 'utils_admin.php';
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
    <link rel="stylesheet" type="text/css" href="css/admin.css" />

    <script src="js/jquery.js"></script>
    <script src="js/jquery-ui.js"></script>
    <script src="js/jquery.qtip.js"></script>
    <script src="js/lib.js"></script>
    <script src="js/admin.js"></script>
  </head>
  
  <body>
    
    <div id="main-admin">
      <?php 
        require_once 'login.php'; 
        
        if( !IS_ADMIN ) 
          exit( "<b style=\"color:red\">You do not have permissions to access this page</b>" );

        ?>
        
        <div id="menu">
          [ <a href="javascript:void(0)" onclick="setView(this, $('.college-floorPlan'))">Floor Plan</a> ]
          [ <a href="javascript:void(0)" onclick="setView(this, $('.display-floorPlan'))">Choice List</a> ]
          [ <a href="javascript:void(0)" onclick="setView(this, $('.display-final'))">Final Result</a> ]
        </div>
        
        <?php
          
        echo '<h3>Mercator College</h3>';
        print_floorPlan( 'Mercator', $Mercator );
        
        echo '<h3>Krupp College</h3>';
        print_floorPlan( 'Krupp', $Krupp );
        
        echo '<h3>College-III</h3>';
        print_floorPlan( 'College-III', $College3 );
        
        
      ?>
    </div>
    
  </body>
</html>