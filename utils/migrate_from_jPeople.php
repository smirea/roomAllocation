<?php

  require_once 'config.php';

  require_once 'models/Allocation_Model.php';

  $Allocation_Model = new Allocation_Model();

  /** jPeople database info */
  define( 'JP_DB_USER', 'jPerson' );
  define( 'JP_DB_PASS', 'jacobsRulz' );
  define( 'JP_DB_NAME', 'jPeople' );
  define( 'JP_TABLE_SEARCH', 'Search' );

  /** Extract info from jPeople and re-create the initial db connection*/
  mysql_close();
  dbConnect( JP_DB_USER, JP_DB_PASS, JP_DB_NAME );
  $columns = "id,eid,account,fname,lname,country,college,email,year,status,major";
  $q = mysql_query( "SELECT $columns FROM ".JP_TABLE_SEARCH."" );
  if( !$q ){ exit( 'Unable to connect to the jPeople database' ); }

  $info = sqlToArray( $q );
  mysql_close();
  dbConnect( DB_USER, DB_PASS, DB_NAME );

  // delete old data;
  mysql_query( "DELETE FROM ".TABLE_PEOPLE );

  /** Add info to the database */
  $columns .= ',query';
  $h = '<table>';
  $h .= '<tr><td>status</td><td>name</td><td>eid</td><td>mysql_error()</td></tr>';
  foreach( $info as $row ){
    $row['query'] = $row['fname'].' '.$row['lname'];
    $h .= addToDb( $row );
    $h .= updateAllocations( $row );
  }
  $h .= '</table>';
  echo "<hr />$h";

  function updateAllocations( $info ){
    global $Allocation_Model;
    $insert_query = $Allocation_Model->insert(array('eid' => $info['eid']));
    $status = '<b style="color:green">OK</b>';
    $error  = '';
    if (!$insert_query) {
      $status = '<b style="color:red">FAIL</b>';
      $error  = mysql_error();
    }
    return "<tr><td>$status</td><td>(Insert into ".TABLE_ALLOCATIONS.")</td><td></td><td>$error</td></tr>";
  }

  /**
   * @brief Adds a person to the database and prints an apropriate message
   * @param {array} $info a row from the jPeople Search table
   */
  function addToDb( array $info ){
    global $columns;
    $row    = implode(',', array_map( function($v){ return '"'.$v.'"'; }, $info) );
    $q      = "INSERT INTO ".TABLE_PEOPLE."($columns) VALUES ($row)";
    $status = '<b style="color:green">OK</b>';
    $error  = '';
    if( !mysql_query( $q ) ){
      $status = '<b style="color:red">FAIL</b>';
      $error  = mysql_error();
    }
    return "<tr><td>$status</td><td>'${info['fname']}, ${info['lname']}'<td>${info['eid']}</td></td><td>$error</td></tr>";
  }

?>
