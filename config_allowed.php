<?php
  
  require_once( 'floorPlan/utils.php' );
  
  $allowed_rooms = array(
    'Mercator' => array_unique(
      array_merge(
        explode(',','A-208,A-209,A-308,A-309,B-108,B-109,B-208,B-209,B-308,B-309,C-108,C-109,C-208,C-209,C-308,C-309'),
        explode(',','B-101,B-102,B-103,C-101,C-102,C-103')
      )
    ),
    'Krupp' => array_unique(
      array_merge(
        prepend('A-', 
          apartment_pattern( 4, 124, 2, 1 ),
          apartment_pattern( 4, 224, 2, 1 ),
          apartment_pattern( 4, 324, 2, 1 )
        ),
        explode(',','A-208,A-209,A-308,A-309,B-108,B-109,B-208,B-209,B-308,B-309,C-108,C-109,C-208,C-209,C-308,C-309'),
        explode(',','B-101,B-102,B-103,A-101,A-102,A-103')
      )
    ),
    'College-III' => array_unique(
      array_merge(
        explode(',','A-208,A-209,A-308,A-309,B-108,B-109,B-208,B-209,B-308,B-309,C-108,C-109,C-208,C-209,C-308,C-309,D-108,D-109,D-308,D-309'),
        explode(',','B-101,B-102,B-103,A-101,A-102,A-103'),
        prepend('D-', 
          explode(',','202,203,240,241,302,303,340,341'),
          apartment_pattern( 8, 108, 2, 1 ),
          apartment_pattern( 8, 208, 2, 1 ),
          apartment_pattern( 8, 308, 2, 1 )
        )
      )
    ),
    'Nordmetall'  => array_unique(
      array_merge(
        explode(',','B-439,C-218,C-219,C-318,C-319,C-418,C-419'),
        prepend('B-',
          apartment_pattern( 5, 402, 2, 1 ),
          apartment_pattern( 4, 426, 2, 1 )
        )
      )
    )
  
  );
  
  function prepend( $str, array $arr ){
    $args = array_reduce( array_slice( func_get_args(), 1 ), 'array_merge', array() );
    return array_map(function($v) use ($str){return "$str$v";}, $args);
  }
  
?>