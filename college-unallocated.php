<?php

  require_once 'config.php';
  require_once 'utils.php';
  require_once 'models/Person_Model.php';
  require_once 'models/College_Choice_Model.php';
  require_once 'models/Allocation_Model.php';

  $Person_Model = new Person_Model();
  $College_Choice_Model = new College_Choice_Model();
  $Allocation_Model = new Allocation_Model();
  
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
    <style>
      #reminder-table > tr:hover, #reminder-table > tbody > tr:hover {
        background: lightgreen;
      }
    </style>
    <script type="text/javascript">
      $(function () {
        var view_state = false;
        $('.action-notify').on('click.send-reminder', function (event) {
          event.preventDefault();
          $.get($(this).attr('href'), function (response) {
            console.log(response);
          });
        });
        $('.action-remove').on('click.remove', function () {
          $(this).parent().parent().remove();
        });
        $('#toggle-view').on('click.toggle-controls', function () {
          if (view_state) {
            $('#reminder-table .face').show();
            $('#reminder-table .email > a').hide();
          } else {
            $('#reminder-table .face').hide();
            $('#reminder-table .email > a').show();
          }
          view_state = !view_state;
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
    <div id="main" style="width: 800px;">
      <?php require_once 'login.php'; ?>
      <div id="wrapper" style="padding: 10px;">
        <?php
          if (!IS_ADMIN) {
            echo '<div class="error">You do not have the power of the Gods!</div>';
          } else {
            $choices = $College_Choice_Model->get_all_choices();
            $already_allocated = $Allocation_Model->get_all('*', 'WHERE college IS NOT NULL');
            $eids_voted = array();
            foreach ($choices as $person) {
              $eids_voted[$person['eid']] = true;
            }
            foreach ($already_allocated as $allocation) {
              $eids_voted[$allocation['eid']] = true;
            }
            $remaining = $Person_Model->get_all('*', "WHERE year>13 AND status='undergrad'");
            foreach ($remaining as $key => $person) {
              if (isset($eids_voted[$person['eid']])) {
                unset($remaining[$key]);
              }
            }
            echo '
              <div>
                <a href="javascript:void(0)" id="toggle-view">Toggle Advanced View</a> |
                <b><a href="javascript:void(0)" id="notify-all">Notify all</a></b>
              </div>
              <hr />
            ';
            echo '<table id="reminder-table">';
            echo '<tr><th>#</th><th>account</th><th>info</th><th>email</th><th>actions</th></tr>';
            $count = 0;
            foreach ($remaining as $person) {
              echo '
                <tr>
                  <td>'.(++$count).'</td>
                  <td>'.$person['account'].'</td>
                  <td>'.$person['status'].' '.$person['year'].'</td>
                  <td class="email">
                    <a href="mailto:'.$person['email'].'" style="display:none">'.$person['email'].'</a>
                    '.getFaceHTML($person).'
                  </td>
                  <td>
                    <a class="action-notify" href="ajax.php?action=remind&eids[]='.$person['eid'].'" reminder-eid="'.$person['eid'].'">send reminder</a> |
                    <a href="javascript:void(0)" class="action-remove">remove from list</a>
                  </td>
                </tr>';
            }
            echo '</table>';
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