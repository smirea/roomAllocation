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
  require_once 'utils_admin.php';

  require_once 'models/Person_Model.php';
  require_once 'models/Allocation_Model.php';
  require_once 'models/Group_Model.php';
  require_once 'models/College_Choice_Model.php';

  require_once 'floorPlan/utils.php';
  require_once 'floorPlan/Mercator.php';
  require_once 'floorPlan/Krupp.php';
  require_once 'floorPlan/College3.php';
  require_once 'floorPlan/Nordmetall.php';

  $Person_Model = new Person_Model();
  $College_Choice_Model = new College_Choice_Model();
  $Allocation_Model = new Allocation_Model();
  $Group_Model = new Group_Model();
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
    <style>
      .college-allocation {
        float: left;
        border: 1px solid #ccc;
        background: #fff;
        margin: 5px;
      }
      .college-allocation h3 {
        background: lightgreen;
        margin: 0!important;
        padding: 5px;
        font-size: 1.2em;
        text-align: center;
      }
      .college-allocation li:hover {
        background: lightblue;
        cursor: pointer;
      }
      .college-allocation li,
      .college-allocation li.ui-state-highlight {
        height: auto;
        padding: 2px 0;
      }
      .Mercator { background:#97BFFF!important; }
      .Krupp { background: #FFA9A9!important; }
      .College-III { background: lightgreen!important; } 
      .Nordmetall { background: yellow!important; }
    </style>
    <script>
      var ajax_url = 'ajax-admin.php';
      $(function () {
        $('.college-allocation').
          disableSelection().
          find('ol').
          sortable({
            placeholder: 'ui-state-highlight',
            connectWith: '.college-allocation ol',
            receive: function (event, ui) {
              ui.item.
                removeClass('Mercator Krupp College-III Nordmetall').
                addClass(ui.sender.attr('data-college'));
              $.post(ajax_url, {
                action: 'allocate',
                eid: ui.item.attr('data-eid'),
                college: ui.item.parent().attr('data-college')
              });
            }
          });
    });
    </script>
  </head>

  <body>

    <div id="main-admin" class="content">
      <?php
        require_once 'login.php';

        if( !IS_ADMIN )
          exit( "<b style=\"color:red\">You do not have permissions to access this page</b>" );

        $colleges = array(
          'Mercator'    => $Mercator,
          'Krupp'       => $Krupp,
          'College-III' => $College3,
          'Nordmetall'  => $Nordmetall
        );

        $floorPlans = array();
        foreach( $colleges as $c_name => $c_map ){
          $cls = array();
          $taken = $Allocation_Model->get_all_rooms_from_college($c_name);
          if( C('round.restrictions') )
            add_class( 'available', $allowed_rooms[$c_name], $cls );
          add_class( 'taken', extract_column( 'room', $taken ), $cls );
          add_class( 'locked', array_map( 'trim', explode(',', C("disabled.$c_name")) ), $cls );
          $cls = array_map(function($v){return implode(' ',$v);}, $cls);
          $floorPlans[$c_name] = create_floorPlan( $c_name, $c_map, $cls );
        }
      ?>

      <div id="menu" style="padding:5px 10px;border-bottom:1px solid #ccc;background:#fff">
        <a href="javascript:void(0)" onclick="setView(this, $('#display-college-phase'))">College Phase</a> |
        <a href="javascript:void(0)" onclick="setView(this, $('.college-floorPlan'))">Floor Plan</a> |
        <a href="javascript:void(0)" onclick="setView(this, $('.display-floorPlan'))">Choice List</a> |
        <a href="javascript:void(0)" onclick="setView(this, $('.user-choices'))">User Choices</a> |
        <a href="javascript:void(0)" onclick="setView(this, $('.display-final'))">Final Result</a> |
        <a href="javascript:void(0)" onclick="setView(this, $('#admin-config'))" style="color:red!important;">Config</a>
      </div>

      <?php
        if( $_SERVER['REQUEST_METHOD'] == 'POST' && isset( $_POST['action'] ) ){
          switch( $_POST['action'] ){
            case 'config':
              $prefix = 'config-';
              foreach( $_POST as $key => $value ){
                if( substr( $key, 0, strlen($prefix) ) == $prefix ){
                  $name = substr( $key, strlen($prefix) );
                  $name = str_replace( '_', '.', $name );
                  if( is_numeric( $value ) ) $value = (int)$value;
                  C( $name, $value );
                }
              }
              break;
            case 'set-final':
              $alloc_rooms  = array();

              $keyword  = 'college-';
              $arr      = array();
              foreach( $_POST as $k => $v ){
                if( substr( $k, 0, strlen($keyword) ) == $keyword ){
                  $college = substr( $k, strlen($keyword) );
                  $arr[$college] = explode(';', $v);
                }
              }

              foreach( $arr as $college_name => $data ){
                $alloc_info = $floorPlans[$college_name];
                $count = array_combine(
                  array_keys($alloc_info['groups']),
                  array_fill(0, count($alloc_info['groups']), 0)
                );
                $a = array();
                $g = array();
                foreach( $data as $tmp ){
                  if(!$tmp) continue;
                  $tmp2 = explode(':',$tmp);
                  if( count($tmp2) != 2 ){
                    echo '<div style="color:red">Invalid: '.$tmp.'</div>';
                    continue;
                  }
                  list( $room_no,$group_no ) = $tmp2;
                  $a[$room_no] = array(
                    $group_no,
                    $alloc_info['groups'][$group_no][$count[$group_no]]
                  );
                  ++$count[$group_no];
                }
                $alloc_rooms[$college_name]   = $a;
              }

              $round = C('round.number');
              foreach( $alloc_rooms as $college => $rooms ){
                $c = $floorPlans[$college];

                $apartments = array();
                foreach( $c['allocations'] as $room_no => $group_no ){
                  $apartments[$group_no][] = $room_no;
                }
                $apartments = array_map(function($v){return implode(',',$v);}, $apartments);
                foreach( $rooms as $room_no => $r_info ){
                  list( $group_no, $eid ) = $r_info;
                  $apartment  = $apartments[$group_no];
                  $update_allocation = $Allocation_Model->update_allocation(
                                          $eid,
                                          "room='$room_no', apartment='$apartment', round='$round'"
                  );
                  if( !$update_allocation ){
                    echo "<div> (room=$room_no) (eid=$eid) :: ".mysql_error().'</div>';
                  }
                }
              }

              $q_delete = "DELETE FROM ".TABLE_APARTMENT_CHOICES."";
              mysql_query( $q_delete );

              $q_reset_groups = "SELECT i.group_id FROM ".TABLE_ALLOCATIONS." a, ".TABLE_IN_GROUP." i
                                  WHERE a.eid=i.eid AND a.room IS NULL;";
              $gids = sqlToArray( mysql_query( $q_reset_groups ) );
              $gids = extract_column( 'group_id', $gids);
              $Group_Model->delete_groups($gids, $gids);

              try{
                foreach( array_keys($arr) as $college ){
                  $name = "$round-$college.html";
                  $log  = $_POST["allocation-log-$college"];
                  $log  = stripslashes(htmlspecialchars_decode( $log ));
                  $log  = <<<HTML
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

    <link rel="stylesheet" type="text/css" href="../css/html5cssReset.css" />
    <link rel="stylesheet" type="text/css" href="../css/floorPlan.css" />
    <link rel="stylesheet" type="text/css" href="../css/roomAllocation.css" />
    <link rel="stylesheet" type="text/css" href="../css/admin.css" />
    <script src="../js/admin.js" type="text/javascript"></script>
  </head>

  <body>
    <div id="main" style="width:auto;margin:10px">
      <h3>Allocation log for $college in round $round</h3>
      $log
    </div>
  </body>
</html>
HTML;
                  file_put_contents( "log/$name", $log );
                }
              } catch( Exception $e ){
                echo "<div>Unable to write log for $college: ".$e->getMessage()."</div>";
              }
              break;
            default:
              echo '<div>No action took</div>';
          }
        }
      ?>

      <div class="view" id="admin-config">
        <div class="wrapper">
          <h3>General configuration</h3>
          <form action="admin.php" method="post" class="wrapper">
            <input type="hidden" name="action" value="config" />
            <?php
              $fields = array(
                'Is round open'                   => 'round.active/bool',
                'Restrict allocations'            => 'round.restrictions/bool',
                'Current round type'              => 'round.type/select/college,roommate,apartment',
                'Current round number'            => 'round.number/int',
                'Max allowed roommates'           => 'roommates.max/int',
                'Min required roommates'          => 'roommates.min/int',
                'Allow a freshman roommate'       => 'roommates.freshman/bool',
                'Max number of choices'           => 'apartment.choices/int',
                'Minimum points required'         => 'points.min/int',
                'Maximum points allowed'          => 'points.max/int',
                'Total point cap'                 => 'points.cap/int',
                'Nationality cap (no of people)'  => 'allocation.nationalityCap/int',
                'Disabled rooms in Mercator'      => 'disabled.Mercator/string',
                'Disabled rooms in Krupp'         => 'disabled.Krupp/string',
                'Disabled rooms in College-III'   => 'disabled.College-III/string',
                'Disabled rooms in Nordmetall'    => 'disabled.Nordmetall/string'
              );
              $h = array();
              foreach( $fields as $label => $properties ){
                @list( $key, $type, $extra ) = explode( '/', $properties );
                if ($type == 'bool') {
                  $type = 'select';
                  $extra = '1:true,0:false';
                }
                $value      = C($key);
                $field      = '';
                $name       = "config-$key";
                $form_attr  = 'name="'.$name.'" value="'.$value.'"';
                switch( $type ){
                  case 'int':
                    $field = '<input type="text" maxlength="2" size="1" '.$form_attr.' />';
                    break;
                  case 'select':
                    $arr = explode(',', $extra);
                    $field = '<select name="'.$name.'">';
                    $s = 'selected="selected"';
                    foreach ($arr as $option_value) {
                      @list($val, $text) = explode(':', $option_value);
                      $text = isset($text) ? $text : $val;
                      $field .= '<option value="'.$val.'" '.($val == $value ? $s : '').'>'.$text.'</option>';
                    }
                    $field .= '</select>';
                  break;
                  default:
                  case 'string':
                    $field = '<input type="text" '.$form_attr.' />';
                    break;
                }
                $h[] = "<tr><td>$label</td><td style=\"text-align:right\">$field</td></tr>";
              }
              echo '
                <table>
                  '.implode("\n",$h).'
                  <tr>
                    <td colspan="2" style="text-align:left">
                      <input type="submit" value="Update" />
                    </td>
                  </tr>
                </table>';
            ?>
          </form>
        </div>
      </div>

      <?php if( !C('round.active') ){ ?>
        <form class="view display-final wrapper" action="admin.php" method="post">
          <input type="hidden" name="action" value="set-final" />
          <?php
            foreach( $floorPlans as $college => $data ){
              $arr = array();
              foreach( $data['allocations'] as $room_id => $group_id ){
                $arr[] = "$room_id:$group_id";
              }
              echo '<input type="hidden" name="college-'.$college.'" value="'.implode(';',$arr).'" size="200"/>';
              $log = htmlspecialchars($data['log']);
              echo '<textarea style="display:none" name="allocation-log-'.$college.'">'.$log.'</textarea>';
            }
          ?>
          <input onclick="return confirm('Warning: this cannot be undone!');" type="submit" value="Make allocations permanent" />
        </form>
      <?php
      } else {
        echo '<div class="view display-final wrapper"><b style="color:red">*Note:</b> You need to close the round in order to make the allocations permanent</div>';
      }
      ?>
      <div class="wrapper">
        Download final result as csv:
        <?php
          foreach( array_keys( $colleges ) as $college ){
            echo '<a target="_blank" href="download_output.php?college='.$college.'">'.$college.'</a> ';
          }
        ?>
      </div>
      <div class="wrapper view" id="display-college-phase">
        <?php

          function print_college_allocations ($college_allocations, $people) {
            $output = '';
            $output .= '<div class="clearfix">';
            foreach ($college_allocations as $college_name => $allocations) {
              $output .= '<div class="college-allocation clearfix">';
              $output .= '<h3>'.$college_name.'</h3>';
              $output .= '<ol data-college="'.$college_name.'">';
              foreach ($allocations as $eid) {
                  $output .= '<li data-eid="'.$eid.'">'.utf8_encode($people[$eid]['fname']).' '.utf8_encode($people[$eid]['lname']).'</li>';
              }
              $output .= '</ol>';
              $output .= '</div>';
            }
            $output .= '</div>';
            return $output;
          }

          $raw_people = $Person_Model->get_all();
          $people = array();
          foreach ($raw_people as $person) {
            $person['photo_url'] = imageURL($person['eid']);
            $people[$person['eid']] = $person;
          }
          if (C('round.type') == 'college') {
            $raw_college_choices = $College_Choice_Model->get_all_choices();
            $college_choices = array();
            foreach ($raw_college_choices as $choice) {
              $college_choices[$choice['eid']] = $choice;
            }
            $limits = array();
            foreach ($colleges as $college => $whatever) {
              $limits[$college] = intval(C('college.limit.'.$college) * C('college.limit.threshold'), 10);
            }
            $college_allocations = college_allocation($college_choices, $people, $limits);
            if (!$college_allocations) {
              echo '<div style="color:red">Error allocating putas</div>';
            } else {
              echo '<input type="button" value="Make college allocations permanent" id="make-college-choices-permanent" />';
              echo print_college_allocations($college_allocations['allocated'], $people);
              echo '
                <script>
                  var allocations = '.json_encode($college_allocations['allocated']).';
                  $("#make-college-choices-permanent").on("click.allocate", function allocate () {
                    $.post(ajax_url, {
                      action: "set-college-allocations",
                      allocations: allocations
                    });
                  });
                </script>
              ';
            }
          } else {
            $raw_college_choices = $Allocation_Model->get_all('*', 'WHERE college IS NOT NULL');
            $college_choices = array();
            foreach ($raw_college_choices as $choice) {
              $college_choices[$choice['college']][] = $choice['eid'];
            }
            echo print_college_allocations($college_choices, $people);
          }
        ?>
      </div>
      <?php
        echo '
          <div class="wrapper">
            <h3>Mercator College</h3>
            '.$floorPlans['Mercator']['html'].'
          </div>';

        echo '
          <div class="wrapper">
            <h3>Krupp College</h3>
            '.$floorPlans['Krupp']['html'].'
          </div>';

        echo '
          <div class="wrapper">
            <h3>College-III</h3>
            '.$floorPlans['College-III']['html'].'
          </div>';

        echo '
          <div class="wrapper">
            <h3>Nordmetall</h3>
            '.$floorPlans['Nordmetall']['html'].'
          </div>';
      ?>

      <div id="footer" class="message info">
        <span style="float:left">(C) 2012 code4fun.de</span>
        Designed and developed by
        <a title="contact me if anything..." href="mailto:s.mirea@jacobs-university.de">Stefan Mirea</a>
      </div>

    </div>

  </body>
</html>