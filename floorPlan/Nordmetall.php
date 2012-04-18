<?php
  
  $Nordmetall = array(
    /** Too freakin' different. Someone else do it :) **/
  );
  
  $Nordmetall_apartments = array_merge(
    NM_B_ground_floor(),
    NM_B_upper_floor(2),
    NM_B_upper_floor(3),
    NM_B_upper_floor(4)
  );
  
  $Nordmetall_rooms = NM_apartments_to_rooms( $Nordmetall_apartments );
  
?>