<?php

  require_once 'config.php';
  require_once 'utils.php';
  require_once 'models/Person_Model.php';

  $Person_Model = new Person_Model();

  function random_smily () {
    $smilies = explode(' ', "^.^ :D :P :) :)) ';..;' :-) ;-) :-X 8-) }:-X >:) (-_-) :-[ (*_*) (X_X) d[-_-]b (O_O) (/.__.) (.__.\)");
    return $smilies[rand(0, count($smilies)-1)];
  }

  function generate_random_string ($length = 16) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
  }
?>

<!DOCTYPE html>
<html>
  <head>
    <link rel="stylesheet" type="text/css" href="css/html5cssReset.css" />
    <link rel="stylesheet" type="text/css" href="css/jquery-ui/jquery-ui.css" />
    <link rel="stylesheet" type="text/css" href="css/messages.css" />
    <link rel="stylesheet" type="text/css" href="css/roomAllocation.css" />
    <script src="js/jquery.js"></script>
    <script src="js/jquery-ui.js"></script>
  </head>

  <body>
    <div id="main">
      <?php require_once 'login.php'; ?>
      <div id="wrapper">
        <?php
          if (!IS_ADMIN) {
            echo '<div class="error">You do not have the power of the Gods!</div>';
          } else {
            echo '<div class="info message" style="display:block"><b>Note:</b> This will only add random password to the accounts that do not have one already. If you wish to change a random password, clear the random_password field in the database for the person and re-run this script</div>';
            $people = $Person_Model->get_all();
            echo '<div class="content" style="padding:10px">';
            echo '<table>';
            foreach ($people as $person) {
              $message = '';
              $password = $person['random_password'];
              if (!$person['random_password'] || strlen($person['random_password']) == 0) {
                $password = generate_random_string();
                $password = substr($password, 0, strlen($password)/2).'|'.random_smily().'|'.substr($password, strlen($password)/2);
                $message = 'style="background:lightgreen"';
                if (!$Person_Model->set_password($person['eid'], mysql_real_escape_string($password))) {
                  $message = 'style="background:orange;"';
                }
              }
              echo '<tr '.$message.'><td>'.$person['account'].'</td><td>'.$password.'</td><td>'.mysql_error().'</td></tr>';
            }
            echo '</table>';
            echo '</div>';
          }
        ?>
      </div>
    </div>

  </body>
</html>