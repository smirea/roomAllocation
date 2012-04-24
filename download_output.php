<?php
  
  require_once 'config.php';

  if( isset($_GET['college']) ){
  
    $q = "SELECT a.college,a.room,p.fname,p.lname
          FROM ".TABLE_PEOPLE." p, ".TABLE_ALLOCATIONS." a 
          WHERE a.college='${_GET['college']}' AND a.room IS NOT NULL AND a.eid=p.eid
          ORDER BY a.room";
    if( $sql = mysql_query( $q ) ){
      $info = sqlToArray( $sql );
      $arr  = array();
      foreach( $info as $row ){
        $arr[] = '"'.$row['room'].'","'.$row['fname'].'","'.$row['lname'].'"';
      }
      header("Pragma: public");
      header("Expires: 0");
      header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
      header("Cache-Control: private",false);
      header("Content-Type: application/octet-stream");
      header("Content-Disposition: attachment; filename=\"${_GET['college']}.csv\";" );
      header("Content-Transfer-Encoding: binary");
      echo implode("\n", $arr);
    } else {
      exit( mysql_error() );
    }
  } else {
    exit( 'No college specified!' );
  }
  
?>