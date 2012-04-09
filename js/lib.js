/**
 * This file is a collection of shared function between multiple other javascript files
 */

function get_apartment( $element ){
  var id      = $element.attr('id');
  var prefix  = '#' + id.substr( 0, id.length-3 );
  var no      = Number(id.split('-')[2]);
  var $r      = $();
  if( no <= 103 ){
    $r = $(prefix+'101,'+prefix+'102,'+prefix+'103');
  } else if( no % 2 == 0 ){
    $r = $element.add( $(prefix+(no+1)) );
  } else {
    $r = $element.add( $(prefix+(no-1)) );
  }
  return $r;
} 
