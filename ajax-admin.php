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
  require_once 'class.Search.php';
  require_once 'utils.php';
  require_once 'utils_admin.php';

  require_once 'models/Allocation_Model.php';
  require_once 'models/Group_Model.php';
  require_once 'models/Request_Model.php';
  require_once 'models/Person_Model.php';
  require_once 'models/Apartment_Choice_Model.php';
  require_once 'models/College_Choice_Model.php';

  recursive_escape( $_REQUEST );

  $Allocation_Model = new Allocation_Model();
  $Groups_Model = new Group_Model();
  $Request_Model = new Request_Model();
  $Person_Model = new Person_Model();
  $Apartment_Choice_Model = new Apartment_Choice_Model();
  $College_Choice_Model = new College_Choice_Model();

  e_assert( isset($_REQUEST['action']) && strlen($_REQUEST['action']) >= 2, 'No action set' );


  /**
   * BEGIN Logged-in Actions
   */

  // make sure user is logged in before accessing this file
  e_assert_isset($_SESSION, 'eid,username,info');
  e_assert($_SESSION['admin'], 'You do not possess the power of the Gods!');

  $output = array(
    'result'  => true,
    'rpc'     => null,
    'error'   => array(),
    'warning' => array(),
    'info'    => array(),
    'success' => array()
  );

  $colleges = explode(' ', 'Mercator Krupp College-III Nordmetall');

  switch ($_REQUEST['action']) {
    case 'email':
      e_assert_isset($_REQUEST, 'emails,subject,content');
      e_assert(is_array($_REQUEST['emails']), 'Must provide an array of emails');
      e_assert(!empty($_REQUEST['content']), 'content is empty');
      e_assert(!empty($_REQUEST['subject']), 'subject is empty');
      $output['result'] = send_mail(
        implode(',', $_REQUEST['emails']), 
        $_REQUEST['subject'], 
        'no-reply@code4fun.de', 
        isset($_REQUEST['no_template']) && $_REQUEST['no_template'] ? $_REQUEST['content'] : email_template($_REQUEST['content'])
      );
      $output['emails'] = $_REQUEST['emails'];
      $output['success'][] = 'All emails sent successfully!';
      break;
    case 'set-college-allocations':
      e_assert_isset($_REQUEST, 'allocations');
      e_assert(!C('round.active'), 'You must close the round before making the allocations permanent');
      e_assert(is_array($_REQUEST['allocations']), 'The key `allocations` must be an associative array');
      $output['eids'] = array();
      $ok = true;
      foreach ($_REQUEST['allocations'] as $college => $eids) {
        foreach ($eids as $eid) {
          if (!$Allocation_Model->update_allocation($eid, "college='$college'")) {
            $output['error'][] = "$eid->$college: ".mysql_error();
            $ok = false;
            continue;
          }
          $output['eids'][] = $eid;
        }
      }
      if ($ok) {
        $College_Choice_Model->delete_all();
      }
      $output['success'] = 'Successfully allocated everyone to Mercator... erm... to their respective colleges :)';
      break;
    case 'allocate':
      e_assert_isset($_REQUEST, 'eid,college');
      e_assert(in_array($_REQUEST['college'], $colleges), 'Unknown college `'.$_REQUEST['college'].'`');
      $person = $Person_Model->get($_REQUEST['eid']);
      e_assert($person, 'Invalid eid `'.$_REQUEST['eid'].'`');
      $output['result'] = $Allocation_Model->update_allocation($_REQUEST['eid'], "college='".$_REQUEST['college']."'");
      $output['success'] = 'You have successfully allocated `'.$person['fname'].' '.$person['lname'].'` to `'.$_REQUEST['college'].'`';
      $output['error'] = mysql_error();
      break;
    default:
      outputError( 'Unknown action' );
  }

  jsonOutput( $output );

  
?>