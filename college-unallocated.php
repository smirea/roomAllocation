<?php

  require_once 'config.php';
  require_once 'utils.php';
  require_once 'models/Person_Model.php';
  require_once 'models/College_Choice_Model.php';

  $Person_Model = new Person_Model();
  $College_Choice_Model = new College_Choice_Model();
  
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
    <script src="js/lib.js"></script>
    <script type="text/javascript">
      $(function () {
        $('#reminder-table a').on('click.send-reminder', function (event) {
          event.preventDefault();
          $.get($(this).attr('href'), function (response) {
            console.log(response);
          });
        });
        $('#notify-all').on('click.send-reminder', function () {
          var eids = $('.action-notify').map(function () { return $(this).attr('reminder-eid'); }).get();
          $.get('ajax.php', {
            action: 'remind',
            eids: eids,
          }, function (response) {
            console.log(response);
          });
        });
      });
    </script>
  </head>

  <body>
    <div id="main">
      <?php require_once 'login.php'; ?>
      <div id="wrapper">
        <?php
          if (!IS_ADMIN) {
            echo '<div class="error">You do not have the power of the Gods!</div>';
          } else {
            $choices = $College_Choice_Model->get_all_choices();
            $eids_voted = array();
            foreach ($choices as $person) {
              $eids_voted[$person['eid']] = true;
            }
            // var_export($eids_voted);
            $remaining = $Person_Model->get_all('*', "WHERE year>13 AND status='undergrad'");
            foreach ($remaining as $key => $person) {
              if (isset($eids_voted[$person['eid']])) {
                unset($remaining[$key]);
              }
            }
            echo '<div><a href="javascript:void(0)" id="notify-all">Notify all</a></div>';
            echo '<table id="reminder-table">';
            echo '<tr><th>#</th><th>account</th><th>email</th><th>actions</th></tr>';
            $count = 0;
            foreach ($remaining as $person) {
              echo '
                <tr>
                  <td>'.(++$count).'</td>
                  <td>'.$person['account'].'</td>
                  <td><a href="mailto:'.$person['email'].'">'.$person['email'].'</a></td>
                  <td><a class="action-notify" href="ajax.php?action=remind&eids[]='.$person['eid'].'" reminder-eid="'.$person['eid'].'">send reminder</a></td>
                </tr>';
            }
            echo '</table>';
          }
        ?>
      </div>
    </div>

  </body>
</html>