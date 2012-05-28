<?php
  
  
  $a = array(
    22, 55, 14, 1, 3, 21, 4, 2, 1, 1, 51, 2, 1, 3
  );
  $a = array_combine( range('a','n'), $a );
  
  var_export( $a );
  
  uarsort( $a );
  var_export( $a );
  var_export( array_keys($a, 1) );
 
  /**
   * @brief User-Key-Random-Sort
   * @param {array} &$array
   * @param {function} $compare
   * @param {function} $sort
   */
  function urasort( array &$array, $compare = null ){
  
    function add_to_result( array $buffer, array &$new, array $array ){
      shuffle( $buffer );
      $new = array_merge(
        $new,
        array_combine(
          $buffer,
          array_map(function($k)use($array){ return $array[$k]; }, $buffer)
        )
      );
    }
  
    $compare = $compare ? $compare : function($a,$b){ return $a-$b; };
    uasort( $array, $compare );
    
    $new = array();
    
    reset( $array );
    $buffer = array( key($array) );
    next( $array );
    while( list($key, $value) = each( $array ) ){
      if( $compare( $array[$buffer[0]], $value ) == 0 ){
        $buffer[] = $key;
      } else {
        add_to_result( $buffer, $new, $array );
        $buffer = array( $key );
      }
    }
    add_to_result( $buffer, $new, $array );
    $array = $new; 
  }
  
?>