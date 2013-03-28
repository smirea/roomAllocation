/**
 * This file is a collection of shared function between multiple other javascript files
 */

console = console || {log:function(){}, warn:function(){}, error:function(){}};
var ajax_file = 'ajax.php';

var messages = $();
var message_timeout;

var global_ajax_handlers = {
  message: function handle_messages (json) {
    var types = [ 'error', 'warning', 'info', 'success' ];
    for (var i in types) {
      if (check_message_key(types[i], json)) {
        var value = json[types[i]];
        if ($.isArray(value)) {
          value = value.join('<br />');
        }
        message(types[i], value);
        delete json[types[i]];
        break;
      }
    }
  },
  rpc: function handle_rpc (json) {
    if (json.rpc) {
      eval(json.rpc);
      delete json.rpc;
    }
  }
};

$(function () {
  $('body').ajaxSuccess(function (event, xhr, settings, json) {
    if ($.isPlainObject(json)) {
      for (var key in global_ajax_handlers) {
        global_ajax_handlers[key](json);
      }
    }
  });

  $('body').append(
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

  messages = $('#message-info,#message-error,#message-warning,#message-success');
  $(document).on('click.close', '.message .close', function () {
    var $msg = $(this).parent();
    $msg.slideUp(600, function(){ $msg.remove(); });
  });
});

/**
 * Checks if the key of a specific object is valid message ruturn object.
 *  It mainly checks if the key exists and it is either an object, a non-empty stirng, a non-empty array or a number
 * @param {String} key
 * @param {Object} object
 * @return {Boolean}
 */
function check_message_key (key, object) {
  return object && object[key] !== undefined && (
    $.isNumeric(object[key]) ||
    $.isPlainObject(object[key]) ||
    (($.isArray(object[key]) || typeof object[key] == 'string') && 
      object[key].length > 0
    )
  );
}

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

function C (key, value) {
  if (value !== undefined) {
    configuration[key] = value;
  }
  return configuration[key];
}