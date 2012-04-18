<?php
  
  require_once 'config.php';
  require_once 'campusnet.php';
  
  define( 'FILE_SELF', basename($_SERVER['PHP_SELF']) );
  $message  = false;
  $login    = false;
?>

<div id="login">
  <?php
    if( isset($_GET['logout']) ){
      unset( $_SESSION['username'] );
      session_destroy();
      header('Location:'.FILE_SELF);
    } else if( 
      !isset($_SESSION['username'])
      && isset($_POST['login'])
      && isset($_POST['username']) 
      && (DEBUG || isset($_POST['password']))
      && !($message = check_login($_POST['username'], $_POST['password']))
    ){
      $q    = " SELECT 
                  p.*, 
                  (SELECT i.group_id FROM ".TABLE_IN_GROUP." i WHERE i.eid=p.eid) AS group_id 
                FROM ".TABLE_PEOPLE." p 
                WHERE account='${_POST['username']}'";
      $info = mysql_fetch_assoc( mysql_query( $q ) );
      $min_year = (int)date('Y') % 100;
      if( ($info['status'] == STATUS_UNDERGRAD && $info['year'] > $min_year)
          || ($info['status'] == STATUS_FOUNDATION && $info['year'] == $min_year)
      ) {
        $college  = "SELECT college FROM ".TABLE_ALLOCATIONS." WHERE eid='${info['eid']}'";
        $college  = mysql_fetch_assoc( mysql_query( $college ) );
        $college  = $college['college'];
        $_SESSION['username'] = $info['account'];
        $_SESSION['eid']      = $info['eid'];
        $_SESSION['info']     = $info;
        header('Location:'.FILE_SELF);
      } else {
        $message = '<div style="color:red">Records show that you are not going to be 
                        at Jacobs next year, therefore you do not need a room</div>';
      }
    }
    
    if( isset($_SESSION['username']) ){
      $login = true;
  ?>
      Logged in as <b><?php echo $_SESSION['username']; ?></b>
      <a href="<?php echo FILE_SELF; ?>?logout=true">[log out]</a>
  <? 
    } else {
  ?>

    <form action="<?php echo FILE_SELF; ?>" method="post">
      <?php
        if( DEBUG ){
          echo '<div style="color:green">Debug mode enabled. No password required!</div>';
          echo '<div>If you do not know what username to use, try: <b>smirea, bmatican, cprodescu, jbrenstein</b></div>';
        }
      ?>
      <input type="hidden" name="login" value="true" />
      <?php if( is_string( $message ) ){ echo $message; } ?>
      <input type="text" name="username" placeholder="username" /><input type="password" name="password" placeholder="password" /><input type="submit" value="Log In" />
    </form>
<?php 
  }
  
  define( 'LOGGED_IN', $login );
  
  if( isset($_SESSION['username']) ){
    define( 'IS_ADMIN', in_array( $_SESSION['username'], $admin ) );
    define( 'USERNAME', $_SESSION['username'] );
  } else {
    define( 'IS_ADMIN', false );
    define( 'USERNAME', null );
  }
  
  function check_login( $user, $pass ){
    $user = strtolower( $user );
    if( !DEBUG ){
      if( !loginToCampusNet( $user, $pass ) ){
        return '<div style="color:red">Invalid credentials!</div>';
      }
    } else {
      $q = "select id from ".TABLE_PEOPLE." where account='$user'";
      if( mysql_num_rows( mysql_query( $q ) ) == 0 ){
        return '<div style="color:red">Although debug enabled, you must still provide a valid account</div>';
      }
    }
    return '';
  }
?>
</div>
