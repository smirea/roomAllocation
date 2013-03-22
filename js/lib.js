/**
 * This file is a collection of shared function between multiple other javascript files
 */

console = console || {log:function(){}, warn:function(){}, error:function(){}};
var ajax_file = 'ajax.php';

var messages = $();
var message_timeout;

$(function message_events () {
  messages = $('#message-info,#message-error,#message-warning,#message-success');
  $('body').append(''+
    '<div class="message-holder">' +
      '<div id="message-info" class="info message">' +
        '<div class="content"></div>' +
        '<a href="javascript:void(0)" class="close">X</a>' +
      '</div>' +
      '<div id="message-error" class="error message">' +
        '<div class="content"></div>' +
        '<a href="javascript:void(0)" class="close">X</a>' +
      '</div>' +
      '<div id="message-warning" class="warning message">' +
        '<div class="content"></div>' +
        '<a href="javascript:void(0)" class="close">X</a>' +
      '</div>' +
      '<div id="message-success" class="success message">' +
        '<div class="content"></div>' +
        '<a href="javascript:void(0)" class="close">X</a>' +
      '</div>' +
    '</div>'
  );
  $('.message .close').live('click.close', function(){
    var $msg = $(this).parent();
    $msg.fadeOut( 600, function(){ $msg.remove(); } );
  });
});

function message (type, message, timeout) {
  timeout = timeout || 5000 + message.length * 20;
  var msg = messages.filter('.'+type);
  if( msg.length > 0 ){
    var container = msg.parent();
    var clone     = msg.clone();
    clone
      .appendTo( container )
      .hide()
      .fadeIn( 800 )
      .find('.content')
      .html( message );
    setTimeout(function(){
      clone.slideUp();
    }, timeout);
    container[0].scrollTop = container[0].scrollHeight;
  } else {
    console.warn( 'Unknown message type', arguments );
  }
}

function jq_element (type) {
  return $(document.createElement(type));
}

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
