console = console || {log:function(){}, warn:function(){}, error:function(){}};

var ajax_file = 'ajax.php';

var sendResponse;

(function($){
  
  var $searchBox;
  var $search;
  var $eid;
  var $addRoommate;
  var $loading;
  var messages = $();
  var message_timeout;
  var rpc = {
    reload  : function(){ window.location.reload(); }
  };
  
  $(function(){
    alter_jquery_ui();
    set_variables();
    init_roommate_search();
    add_floorplan_events();
    // refresh after 20 minutes so you don't get a session timeout
    setTimeout( function(){ window.location.reload() }, 20 * 60 * 1000 );
  });
  
  
  var alter_jquery_ui = function(){
    /* allows us to pass in HTML tags to autocomplete. Without this they get escaped */
    $[ "ui" ][ "autocomplete" ].prototype["_renderItem"] = function( ul, item ) {
      return $( "<li></li>" ) 
        .data( "item.autocomplete", item )
        .append( $( "<a></a>" ).html( item.label ) )
        .appendTo( ul );
    };
  };
  
  var set_variables = function(){
    $searchBox    = $('#searchBox');
    $search       = $('#search');
    $eid          = $('#roommate-eid');
    $addRoommate  = $('#addRoommate');
    
    $loading      = $(document.createElement('img'));
    $loading
      .insertBefore( $search )
      .attr({
        'src'       : 'images/ajax.gif',
        'height'    : 22
      })
      .css({
        position  : 'absolute',
        top       : 2,
        right     : 7
      }).hide();
      
    messages = $('#message-info,#message-error,#message-warning,#message-success');
  };
  
  var init_roommate_search = function(){
    $search
      .autocomplete({
        autofocus : true,
        minLength : 2,
        delay     : 200,
        source    : function( request, response ){
          $.get( ajax_file, {
            action  : 'autoComplete',
            str     : request.term
          }, function( data ){
            response($.map( data, function( item ){
              return {
                label : item.fname+' '+item.lname,
                value : item.fname+' '+item.lname,
                full  : item
              }
            }));
          });
        }
      });

    // Override default select method for the autocomplete to prevent the menu from closing
    $search.data("autocomplete").menu.options.selected = function(event, data) {
      $search.focus();
      $search.autocomplete('close');
      $eid.val( data.item.data('item.autocomplete').full.eid );
      return false;
    };
    
    $searchBox.bind('submit.addRoommate', function(){
      $addRoommate.hide();
      $loading.show();
      $.get( ajax_file, {
        action  : 'addRoommate',
        eid     : $eid.val()
      }, function( data ){
        $loading.hide();
        $addRoommate.show();
        if( !data.error && data.result ){
          message( 'success', 'Roommate request sent successfully!' );
          var elem = $(document.createElement('div')).html(data.result).unwrap();
          $('#requests-sent').find('.none').slideUp().end().append( elem );
          elem.hide().slideDown();
        } else {
          message( 'error', data.error );
        }
      });
      return false;
    });
  };
  
  sendResponse = function( type, eid, msg ){
    if( ['requestReceived', 'requestSent'].indexOf(type) == -1 ){
      console.warn( 'Unknown type in sendResponse', arguments );
      return false;
    }
    $.get( ajax_file, {
      action  : type,
      eid     : eid,
      msg     : msg
    }, function( data ){
      if( !data.error && data.result ){
        var $face = $('#face-eid-'+eid);
        var $cr = $('#current-roommates');
        console.log( $face.siblings(':visible').length );
        if( $face.siblings(':visible').length == 0 ){
          $face.parent().find('.none').slideDown();
        }
        if( type == 'requestReceived' && msg == 'yes' ){
          $cr.find('.none').slideUp();
          $face.find('.actions').hide();
          $face.fadeOut(800).fadeIn();
          setTimeout(function(){ $face.appendTo($cr) }, 550 );
        } else {
          $face.fadeOut(800);
        }
      } else {
        message( 'error', data.error );
      }
    });
  }

  var add_floorplan_events = function(){
    $('.room').bind('mouseover mouseout', function(){
      var id      = $(this).attr('id');
      var prefix  = '#' + id.substr( 0, id.length-3 );
      var no      = Number(id.split('-')[2]);
      var $rooms  = $();
      if( no <= 103 ){
        $rooms = $(prefix+'101,'+prefix+'102,'+prefix+'103');
      } else if( no % 2 == 0 ){
        $rooms = $(this).add( $(prefix+(no+1)) );
      } else {
        $rooms = $(this).add( $(prefix+(no-1)) );
      }
      $rooms.toggleClass('selected');
    })
  }
  
  function message( type, message ){
    var msg = messages.filter('.'+type);
    if( msg.length > 0 ){
      messages.stop(true,true).clearQueue().hide();
      msg.fadeIn('slow').find('.content').html( message );
      clearTimeout( message_timeout );
      message_timeout = setTimeout( function(){ msg.fadeOut(); }, 10 * 1000 );
    } else {
      console.warn( 'Unknown message type', arguments );
    }
  }
  
})( jQuery );