<?php
  
  require_once( 'floorPlan/utils.php' );
  
  $tall_rooms = array(
    'Mercator' => array_unique(
      explode(',', 'A-108,A-208,A-308,B-108,B-208,B-308,C-108,C-208,C-308')
    ),
    'Krupp' => array_unique(
      explode(',', 'A-108,A-208,A-308,B-108,B-208,B-308,C-108,C-208,C-308')
    ),
    'College-III' => array_unique(
      explode(',', 'A-108,A-208,A-308,B-108,B-208,B-308,C-108,C-208,C-308,D-108,D-208,D-308')
    ),
    'Nordmetall'  => array(
      explode(',', '')
    )
  );
  
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
          apartment_pattern( 4, 108, 2, 1 ),
          apartment_pattern( 4, 124, 2, 1 ),
          apartment_pattern( 5, 208, 2, 1 ),
          apartment_pattern( 5, 224, 2, 1 ),
          apartment_pattern( 5, 308, 2, 1 ),
          apartment_pattern( 5, 324, 2, 1 )
        ),
        explode(',','A-208,A-209,A-308,A-309,B-108,B-109,B-208,B-209,B-308,B-309,C-108,C-109,C-208,C-209,C-308,C-309'),
        explode(',','A-101,A-102,A-103,B-101,B-102,B-103')
      )
    ),
    'College-III' => array_unique(
      array_merge(
        explode(',','A-208,A-209,A-308,A-309,B-108,B-109,B-208,B-209,B-308,B-309,C-108,C-109,C-208,C-209,C-308,C-309,D-108,D-109,D-308,D-309'),
        explode(',','B-101,B-102,B-103,A-101,A-102,A-103'),
        prepend('D-', 
          explode(',','240,241'),
          apartment_pattern( 8, 108, 2, 1 ),
          apartment_pattern( 8, 208, 2, 1 ),
          apartment_pattern( 8, 308, 2, 1 )
        )
      )
    ),
    'Nordmetall'  => array_unique(
      array_merge(
        explode(',', 'B-178,B-180,B-181,B-184,B-185,B-188,B-189,B-192,B-193,B-196,B-197,B-278,B-280,B-281,B-284,B-285,B-288,B-289,B-292,B-293,B-296,B-297,B-378,B-380,B-381,B-384,B-385,B-388,B-389,B-392,B-393,B-396,B-397,B-478,B-480,B-481,B-484,B-485,B-488,B-489,B-492,B-493,B-496,B-497,C-208,C-210,C-211,C-216,C-218,C-219,C-308,C-310,C-311,C-316,C-318,C-319,C-408,C-410,C-411,C-416,C-418,C-419,C-222,C-224,C-203,C-303'),
        explode(',', '')
      )
      // array(
      //   'A-303','A-305','A-307','A-309','A-311','A-313','A-315','A-317','A-319','A-321','A-323','A-325','A-327','A-329','A-331','A-333','A-335', 'A-523','A-525','A-527','A-529','A-531','A-533','A-535'
      // )
      // array_merge(
      //   explode(',','B-439,C-218,C-219,C-318,C-319,C-418,C-419'),
      //   prepend('B-',
      //     apartment_pattern( 5, 402, 2, 1 ),
      //     apartment_pattern( 4, 426, 2, 1 )
      //   )
      // )
    )
  
  );
  
  function prepend( $str, array $arr ){
    $args = array_reduce( array_slice( func_get_args(), 1 ), 'array_merge', array() );
    return array_map(function($v) use ($str){return "$str$v";}, $args);
  }
  
?>