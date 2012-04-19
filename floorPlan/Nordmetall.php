<?php
  
  $Nordmetall = array(
    /** Too freakin' different. Someone else do it :) **/
  );
  
  $Nordmetall_apartments = array_merge(
    NM_B_ground_floor(),
    NM_B_upper_floor(2),
    NM_B_upper_floor(3),
    NM_B_upper_floor(4),
    array_chunk( 
      explode(',', 'C-210,C-211,C-218,C-219,C-310,C-311,C-318,C-319,C-410,C-411,C-418,C-419'),
      2
    )
  );

  $Nordmetall_rooms = NM_apartments_to_rooms( $Nordmetall_apartments );
  
?>