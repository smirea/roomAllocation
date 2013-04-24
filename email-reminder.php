<?php
  require_once 'config.php';
  require_once 'utils.php';
  require_once 'utils_admin.php';

  $PM = new Person_Model();
  $GM = new Group_Model();
  $AM = new Allocation_Model();
?>
<!DOCTYPE html>
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
    <script src="js/lib.js"></script>
    <style>
      .selected {
        background: lightgreen!important;
      }
      .emailable tr {
        cursor: pointer;
      }
      .emailable tr:hover {
        background: lightpink;
      }
    </style>
    <script>
      var ajax_url = 'ajax-admin.php';
      var url = window.location.protocol+'//'+window.location.host;
      var email_content = 'This is an automated email to inform you that your round for room allocation is open. <br /><br />To specify your choices, just go to <a href="'+url+'" target="_blank">'+url+'</a>, and log in.<br /><br />If you have any questions what-so-ever and/or you are looking for someone to help you through the process, feel free to contact the current members of the Housing Committee. <br /><br />Do not email me, I just made the software, not the rules :)';
      $(function () {
        $emailable = $('.emailable tr');
        $emailable.on('click.toggle', function () {
          $(this).toggleClass('selected');
        });
        $('#select-all').on('click.select-all', function () {
          $emailable.not('.selected').trigger('click.toggle');
        });
        $('#deselect-all').on('click.deselect-all', function () {
          $emailable.filter('.selected').trigger('click.toggle');
        });
        $('#email-selected').on('click.email-all', function () {
          var emails = $emailable.filter('.selected').find('a').map(function () {return $(this).attr('href').slice(7);}).get();
          $.post(ajax_url, {
            action: 'email', 
            subject: '[Room Allocation] Personal Notice: Your round is open', 
            emails: emails, 
            content: email_content
          });
        });
      });
    </script>
  </head>

  <body id="main-admin" class="content">
    <div class="wrapper">
      <button class="gh-button safe" id="select-all">Select all</button>
      <button class="gh-button danger" id="deselect-all">Deselect all</button>
      <button class="gh-button primary" id="email-selected">Send emails to selected groups</button>
      <hr />
      <?php
        $in_groups = $GM->get_all_in_groups();
        $groups = array();
        foreach ($in_groups as $person) {
          $groups[$person['group_id']][] = $person['eid'];
        }

        $min_points = C('points.min');
        $max_points = C('points.max');
        $min_size = C('roommates.min') + 1;
        $max_size = C('roommates.max') + 1;

        $people = include_freshman($PM->get_all_assoc());
        $allocations = $AM->get_all_assoc();
        $reminders = array();
        $distribution = array();
        foreach ($groups as $group) {
          if (count($group) < $min_size || count($group) > $max_size) {
            continue;
          }
          $already_allocated = false;
          foreach ($group as $eid) {
            if (isset($allocations[$eid]) && !is_null($allocations[$eid]['room'])) {
              $already_allocated = true;
              break;
            }
          }
          if ($already_allocated) {
            continue;
          }
          $points = get_points(
            array_map(function($eid)use($people){ return $people[$eid]; }, $group),
            $allocations[$group[0]]['college']
          );
          $distribution[(string)$points['total']][] = $group;
        }
        ksort($distribution);
        // $distribution = array_map(function ($a) { 
        //   return array_map(function ($b) { return implode(',', $b); }, $a);
        // }, $distribution);
        // v_export($distribution);
        foreach ($distribution as $score => $groups) {
          if ($score < $min_points || $score > $max_points) {
            continue;
          }
          $groups = array_map(function ($group) use ($people) {
            return implode(', ', array_map(function ($eid) use ($people) { 
              return '<a href="mailto:'.$people[$eid]['email'].'">'.$people[$eid]['fname'].' '.$people[$eid]['lname'].'</a>';
            }, $group));
          }, $groups);
          echo '<table class="allocation-table emailable">'.key_value_table($groups, "$score points").'</table>';
        }

        function print_group (array $group) {
          global $people;
          $result = array();
          foreach ($group as $eid) {
            $result[] = $people[$eid]['fname'].' '.$people[$eid]['lname'];
          }
          return '['.implode(', ', $result).']';
        }

      ?>
    </div>
  </body>
</html>
