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
          $q_taken = "SELECT * FROM ".TABLE_ALLOCATIONS." 
                                WHERE college='$c_name' AND room IS NOT NULL";
          $taken = sqlToArray( mysql_query( $q_taken ) );
          if( C('round.restrictions') )
            add_class( 'available', $allowed_rooms[$c_name], $cls );
          add_class( 'taken', extract_column( 'room', $taken ), $cls );
          add_class( 'locked', array_map( 'trim', explode(',', C("disabled.$c_name")) ), $cls );
          $cls = array_map(function($v){return implode(' ',$v);}, $cls);
          $floorPlans[$c_name] = create_floorPlan( $c_name, $c_map, $cls );
        }
      ?>
        
      <div id="menu" style="padding:5px 10px;border-bottom:1px solid #ccc;background:#fff">
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
                  $q = "UPDATE ".TABLE_ALLOCATIONS." 
                        SET room='$room_no', 
                            apartment='$apartment', 
                            round='$round' 
                        WHERE eid='$eid'";
                  if( !mysql_query($q) ){
                    echo "<div> (room=$room_no) (eid=$eid) :: ".mysql_error().'</div>';
                  }
                }
              }
              
              $q_delete = "DELETE FROM ".TABLE_APARTMENT_CHOICES."";
              mysql_query( $q_delete );
              
              $q_reset_groups = "SELECT i.group_id FROM ".TABLE_ALLOCATIONS." a, ".TABLE_IN_GROUP." i 
                                  WHERE a.eid=i.eid AND a.room IS NULL;";
              $gids = sqlToArray( mysql_query( $q_reset_groups ) );
              $gids = implode(',', extract_column( 'group_id', $gids) );
              
              $q_delete_1 = "DELETE FROM ".TABLE_IN_GROUP." WHERE group_id IN (".$gids.")";
              mysql_query( $q_delete_1 );

              $q_delete_2 = "DELETE FROM ".TABLE_GROUPS." WHERE id IN (".$gids.")";
              mysql_query( $q_delete_2 );
              
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
                'Current round number'            => 'round.number/int',
                'Max allowed roommates'           => 'roommates.max/int',
                'Min required roommates'          => 'roommates.min/int',
                'Max number of choices'           => 'apartment.choices/int',
                'Minimum points required'         => 'points.min/int',
                'Maximum points required'         => 'points.max/int',
                'Nationality cap (no of people)'  => 'allocation.nationalityCap/int',
                'Disabled rooms in Mercator'      => 'disabled.Mercator/string',
                'Disabled rooms in Krupp'         => 'disabled.Krupp/string',
                'Disabled rooms in College-III'   => 'disabled.College-III/string',
                'Disabled rooms in Nordmetall'    => 'disabled.Nordmetall/string'
              );
              $h = array();
              foreach( $fields as $label => $properties ){
                list( $key, $type ) = explode( '/', $properties );
                $value      = C($key);
                $field      = '';
                $name       = "config-$key";
                $form_attr  = 'name="'.$name.'" value="'.$value.'"';
                switch( $type ){
                  case 'int':
                    $field = '<input type="text" maxlength="2" size="1" '.$form_attr.' />';
                    break;
                  case 'bool':
                    $s = 'selected="selected"';
                    $field = '<select name='.$name.'>
                        <option value="1" '.((int)$value?$s:'').'>true</option>
                        <option value="0" '.((int)$value?'':$s).'>false</option>
                      </select>';
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