<?php
  
  require_once( 'floorPlan/utils.php' );
  
  $allowed_rooms = array(
    'Mercator' => array_merge(
      explode(',','A-208,A-209,A-308,A-309,B-108,B-109,B-208,B-209,B-308,B-309,C-108,C-109,C-208,C-209,C-308,C-309'),
      explode(',','B-101,B-102,B-103,C-101,C-102,C-103')
    ),
    'Krupp' => array_merge(
      explode(',','A-208,A-209,A-308,A-309,B-108,B-109,B-208,B-209,B-308,B-309,C-108,C-109,C-208,C-209,C-308,C-309'),
      explode(',','B-101,B-102,B-103,C-101,C-102,C-103')
    ),
    'College-III' => array_merge(
      explode(',','A-208,A-209,A-308,A-309,B-108,B-109,B-208,B-209,B-308,B-309,C-108,C-109,C-208,C-209,C-308,C-309,D-108,D-109,D-308,D-309'),
      explode(',','B-101,B-102,B-103,A-101,A-102,A-103'),
      array_map(function($v){return "D-$v";}, 
        array_merge(
          apartment_pattern( 8, 108, 2, 1 ),
          apartment_pattern( 8, 208, 2, 1 ),
          apartment_pattern( 8, 308, 2, 1 )
        )
      )
    ),
    'Nordmetall'  => array_merge(
      explode(',','B-439,C-218,C-219,C-318,C-319,C-418,C-419'),
      array_map(function($v){return "B-$v";},array_merge(
        apartment_pattern( 5, 402, 2, 1 ),
        apartment_pattern( 4, 426, 2, 1 )
      ))
    )
  
  );
  
?>