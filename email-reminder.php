<?php
  require_once 'config.php';
  require_once 'utils.php';
  require_once 'utils_admin.php';

  $PM = new Person_Model();
  $GM = new Group_Model();
  $AM = new Allocation_Model();
?>
<!DOCTYPE html>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php
  $in_groups = $GM->get_all_in_groups();
  $groups = array();
  foreach ($in_groups as $person) {
    $groups[$person['group_id']][] = $person['eid'];
  }

  $min_points = 8;
  $max_points = 8.5;
  $min_size = 2;
  $max_size = 2;

  $people = include_freshman($PM->get_all_assoc());
  $allocations = $AM->get_all_assoc();
  $reminders = array();
  $distribution = array();
  echo '<ol>';
  foreach ($groups as $group) {
    if (count($group) < $min_size || count($group) > $max_size) {
      continue;
    }
    $points = get_points(
      array_map(function($eid)use($people){ return $people[$eid]; }, $group)
    );
    $distribution[(string)$points['total']][] = implode(',', $group);
    if ($points['total'] < $min_points || $points['total'] > $max_points) {
      continue;
    }
    echo '<li>'.print_group($group).' -> '.$points['total'].'</li>';
  }
  echo '</ol>';
  ksort($distribution);
  v_export($distribution);

  function print_group (array $group) {
    global $people;
    $result = array();
    foreach ($group as $eid) {
      $result[] = $people[$eid]['fname'].' '.$people[$eid]['lname'];
    }
    return '['.implode(', ', $result).']';
  }

?>