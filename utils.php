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
  require_once 'WorldRegions.php';
  require_once 'models/Group_Model.php';
  require_once 'models/Allocation_Model.php';

  $Group_Model = new Group_Model();
  $Allocation_Model = new Allocation_Model();

  /**
   * @brief Applies input sanitizing functions for every value in the array, recursively
   * @param {array} &$arr
   * @returns {array}
   */
  function recursive_escape( array &$arr ){
    foreach( $arr as $k => $v ){
      if( is_array( $v ) ){
        recursive_escape($arr[$k]);
      } else {
        $arr[$k] = addslashes( $v );
      }
    }
    return $arr;
  }

  /**
   * Get all roommates of a person from a group
   * @param {string} $eid
   * @param {string} $group_id
   */
  function get_roommates( $eid, $group_id ){
    $q = "SELECT p.* FROM ".TABLE_PEOPLE." p, ".TABLE_IN_GROUP." i
            WHERE i.group_id='$group_id'
            AND p.eid=i.eid AND i.eid<>'$eid' ";
    return sqlToArray( mysql_query( $q ) );
  }

  /**
   * @brief Extracts only one field from a 2-d array.
   *          Typically used when you want to get one column from sqlToArray
   * @param {string} $column
   * @param {array} $array
   * @returns {array}
   */
  function extract_column( $column, array $array ){
    foreach( $array as $key => $row ){
      $array[$key] = $row[$column];
    }
    return $array;
  }

  /**
   * @brief Appends a value to multiple keys in a 2-d array
   * @param {mixed} $class
   * @param {array} $keys
   * @param {array} &$target
   */
  function add_class( $class, array $keys, array &$target ){
    foreach( $keys as $room ){
      if( !isset( $target[$room] ) ){
        $target[$room] = array();
      }
      $target[$room][] = $class;
    }
    return $target;
  }


  function add_to_group ($eid, $group_id = null) {
    global $Group_Model;
    $group_id = $Group_Model->add_to_group($eid, $group_id);
    $q_people = "SELECT p.* FROM ".TABLE_IN_GROUP." i, ".TABLE_PEOPLE." p
                  WHERE group_id='$group_id' AND i.eid=p.eid";
    $people = Model::to_array( mysql_query( $q_people ) );
    $points = get_points( $people );
    $Group_Model->set_group_score($group_id, $points['total']);
    return $group_id;
  }

  function group_info( $eid ){
    $q = "SELECT
            i.group_id,
            (SELECT COUNT(id) FROM ".TABLE_IN_GROUP." j where j.group_id=i.group_id) AS members
          FROM ".TABLE_IN_GROUP." i
          WHERE i.eid='$eid';";
    return mysql_fetch_assoc( mysql_query( $q ) );
  }

  function get_points( array $people, $college = null ){
    global $WorldRegions;
    global $WorldRegions_Inv;
    global $Allocation_Model;
    $year               = ((int)date('Y')) % 100;
    $countries          = array();
    $majors             = array();
    $individual_points  = 0;
    $individual         = array();
    foreach( $people as $v ){
      if ($college === null) {
        $alloc = $Allocation_Model->get_allocation($v['eid']);
        $college = $alloc['college'];
      }
      if( $v['eid'] != FRESHMAN_EID ){
        if( $v['status'] == 'undergrad' )
          $p = min(2, max(1, 3-($v['year']-$year) ) );
        else
          $p = 1;
        $countries[$v['country']] = true;
        $majors[$v['major']] = true;
        $p += ($college == $v['college']) ? 0.5 : 0;
        $individual[] = $p;
        $individual_points += $p;
      } else {
        $individual[] = 0;
      }
    }
    $country_points = count($countries) > 1 ? count($countries) : 0;
    $major_points   = count($majors) > 1 ? count($majors)*0.25 : 0;
    $world_regions  = array_map(function($v){global $WorldRegions_Inv; return $WorldRegions_Inv[$v];}, array_keys($countries));
    $world_regions  = array_unique( $world_regions );
    $world_regions  = count( $world_regions ) * 0.5;
    $world_regions  = $world_regions > 0.5 ? $world_regions : 0;
    $total          = $individual_points + $country_points + $major_points + $world_regions;
    return array(
      'people'      => $individual,
      'individual'  => $individual_points,
      'country'     => $country_points,
      'major'       => $major_points,
      'world'       => $world_regions,
      'total'       => $total
    );
  }

  function print_score( array $people, $points = null ){
    $points = $points ? $points : get_points( $people );
    $h = '<table class="points" cellspacing="0" cellpadding="0">';
    $h .= '<tr><td colspan="2" class="section">Individual points</td></tr>';
    foreach( $points['people'] as $k => $value ){
      $h .= "<tr>
              <td>".$people[$k]['fname'].", ".$people[$k]['lname']."</td>
              <td class=\"value\">".$value."</td>
             </tr>";
    }
    $h .= '<tr><td colspan="2" class="section">Bonus points</td></tr>';
    $h .= '<tr><td>Nationalities</td><td class="value">'.$points['country'].'</td></tr>';
    $h .= '<tr><td>World Regions</td><td class="value">'.$points['world'].'</td></tr>';
    $h .= '<tr><td>Majors</td><td class="value">'.$points['major'].'</td></tr>';
    $h .= '<tr><td class="section">Total</td><td class="value">'.$points['total'].'</td></tr>';
    $h .= '</table>';
    return $h;
  }

  function getFaceHTML( $info, $append = '' ){
    foreach( $info as $k => $v ){ $$k = $v; }
      $img            = imageUrl( $eid );
      $country_flag   = flagURL( $country );
      $d              = 3-((2000+(int)$year)-(int)date("Y"));
      $year_of_study  = $d."<sup>".($d==1?'st':($d==2?'nd':($d==3?'rd':'th')))."</sup>";
      $email          = $info['email'];
      $short_email    = substr($email, 0, strrpos($email, '-'));
      return <<<HTML
        <table class="face" cellspacing="0" cellpadding="0" id="face-eid-$eid">
          <tr>
            <td rowspan="4" class="photo"><img src="$img" height="64" /></td>
            <td class="name"><b>$fname, $lname</b></td>
            <td rowspan="4" class="country-photo">
              <img height="64" alt="$country" src="$country_flag">
            </td>
          </tr>
          <tr>
            <td class="year">class of 20$year ($year_of_study year)</td>
          </tr>
          <tr>
            <td class="country">$country</td>
          </tr>
          <tr>
            <td class="email"><a href="mailto:$email">$short_email</a></td>
          </tr>
          $append
        </table>
HTML;
  }

  function getFaceHTML_received( $info, $append = '' ){
    $actions = '
      <tr class="actions">
        <td colspan="3" style="padding:3px;border-top:1px solid #999;background:#fff;text-align:center">
          <div class="gh-button-group">
            <a href="javascript:void(0)" onclick="sendResponse(\'requestReceived\',\''.$info['eid'].'\',\'yes\')" class="gh-button pill primary safe icon approve">accept</a>
            <a href="mailto:'.$info['email'].'" class="gh-button pill icon mail">send email</a>
            <a href="javascript:void(0)"  onclick="sendResponse(\'requestReceived\',\''.$info['eid'].'\',\'no\')" class="gh-button pill danger icon remove">reject</a>
          </div>
        </td>
      </tr>
    ';
    return getFaceHTML( $info, $actions.$append );
  }

  function getFaceHTML_sent( $info, $append = '' ){
    $actions = '
      <tr class="actions">
        <td colspan="3" style="padding:3px;border-top:1px solid #999;background:#fff;text-align:center">
          <div class="gh-button-group">
            <a href="mailto:'.$info['email'].'" class="gh-button pill icon mail">send email</a>
            <a href="javascript:void(0)" onclick="sendResponse(\'requestSent\',\''.$info['eid'].'\',\'no\')" class="gh-button pill danger icon remove">cancel request</a>
          </div>
        </td>
      </tr>
    ';
    return getFaceHTML( $info, $append.$actions );
  }

  /**
   * Send a HTML email
   * @param  String $to      
   * @param  String $subject 
   * @param  String $message 
   * @return String          
   */
  function send_mail ($to, $subject, $from = 'code4fun@gmail.com', $message = 'Empty <b>HTML</b> mail') {
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    $headers .= "From: $from" . "\r\n";
    $bcc = explode(',', $to);
    for($i=1; $i<count($bcc); ++$i) {
      $headers .= "BCC: ".$bcc[$i]." \r\n";
    }
    
    return mail($bcc[0], $subject, $message, $headers) ? true : false;
  }

?>