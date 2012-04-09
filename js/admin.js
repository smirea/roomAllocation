$(function(){
  
  var $rooms = $('.room');
  
  $('<div />').qtip({
    content: ' ', 
    position: {
      target    : 'event',
      effect    : false,
      viewport  : $(window)
    },
    show: {
      target  : $rooms,
      event   : 'click'
    },
    hide: {
      target  : $rooms,
      event   : 'unfocus'
    },
    events: {
      show: function(event, api) {
        var $target   = $(event.originalEvent.currentTarget);
        var cls       = $target.attr('class').split(' ');
        var $content  = $();
        for( var i in cls ){
          if( /^group-[0-9]+$/.test( cls[i] ) ){
            $content = $content.add( $('#'+cls[i]).clone() );
          }
        }
        
        if( $content.length ) {
          api.set( 'content.text', $content );
        } else {
          api.set( 'content-text', '-- nobody applied for this apartment :( --' );
        }
      }
    }
  });
  
}); 
