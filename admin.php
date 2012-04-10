<?php
  
  require_once 'config.php';
  require_once 'utils.php';
  require_once 'floorPlan/utils.php';
  require_once 'floorPlan/Mercator.php';
  require_once 'floorPlan/Krupp.php';
  require_once 'floorPlan/College3.php';
  require_once 'floorPlan/Nordmetall.php';
  
?>
<html>
  <head>
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
    
    <div id="main-admin">
      <?php 
        require_once 'login.php'; 
        if( !IS_ADMIN ) 
          exit( "<b style=\"color:red\">You do not have permissions to access this page</b>" );
        
        $people = array();  // maps: eid      -> personal info
        $rooms  = array();  // maps: room     -> group_id
        $choic  = array();  // maps: room     -> choice
        $groups = array();  // maps: group_id -> array( eid )
        $points = array();  // maps: group_id -> points
        $total  = array();  // maps: group_id -> $points[$k]['total']
        
        $people_in_group = array(); // maps: group_id -> array( people[group[$k]] )
        
        $q = "SELECT * FROM ".TABLE_APARTMENT_CHOICES." 
                WHERE college='Mercator'
                ORDER BY college,number,group_id,choice";
        $rooms_tmp  = sqlToArray( mysql_query($q) );
        foreach( $rooms_tmp as $room ){
          $rooms[$room['number']][] = $room['group_id'];
        }
        
        $q = "SELECT i.group_id, p.* FROM ".TABLE_PEOPLE." p, ".TABLE_IN_GROUP." i
                    WHERE i.eid=p.eid ORDER BY i.group_id";
        $people_tmp = sqlToArray( mysql_query($q) );
        
        foreach( $people_tmp as $v ){
          $people[$v['eid']] = $v;
          $groups[$v['group_id']][] = $v['eid'];
          $people_in_group[$v['group_id']][] = $people[$v['eid']];
        }
        
        foreach( $groups as $group_id => $group ){
          $points[$group_id]  = get_points( $people_in_group[$group_id] );
          $total[$group_id]   = $points[$group_id]['total'];
        }
        
        // sort rooms
        foreach( $rooms as $number => $value ){
          usort( $rooms[$number], function($a, $b){ 
            global $total;
            return $total[$a] == $total[$b] ? 0 : ($total[$a] > $total[$b] ? -1 : 1); 
          });
        }
        
        $classes    = array();
        $arguments  = array();
        foreach( $rooms as $number => $gids ){
          $max = -1;
          foreach( $gids as $group_id ){
            $max = $total[$group_id] > $max ? $total[$group_id] : $max;
          }
          $max_groups = array();
          foreach( $total as $k => $v ){
            if( in_array( $k, $gids ) && $v == $max ){
              $max_groups[] = $k;
            }
          }
          
          $classes[$number]   = array_map(function($v){return "group-$v";}, $gids);
          $classes[$number][] = count($max_groups) > 1 ? 'ambiguous' : 'chosen';
          $classes[$number]   = implode(' ', $classes[$number]);
          
          $arguments[$number]['country'] = array(
            'fname'   => '', 
            'lname'   => '', 
            'country' => ''
          );
        }
        
        /** Print groups */
        foreach( $groups as $group_id => $eids ){
          $faces = array();
          $emails = array();
          foreach( $eids as $eid ){
            $faces[]  = getFaceHTML( $people[$eid] );
            $emails[] = $people[$eid]['email'];
          }
          echo '
            <div class="group" id="group-'.$group_id.'" style="display:none">
              <h4>
                Total: <b>'.$total[$group_id].'</b> 
                Country: '.$points[$group_id]['country'].'
                World:'.$points[$group_id]['world'].'
              </h4>
              '.implode("\n", $faces).'
              <div class="actions" style="padding:3px;border-top:1px solid #999;background:#fff;text-align:center;display:none;">
                <div class="gh-button-group">
                  <a href="javascript:void(0)" class="gh-button pill primary icon user">View chosen rooms</a>
                  <a href="mailto:'.implode(',', $emails).'" class="gh-button pill icon mail">send email</a>
                </div>
              </div>
            </div>
          ';
        }
        
        $table = array();
        $i = 0;
        foreach( $rooms as $number => $gr ){
          $h = array();
          $h[] = '<tr class="'.($i%2==0? 'even' : 'odd').'">';
          $h[] = '<td style="width:50px;text-align:center;font-weight:bold;">'.$number.'</td>';
          $h[] = '<td>';
          foreach( $gr as $g_id ){
            $h[] = '<span class="small-group">';
            $h[] = '<span class="total">'.$total[$g_id].'</span>';
            foreach( $groups[$g_id] as $p_id ){
              $person = $people[$p_id];
              $d              = 3-((2000+(int)$person['year'])-(int)date("Y"));
              $year_of_study  = $d."<sup>".($d==1?'st':($d==2?'nd':($d==3?'rd':'th')))."</sup>";
              $h[] = '<span class="person">
                        <img height="18" class="county" src="'.flagURL( $person['country'] ).'" />
                        <span class="year">'.$year_of_study.'</span>
                        <span class="name">'.$person['fname'].', '.$person['lname'].'</span>
                      </span>';
            }
            $h[] = '</span>';
          }
          $h[] = '</td>';
          $h[] = '</tr>';
          $table[$i] = implode("\n", $h);
          ++$i;
        }
        
        echo ' [ <a href="javascript:void(0)" onclick="$(\'#allocation-table\').toggle();$(\'#floorPlan\').toggle();">Toggle View</a> ]';
        
        echo '
          <table cellspacing="0" cellpadding="0" id="allocation-table" style="display:none;">
            '.implode("\n", $table).'
          </table>
        ';
        
        echo '<div id="floorPlan">'.
                renderMap( $Mercator, $classes, $arguments ).
              '</div>';
        
      ?>
    </div>
    
  </body>
</html>