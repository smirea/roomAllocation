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
      $eid_from   = $_SESSION['eid'];
      $eid_to     = $_GET['eid'];
      $q_hasRoom  = "SELECT id from ".TABLE_ALLOCATIONS." WHERE (eid='$eid_from' OR eid='$eid_to') AND college IS NOT NULL AND room IS NOT NULL";
      $q_exists   = "SELECT * FROM ".TABLE_PEOPLE." WHERE eid='$eid_to'";
      $q_sameReq  = "SELECT id FROM ".TABLE_REQUESTS." WHERE (eid_from='$eid_from' AND eid_to='$eid_to') OR (eid_from='$eid_to' AND eid_to='$eid_from')";
      $q_noReq    = "SELECT id FROM ".TABLE_REQUESTS." WHERE eid_from='$eid_from' UNION SELECT id FROM ".TABLE_ROOMMATES." WHERE eid_a='$eid_from'";
      
      $sql_exists = mysql_query( $q_exists );
      
      e_assert( $eid_from != $eid_to, "Don't be narcissistic, you can't add yourself as a roommate d'oh!" );
      e_assert( mysql_num_rows( mysql_query( $q_hasRoom ) ) == 0, "Either you or your chosen roommate already have a room" );
      e_assert( mysql_num_rows( $sql_exists ) > 0, "Person does not exist?!?!" );
      e_assert( mysql_num_rows( mysql_query( $q_sameReq ) ) == 0, "A requests between you two already exists! You need to check your notifications and accept/reject it..." );
      e_assert( mysql_num_rows( mysql_query( $q_noReq ) ) < MAX_ROOMMATES, "You can't have more than ".MAX_ROOMMATES." roommate(s) this round. You must cancel some requests before you continue");
      
      $q = "INSERT INTO ".TABLE_REQUESTS."(eid_from,eid_to) VALUES ('$eid_from', '$eid_to')";
      if( mysql_query($q) ){
        $info = sqlToArray( $sql_exists );
        jsonOutput( array( 'result' => getFaceHTML_sent( $info[0] ) ) );
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
        notifyPerson( $_GET['eid'], $_SESSION['fname']." accepted your roommate request" );
        $q_insert = "INSERT INTO ".TABLE_ROOMMATES."(eid_a,eid_b) VALUES ('$eid_from','$eid_to'),('$eid_to','$eid_from')";
        mysql_query( $q_insert );
        /* NOTE:  must check if limit is reach and all that bull
        $q    = "SELECT eid FROM ".TABLE_REQUESTS." WHERE eid_to='$eid_from'";
        $res  = sqlToArray( mysql_query( $q_getRejected ) );
        foreach( $res as $person ){
          notifyPerson( $person['eid'], $_SESSION['fname']." has choosen another roommate" );
        }
        */
      } else {
        notifyPerson( $_GET['eid'], $_SESSION['fname']." rejected your roommate request" );
      }
      
      $q = "DELETE FROM ".TABLE_REQUESTS." WHERE (eid_from='$eid_from' AND eid_to='$eid_to') OR (eid_from='$eid_to' AND eid_to='$eid_from')";
      jsonOutput( array('result' => mysql_query( $q )) );
      break;
    case 'getFaceHTML':
      e_assert_isset( $_GET, 'eid,fname,lname,country,year' );
      jsonOutput( array( 'result' => getFaceHTML( $_GET ) ) );
      break;
    default: 
      outputError( 'Unknown action' );
  }
  
  function notifyPerson( $eid, $message ){
    //TODO: me
  }
  
?>
