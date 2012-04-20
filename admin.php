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
          add_class( 'available', $allowed_rooms[$c_name], $cls );
          $cls = array_map(function($v){return implode(' ',$v);}, $cls);
          $floorPlans[$c_name] = create_floorPlan( $c_name, $c_map, $cls );
        }
        
      ?>
        
      <div id="menu" style="padding:5px 10px;border-bottom:1px solid #ccc;background:#fff">
        <a href="javascript:void(0)" onclick="setView(this, $('.college-floorPlan'))">Floor Plan</a> |
        <a href="javascript:void(0)" onclick="setView(this, $('.display-floorPlan'))">Choice List</a> |
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
              foreach( $floorPlans as $college_name => $alloc_info ){
                $count = array_combine( 
                  array_keys($alloc_info['groups']),
                  array_fill(0, count($alloc_info['groups']), 0)
                );
                $a = array();
                $g = array();
                foreach( $alloc_info['allocations'] as $room_no => $group_no ){
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
                $c          = $floorPlans[$college];
                
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
              //mysql_query( $q_delete );
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
                'Is round open'           => 'round.active/bool',
                'Current round number'    => 'round.number/int',
                'Max allowed roommates'   => 'roommates.max/int',
                'Min required roommates'  => 'roommates.min/int',
                'Max number of choices'   => 'apartment.choices/int',
                'Minimum points required' => 'points.min/int',
                'Maximum points required' => 'points.max/int'
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
                    <td colspan="2" style="text-align:right">
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
          <input onclick="return confirm('Warning: this cannot be undone!');" type="submit" value="Make allocations permanent" />
        </form>
      <?php 
      } else {
        echo '<div class="view display-final wrapper"><b style="color:red">*Note:</b> You need to close the round in order to make the allocations permanent</div>';
      }
      ?>
      
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