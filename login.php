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
  require_once 'campusnet.php';

  require_once 'models/Allocation_Model.php';
  require_once 'models/Person_Model.php';

  $Allocation_Model = new Allocation_Model();
  $Person_Model = new Person_Model();

  define('FILE_SELF', basename($_SERVER['PHP_SELF']));
  $message  = false;
?>

<div id="login">
  <?php
    recursive_escape( $_GET );
    recursive_escape( $_POST );
    if( isset($_GET['logout']) ){
      unset( $_SESSION['username'] );
      session_destroy();
      header('Location:'.FILE_SELF);
    } else if(
      !isset($_SESSION['username'])
      && isset($_POST['login'])
    ) {
      if (
        isset($_POST['username'])
        && (DEBUG || isset($_POST['password']))
        // && !($message = check_login($_POST['username'], $_POST['password']))
        && (random_password_login($_POST['username'], $_POST['password']) ||
            // HACK UUUUUBER HAAAAAAAAAACK
            // SEEEEECURITY RISK!
            strlen(loginToCampusNet($_POST['username'], $_POST['password'])) > 10000
        )
      ) {
        if (strlen($_POST['username']) == 0) {
          $message = '<div style="color:red">You might want to consider logging in using a username :)</div>';
        } else {
          $q    = " SELECT
                      p.*,
                      (SELECT i.group_id FROM ".TABLE_IN_GROUP." i WHERE i.eid=p.eid) AS group_id
                    FROM ".TABLE_PEOPLE." p
                    WHERE account='${_POST['username']}'";
          $info = mysql_fetch_assoc( mysql_query( $q ) );
          $min_year = (int)date('Y') % 100;
          if( in_array( $info['account'], $admin )
              || ($info['status'] == STATUS_UNDERGRAD && $info['year'] > $min_year)
              || ($info['status'] == STATUS_FOUNDATION && $info['year'] == $min_year)
          ) {
            $college  = $Allocation_Model->get_allocation($info['eid']);
            $college  = $college['college'];
            $info['college']      = $college;
            $_SESSION['username'] = $info['account'];
            $_SESSION['eid'] = $info['eid'];
            set_username($info);
            header('Location:'.FILE_SELF);
          } else {
            $message = '<div style="color:red">Records show that you are not going to be
                            at Jacobs next year, therefore you do not need a room</div>';
          }
        }
      } else {
        $message = '<div style="color:red">Login failed bro :(</div>';
      }
    }

    if( isset($_SESSION['username']) ){
      define('LOGGED_IN', true);
      define('IS_ADMIN', in_array($_SESSION['username'], $admin));
      echo '
        Logged in as <b>'.$_SESSION['username'].'</b>
        <a href="'.FILE_SELF.'?logout=true">[log out]</a>
      ';
      if (IS_ADMIN) {
        if (isset($_GET['view_as'])) {
          set_username(
            $Person_Model->get_by_account($_GET['view_as'])
          );
        }
        $select = array();
        $people = $Person_Model->get_all('eid,account', 'ORDER BY account');
        foreach ($people as $person) {
          $selected = $_SESSION['info']['account'] == $person['account'] ? 'selected="selected"' : '';
          $select[] = '<option value="'.$person['account'].'" '.$selected.' >'.$person['account'].':'.$person['eid'].'</option>';
        }
        $select = '<select id="change-user">'.implode(' ', $select).'</select>';
        echo '
        <div class="admin">
          You are admin, BOOYAAAH! <br />
          Currently you are trying out this lesser being: '.$select.'
        </div>
        <script>
          $(function () {
            $("#change-user").on("change.change-user", function change_user () {
              window.location = "'.FILE_SELF.'?view_as="+$(this).val();
            });
          });
        </script>
        ';
      }
      define('USERNAME', $_SESSION['username']);
    } else {
      define('LOGGED_IN', false);
      define('IS_ADMIN', false);
      define('USERNAME', null);
  ?>

    <form action="<?php echo FILE_SELF; ?>" method="post">
      <?php
        if( DEBUG ){
          echo '<div style="color:green">Debug mode enabled. No password required!</div>';
          echo '<div>If you do not know what username to use, try: <b>smirea, dkundel</b></div>';
        }
      ?>
      <a id="random-password" href="javascript:void(0)">Need your random password?</a>
      <input type="hidden" name="login" value="true" />
      <?php if( is_string( $message ) ){ echo $message; } ?>
      <input type="text" name="username" placeholder="username" /><input type="password" name="password" placeholder="password" /><input type="submit" value="Log In" />
    </form>
<?php
  }

  function set_username ($info) {
    $_SESSION['info'] = $info;
  }

  function random_password_login ($username, $password) {
    global $Person_Model;
    return !!$Person_Model->random_password_login($username, $password);
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
