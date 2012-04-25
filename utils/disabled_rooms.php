<?php
  
  $disabled = array(
    'Krupp'       => 'A-302,A-303,B-202,B-203,C-101,C-102,C-103',
    'Mercator'    => 'A-101,A-102,A-103,B-202,B-203,C-302,C-303',
    'College-III' => 'C-101,C-102,C-103,C-202,C-203,C-240,C-241,C-302,C-303,'.
                      'D-101,D-102,D-103,D-202,D-203,D-302,D-303,D-340,D-341',
    'Nordmetall'  => ''
  );
  
  $result = array();
  
  foreach( $disabled as $college => $str_rooms ){
    $rooms = array_map( 'trim', explode(',', $str_rooms));
    foreach( $rooms as $room ){
      if( $room ){
        $result[] = "('0','$college','$room','0')";
      }
    }
  }
  
  echo "<pre>";
  echo "INSERT INTO Allocations(college,room,round) VALUES \n".implode(",\n", $result).';';
  echo "</pre>";
?>