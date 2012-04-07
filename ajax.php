<?php
  
  require_once 'config.php';
  require_once 'class.Search.php';
  require_once 'utils.php';
  
  $Search = new Search( array( 'fname', 'lname' ) );
  
  define('MIN_LIMIT', 2);
  
  e_assert( isset($_GET['action']) && strlen($_GET['action']) >= 2, 'No action set' );
  if( !isset( $_SESSION['eid'] ) ){
    jsonOutput(array(
      'error' => 'You were logged off due to timeout',
      'rpc'   => 'reload'
    ));
  }
  
  switch($_GET['action']){
    case 'autoComplete':
      e_assert_isset( $_GET, 'str' );
      e_assert( strlen( $_GET['str'] ) >= MIN_LIMIT, 'Query too short. Must have at least '.MIN_LIMIT.' chars' );
      $columns  = "id,eid,fname,lname,country,college";
      if( $clause = $Search->getQuery( $_GET['str'] ) ){
        $res      = mysql_query( "SELECT $columns FROM ".TABLE_PEOPLE." WHERE (STATUS='undergrad' OR STATUS='master') AND $clause" );
        sqlToJsonOutput( $res );
      } else {
        outputError('Invalid query' );
      }
      break;
    case 'addRoommate':
      e_assert_isset( $_GET, array('eid'=>'Roommate not specified') );
      $eid_from     = $_SESSION['eid'];
      $eid_to       = $_GET['eid'];
      $q_hasRoom    = "SELECT id from ".TABLE_ALLOCATIONS." WHERE (eid='$eid_from' OR eid='$eid_to') AND college IS NOT NULL AND room IS NOT NULL";
      $q_exists     = "SELECT * FROM ".TABLE_PEOPLE." WHERE eid='$eid_to'";
      $q_sameReq    = "SELECT id FROM ".TABLE_REQUESTS." WHERE (eid_from='$eid_from' AND eid_to='$eid_to') OR (eid_from='$eid_to' AND eid_to='$eid_from')";
      
      $sql_exists = mysql_query( $q_exists );
      $info_to    = mysql_fetch_assoc( $sql_exists );
      
      e_assert( $eid_from != $eid_to, "Don't be narcissistic, you can't add yourself as a roommate d'oh!" );
      e_assert( mysql_num_rows( mysql_query( $q_hasRoom ) ) == 0, "Either you or your chosen roommate already have a room" );
      e_assert( mysql_num_rows( $sql_exists ) > 0, "Person does not exist?!?!" );
      e_assert( mysql_num_rows( mysql_query( $q_sameReq ) ) == 0, "A requests between you two already exists! You need to check your notifications and accept/reject it..." );
      
      $q = "INSERT INTO ".TABLE_REQUESTS."(eid_from,eid_to) VALUES ('$eid_from', '$eid_to')";
      if( mysql_query($q) ){
        jsonOutput( array( 'result' => getFaceHTML_sent( $info_to ) ) );
      } else {
        jsonOutput( array('result' => false, 'error' => mysql_error()) );
      }
      break;
    case 'requestSent':
      e_assert_isset( $_GET, 'eid,msg' );
      $eid_from   = $_SESSION['eid'];
      $eid_to     = $_GET['eid'];
      $q = "DELETE FROM ".TABLE_REQUESTS." WHERE (eid_from='$eid_from' AND eid_to='$eid_to') OR (eid_from='$eid_to' AND eid_to='$eid_from')";
      mysql_query( $q );
      jsonOutput( array('result' => mysql_query( $q )) );
      break;
    case 'requestReceived':
      e_assert_isset( $_GET, 'eid,msg' );
      $eid_from   = $_SESSION['eid'];
      $eid_to     = $_GET['eid'];
      
      $q_isRequest = "SELECT id FROM ".TABLE_REQUESTS." WHERE eid_from='$eid_to' AND eid_to='$eid_from'";
      e_assert( mysql_num_rows( mysql_query($q_isRequest) ) > 0, 'The person has not sent you any requests' );
      if( $_GET['msg'] == 'yes' ){
        $q_exists   = "SELECT * FROM ".TABLE_PEOPLE." WHERE eid='$eid_to'";
        $sql_exists = mysql_query( $q_exists );
        e_assert( mysql_num_rows( $sql_exists ) > 0, "Person does not exist?!?!" );
        
        $info_to  = mysql_fetch_assoc( $sql_exists );
        $g_from   = group_info( $_SESSION['info']['eid'] );
        $g_to     = group_info( $info_to['eid'] );
        
        if( $g_from['group_id'] === null && $g_to['group_id'] === null ){
          add_to_group( $info_to['eid'], add_to_group( $_SESSION['info']['eid'] ) );
        } else if( $g_from['group_id'] === null && $g_to['group_id'] !== null ){
          add_to_group( $_SESSION['info']['eid'], $g_to['group_id'] );
        } else if( $g_from['eid'] === null && $g_to['eid'] !== null ){
          add_to_group( $info_to['eid'], $g_from['group_id'] );
        } else {
          outputError("You and this person have more than ".MAX_ROOMMATES." roommates together");
          //TODO: MERGE GROUPS !!! ABOVE SHIT IS HACK
          e_assert( 
            $g_from['members'] + $g_to['members'] <= MAX_ROOMMATES + 1,
            "You are only allowed a maximum of ".MAX_ROOMMATES." roommates this round.".
            "<br />You are <b>${g_from['members']} and they are ${group_to['members']}</b>!"
          );
        }
        notifyPerson( $eid_to, $_SESSION['info']['fname']." accepted your roommate request" );
        /* NOTE:  must check if limit is reach and all that bull
        $q    = "SELECT eid FROM ".TABLE_REQUESTS." WHERE eid_to='$eid_from'";
        $res  = sqlToArray( mysql_query( $q_getRejected ) );
        foreach( $res as $person ){
          notifyPerson( $person['eid'], $_SESSION['fname']." has choosen another roommate" );
        }
        */
      } else {
        notifyPerson( $_GET['eid'], $_SESSION['info']['fname']." rejected your roommate request" );
      }
      
      $q = "DELETE FROM ".TABLE_REQUESTS." WHERE (eid_from='$eid_from' AND eid_to='$eid_to') OR (eid_from='$eid_to' AND eid_to='$eid_from')";
      jsonOutput( array('result' => mysql_query( $q )) );
      break;
    case 'getFaceHTML':
      e_assert_isset( $_GET, 'eid,fname,lname,country,year' );
      jsonOutput( array( 'result' => getFaceHTML( $_GET ) ) );
      break;
    case 'chooseRooms':
      e_assert_isset( $_GET, 'choices' );
      e_assert( count($_GET['choices']) <= MAX_ROOM_CHOICES, "Too many room selections. You are allowed a max of '".MAX_ROOM_CHOICES."'!");
      break;
    default: 
      outputError( 'Unknown action' );
  }
  
  function notifyPerson( $eid, $message ){
    //TODO: me
  }
  
?>
