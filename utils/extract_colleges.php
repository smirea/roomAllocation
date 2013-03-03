<?php

  require_once '../config.php';
  require_once '../class.Search.php';

  define( 'FILE_COLLEGES', 'allocations.csv' );

  $content  = file_get_contents( FILE_COLLEGES );
  $lines    = explode("\n", $content);
  $order    = explode("|", $lines[0]);
  $colleges = array(
    'Krupp'       => array(),
    'Mercator'    => array(),
    'College-III' => array(),
    'Nordmetall'  => array()
  );

  $Search = new Search( array( 'fname', 'lname' ) );

  $str_status = array(
    'OK'        => '<b style="color:green">OK</b>',
    'SEARCHED'  => '<b style="color:orange;">SEARCHED</b>',
    'FAIL'      => '<b style="color:red;">FAIL</b>'
  );

  $records  = array(
    'OK'        => array(),
    'SEARCHED'  => array(),
    'FAIL'      => array()
  );

  for( $i=1; $i<count($lines); ++$i){
    $vals = explode("|", $lines[$i]);
    for( $j=0; $j<4; ++$j ){
      if( strlen($vals[$j]) > 2 ){
        $name     = $vals[$j];
        $name     = array_map(function($v){return trim($v,'" ');}, explode(',', $name));
        $str_name = implode(' ', $name);
        $colleges[$order[$j]][] = $str_name;
        /*
        $q = "UPDATE ".TABLE_ALLOCATIONS." SET college='".$order[$j]."'
                WHERE lname='${name[1]}' AND fname='${name[0]}'";
                */
        $q = "SELECT eid FROM ".TABLE_PEOPLE."
              WHERE lname='${name[1]}' AND fname='${name[0]}'";
        $status = 'OK';
        $person = mysql_query( $q );
        if( !$person || mysql_num_rows($person) == 0 ){
          $search_query = $str_name;
          $search_query = str_replace(array('-',"'"),array(' ','%'), $search_query);
          $search_query = preg_replace('/[^(\x20-\x7F)]+/','%', $search_query);
          $clause       = $Search->getQuery( $search_query );
          $query        = "SELECT eid FROM ".TABLE_PEOPLE." WHERE $clause";
          $person       = mysql_query( $query );
          if( $clause
              && $person
              && mysql_num_rows( $person ) > 0
          ){
            $status = 'SEARCHED';
          } else {
            $status = 'FAIL';
          }
        }

        if( $status != 'FAIL' ){
          $eid    = mysql_fetch_assoc( $person );
          $eid    = $eid['eid'];
          $q      = "UPDATE ".TABLE_ALLOCATIONS." SET college='".$order[$j]."' WHERE eid='$eid'";
          $update = mysql_query( $q ) ? '' : mysql_error();
        }

        $records[$status][] ='
          <tr>
            <td>'.$str_status[$status].'</td>
            <td>'.$str_name.'</td>
            <td>'.$order[$j].'</td>
            <td>'.mysql_error().'</td>
            <td>'.$update.'</td>
          </tr>';
      }
    }
  }
  echo '
    <table>
      <tr>
        <th>status</th>
        <th>name</th>
        <th>college</th>
        <th>mysql_error()</th>
        <th>update</th>
      </tr>
      '.implode("\n", $records['FAIL']).'
      '.implode("\n", $records['SEARCHED']).'
      '.implode("\n", $records['OK']).'
    </table>
  ';
  //var_export( $colleges );

?>