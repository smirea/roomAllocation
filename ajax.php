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

  recursive_escape( $_GET );

  $Search = new Search( array( 'fname', 'lname' ) );
  
  define('MIN_LIMIT', 2);
  
  e_assert( isset($_GET['action']) && strlen($_GET['action']) >= 2, 'No action set' );
  if( !isset( $_SESSION['eid'] ) ){
    jsonOutput(array(
      'error' => 'You were logged off due to timeout',
      'rpc'   => 'RPC.reload();'
    ));
  }
  
  $eid = $_SESSION['eid'];
  
  $tmp_group_id = mysql_fetch_assoc( mysql_query( "SELECT group_id FROM ".TABLE_IN_GROUP." WHERE eid='$eid'") );
  $_SESSION['info']['group_id'] = $tmp_group_id['group_id'];
  $college  = get_college_by_eid( $eid );
  
  $output = array(
    'result'  => false,
    'rpc'     => null,
    'error'   => '',
    'warning' => '',
    'info'    => '',
    'success' => ''
  );
  
  switch($_GET['action']){
    case 'autoComplete':
      e_assert_isset( $_GET, 'str' );
      e_assert( strlen( $_GET['str'] ) >= MIN_LIMIT, 'Query too short. Must have at least '.MIN_LIMIT.' chars' );
      $min_year = (int)date('Y') % 100;
      $columns  = "id,eid,fname,lname,country,college";
      if( $clause = $Search->getQuery( $_GET['str'] ) ){
        $res      = mysql_query( "SELECT $columns FROM ".TABLE_PEOPLE." 
                                WHERE (
                                  (status='undergrad' AND year>'$min_year')
                                  OR (status='foundation-year' AND year='$min_year')
                                )
                                AND $clause" 
                    );
        sqlToJsonOutput( $res );
      } else {
        outputError( 'Invalid query' );
      }
      break;
    case 'addRoommate':
      e_assert( C('round.active'), 'No round is currently active' );
      e_assert_isset( $_GET, array('eid'=>'Roommate not specified') );
      $eid_to       = $_GET['eid'];
      $q_hasRoom    = "SELECT id from ".TABLE_ALLOCATIONS." WHERE (eid='$eid' OR eid='$eid_to') AND college IS NOT NULL AND room IS NOT NULL";
      $q_exists     = "SELECT * FROM ".TABLE_PEOPLE." WHERE eid='$eid_to'";
      $q_sameReq    = "SELECT id FROM ".TABLE_REQUESTS." WHERE (eid_from='$eid' AND eid_to='$eid_to') OR (eid_from='$eid_to' AND eid_to='$eid')";
      
      $sql_exists         = mysql_query( $q_exists );
      $info_to            = mysql_fetch_assoc( $sql_exists );
      $info_to['college'] = get_college_by_eid( $info_to['eid'] );
      
      e_assert( $eid != $eid_to, "Don't be narcissistic, you can't add yourself as a roommate d'oh!" );
      e_assert( mysql_num_rows( $sql_exists ) > 0, "Person does not exist?!?!" );
      e_assert( $info_to['college'] == $college, '<b>'.$info_to['fname'].'</b> is in another college ('.$info_to['college'].') !' );
      e_assert( mysql_num_rows( mysql_query( $q_hasRoom ) ) == 0, "Either you or your chosen roommate already have a room" );
      e_assert( mysql_num_rows( mysql_query( $q_sameReq ) ) == 0, "A requests between you two already exists! You need to check your notifications and accept/reject it..." );
      
      $q = "INSERT INTO ".TABLE_REQUESTS."(eid_from,eid_to) VALUES ('$eid', '$eid_to')";
      @mysql_query( $q );
      $output['result']   = getFaceHTML_sent( $info_to );
      $output['error']    = mysql_error();
      $output['success']  = 'Roommate request sent successfully!';
      break;
    case 'requestSent':
      e_assert( C('round.active'), 'No round is currently active' );
      e_assert_isset( $_GET, 'eid,msg' );
      $eid_to = $_GET['eid'];
      $q = "DELETE FROM ".TABLE_REQUESTS." WHERE (eid_from='$eid' AND eid_to='$eid_to') OR (eid_from='$eid_to' AND eid_to='$eid')";
      mysql_query( $q );
      $output['result'] = mysql_query( $q );
      $output['error']  = mysql_error();
      break;
    case 'requestReceived':
      e_assert( C('round.active'), 'No round is currently active' );
      e_assert_isset( $_GET, 'eid,msg' );
      $eid_to = $_GET['eid'];
      
      $q_isRequest = "SELECT id FROM ".TABLE_REQUESTS." WHERE eid_from='$eid_to' AND eid_to='$eid'";
      e_assert( mysql_num_rows( mysql_query($q_isRequest) ) > 0, 'The person has not sent you any requests' );
      if( $_GET['msg'] == 'yes' ){
        $q_exists   = "SELECT * FROM ".TABLE_PEOPLE." WHERE eid='$eid_to'";
        $sql_exists = mysql_query( $q_exists );
        e_assert( mysql_num_rows( $sql_exists ) > 0, "Person does not exist?!?!" );
        
        $info_to  = mysql_fetch_assoc( $sql_exists );
        $g_from   = group_info( $_SESSION['info']['eid'] );
        $g_to     = group_info( $info_to['eid'] );
        
        $msg_tooMany  = "Too many roommates. The maximum allowed in this phase 
                            is <b>'".MAX_ROOMMATES."'</b> roommate(s) !";
        
        if( $g_from['group_id'] === null && $g_to['group_id'] === null ){
          e_assert( 2 <= MAX_ROOMMATES + 1, $msg_tooMany);
          $_SESSION['info']['group_id'] = add_to_group( $info_to['eid'], add_to_group( $_SESSION['info']['eid'] ) );
        } else if( $g_from['group_id'] === null && $g_to['group_id'] !== null ){
          e_assert( 
            $g_to['members'] <= MAX_ROOMMATES, 
            $msg_tooMany.
            '<br /><b>'.$info_to['fname'].'</b> has '.($g_to['members']-1).' roommate(s)'
          );
          $_SESSION['info']['group_id'] = add_to_group( $_SESSION['info']['eid'], $g_to['group_id'] );
        } else if( $g_from['eid'] === null && $g_to['eid'] !== null ){
          e_assert( $g_from['members'] <= MAX_ROOMMATES, $msg_tooMany);
          $_SESSION['info']['group_id'] = add_to_group( $info_to['eid'], $g_from['group_id'] );
        } else {
          outputError( $msg_tooMany );
          //TODO: MERGE GROUPS !!! ABOVE SHIT IS HACK
          e_assert( 
            $g_from['members'] + $g_to['members'] <= MAX_ROOMMATES + 1,
            $msg_tooMany.
            "<br />You are <b>${g_from['members']} and they are ${group_to['members']}</b> !"
          );
        }
        $new_roommates = get_roommates( $_SESSION['info']['eid'], $_SESSION['info']['group_id'] );
        
        $output['roommates']  = array_map(function($v){ return getFaceHTML($v); }, $new_roommates);
        $output['points']     = print_score( array_merge( array($_SESSION['info']), $new_roommates ) );
        $output['info']       = 'You and <b>'.$info_to['fname'].'</b> are now roommates!
                                  You need to reload the page in order to apply for rooms.';
        $output['rpc']        = 'RPC.reload();';
        
        notifyPerson( $eid_to, $_SESSION['info']['fname']." accepted your roommate request" );
        
        $q = "DELETE FROM ".TABLE_REQUESTS." WHERE eid_to='$eid'";
        $output['result'] = mysql_query( $q );
        
        $q = "DELETE FROM ".TABLE_APARTMENT_CHOICES." WHERE group_id='".$_SESSION['info']['group_id']."'";
        @mysql_query( $q );
        
        /* NOTE:  must check if limit is reach and all that bull
        $q    = "SELECT eid FROM ".TABLE_REQUESTS." WHERE eid_to='$eid'";
        $res  = sqlToArray( mysql_query( $q_getRejected ) );
        foreach( $res as $person ){
          notifyPerson( $person['eid'], $_SESSION['fname']." has choosen another roommate" );
        }
        */
      } else {
        notifyPerson( $_GET['eid'], $_SESSION['info']['fname']." rejected your roommate request" );
      }
      
      $q = "DELETE FROM ".TABLE_REQUESTS." WHERE (eid_from='$eid' AND eid_to='$eid_to') OR (eid_from='$eid_to' AND eid_to='$eid')";
      $output['error'] .= mysql_query( $q ) ? '' : '<div>'.mysql_error().'</div>';
      break;
    case 'getFaceHTML':
      e_assert_isset( $_GET, 'eid,fname,lname,country,year' );
      $output['result'] = getFaceHTML( $_GET );
      break;
    case 'chooseRooms':
      e_assert( C('round.active'), 'No round is currently active' );
      e_assert_isset( $_GET, 'choices' );
      e_assert( is_array( $_GET['choices'] ), "Invalid format for room choices" );
      e_assert( count($_GET['choices']) <= MAX_ROOM_CHOICES, "Too many room selections. You are allowed a max of '".MAX_ROOM_CHOICES."'!");
      
      $roommates = get_roommates( $_SESSION['info']['eid'], $_SESSION['info']['group_id'] );
      
      $rooms          = array();
      $invalid_rooms  = array();
      $bitmask        = array();
      foreach( $_GET['choices'] as $k => $v ){
        if( $v && $v != '' ){
          $tmp = explode(',', $v);
          $tmp = array_map( 'trim', $tmp );
          if( 
            count($tmp) > MAX_ROOMMATES+1 
            || count($roommates)+1 != count($tmp) 
            || array_search( $tmp[0], $allowed_rooms[$college] ) === false ){
            $invalid_rooms[] = "($v)";
          } else {
            sort($tmp);
            $hash = implode(',',$tmp);
            if( !isset($bitmask[$hash]) ){
              $rooms[] = $tmp;
              $bitmask[$hash] = true;
            }
          }
        }
      }
      
      $q_taken = "SELECT room 
                  FROM ".TABLE_ALLOCATIONS."
                  WHERE college='$college' 
                  AND room IN ('".implode("','",array_reduce($rooms,'array_merge', array() ))."')";
      $taken = extract_column('eid', sqlToArray( mysql_query( $q_taken ) ) );
      
      if( count($taken) > 0 ){
        $intersect = array_intersect( $rooms, $taken );
        $output['error'] .= '<div>The following rooms are already taken by someone else:
                              '.implode(', ',$intersect).'.
                            </div>';
      }
      
      if( count($invalid_rooms) > 0 ){
        $output['error'] .= '<div>You are not allowed to apply for these apartments:
                              <b>'.implode(', ', $invalid_rooms).'</b>.</div>';
      }
      
      e_assert( $output['error'] == '', $output['error'] );
      
      $group_id = $_SESSION['info']['group_id'];
      $values   = array();
      foreach( $rooms as $k => $v ){
        foreach( $v as $room ){
          $values[] = "('$room','$college','$group_id','$k')";
        }
      }
      $values = implode(', ', $values);
      
      mysql_query( "DELETE FROM ".TABLE_APARTMENT_CHOICES." WHERE group_id='$group_id'" );
      $q = "INSERT INTO ".TABLE_APARTMENT_CHOICES."(number,college,group_id,choice) VALUES $values";
      $output['result'] = mysql_query($q);
      $output['error'] .= mysql_error();
      $output['info']   = 'Rooms updated successfully!';
      break;
    case 'selectRooms':
      //TODO: EROR:CHECKING
      foreach( $_GET as $k => $v ){
        if( substr( $k, 0, 5 ) == 'room-' ){
          $room = substr( $k, 5 );
          $q = "UPDATE ".TABLE_ALLOCATIONS." SET room='$room' WHERE eid='$v'";
          echo $q."\n";
          echo mysql_error();
          mysql_query($q);
        }
      }
      $output['info'] = 'Rooms update successfully!';
      break;
    default: 
      outputError( 'Unknown action' );
  }
  
  jsonOutput( $output );
  
  function notifyPerson( $eid, $message ){
    //TODO: me
  }
  
?>
