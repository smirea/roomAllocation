/***************************************************************************\
    This file is part of RoomAllocation.

    RoomAllocation is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    RoomAllocation is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with RoomAllocation.  If not, see <http://www.gnu.org/licenses/>.
\***************************************************************************/

var MAX_ROOMS_CHOICES = 9;

var sendResponse;

/**
 * contains all available remote-procedural calls
 *  a.k.a. the interface available to the backend
 */
var RPC = {
  reload  : function(){ window.location.reload(); },
  updatePoints  : function(){
    
  }
};

(function($){
  
  var $searchBox;
  var $search;
  var $eid;
  var $addRoommate;
  var $freshman;
  var $loading;
  
  $(function(){
    alter_jquery_ui();
    set_variables();
    init_roommate_search();
    init_freshman_toggle();
    init_select_rooms();
    add_floorplan_events();
    register_global_ajax_handlers();
    setup_college_chooser();
    if (jQuery.isFunction(window.setup_tutorial)) {
      window.setup_tutorial(window);
    }
    bulk_events();
    // refresh after 20 minutes so you don't get a session timeout
    setTimeout( RPC.reload, 20 * 60 * 1000 );
  });



  var setup_college_chooser = function() {
    function evalCollegeChoice (e, ui) {
        if (window.tutorial_running) {
          window.tutorial.next();
          console.log(ui);
        }
        var choices = $("#college_choices_sort").sortable("toArray");
        for(var i = 0; i < choices.length; i++) {
          choices[i] = choices[i].substr(7);
        }
        var exchange = $("#exchange_checkbox").is(":checked") ? 1 : 0;
        var quiet = $("#quiet_zone_checkbox").is(":checked") ? 1 : 0;
        $.get(ajax_file, { 
          'action' : 'setCollegeChoices',
          'choices' : choices,
          'exchange' : exchange,
          'quiet' : quiet
        }, function(data){
          // TODO: implement handle response
        });
    }
    $("#college_choices_sort").sortable({
      placeholder: "ui-state-highlight",
      update: evalCollegeChoice,
      change: function college_sort (event, ui) {
        ui.item.siblings('.college-choice').each(function () {
          $(this).find('.number').html(
            $(this).prevAll('li').not(ui.item).length + 1
          );
        });
        ui.item.find('.number').html(
          ui.placeholder.prevAll('.college-choice').not(ui.item).length + 1
        );
      }
    }); 
    $("#college_choices_sort").disableSelection();
    $("#exchange_checkbox").on("change", evalCollegeChoice);
    $("#quiet_zone_checkbox").on("change", evalCollegeChoice);
  }

  var bulk_events = function(){
    var checkbox_selector = '.college-additional-options input[type="checkbox"]';
    $(document).on('change', checkbox_selector, function () {
      if ($(this).attr('checked') === 'checked') {
        $(this).parent().addClass('checked');
      } else {
        $(this).parent().removeClass('checked');
      }
    });
    $(checkbox_selector+':checked').parent().addClass('checked');
  }
  
  var init_select_rooms = function(){
    
    $('#select-rooms').bind('submit.selectRooms', function(e){
      var variables = {
        action  : 'selectRooms'
      };
      $(this).find('select').each(function(){
        variables[$(this).attr('name')] = $(this).val();
      });
      $.get( ajax_file, variables);
      return false;
    });
    
  };
  
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
    $freshman     = $('#toggle_freshman');
    
    $loading      = jq_element('img');
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
    if( $search.data("autocomplete") ){
      $search.data("autocomplete").menu.options.selected = function(event, data) {
        $search.focus();
        $search.autocomplete('close');
        $eid.val( data.item.data('item.autocomplete').full.eid );
        return false;
      };
    }
    
    $searchBox.bind('submit.addRoommate', function(){
      $addRoommate.hide();
      $loading.show();
      $.get( ajax_file, {
        action  : 'addRoommate',
        eid     : $eid.val()
      }, function( data ){
        $loading.hide();
        $addRoommate.show();
        if( data.result ){
          var elem = jq_element('div').html(data.result).unwrap();
          $('#requests-sent').find('.none').slideUp().end().append( elem );
          elem.hide().slideDown();
        }
      });
      return false;
    });
  };
  
  var init_freshman_toggle = function(){
    if( $freshman.length > 0 ){
      $freshman.bind('click', function(){
        if( $(this).attr('checked') == 'checked' )
          $.get( ajax_file, {action:'addFreshman'} );
        else
          $.get( ajax_file, {action:'removeFreshman'} );
      });
    }
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
      if( data.result ){
        var $face = $('#face-eid-'+eid);
        if( $face.siblings(':visible').length == 0 ){
          $face.parent().find('.none').slideDown();
        }
        $face.fadeOut(800);
      }
    });
  }

  var register_global_ajax_handlers = function register_global_ajax_handlers () {
    $.extend(global_ajax_handlers, {
      points: function handle_points (json) {
        if (json.points) {
          $('#total-points').html(json.points);
          delete json.points;
        }
      },
      roommates: function handle_roommates (json) {
        if ($.isArray(json.roommates)) {
          var $cr = $('#current-roommates');
          if (json.roommates.length > 0) {
            $cr.append( json.roommates.join("\n") );
            $cr.find('.none').slideUp();
          } else {
            $cr.find('.none').slideDown();
          }
          delete json.roommates;
        }
      }
    });
  };

  var add_floorplan_events = (function(){
    
    var $selection          = jq_element('div');
    var $current_apartment  = $();
    var $rooms              = $();
    var current_choice      = null;
    var no_choice           = '   ';
    var close_timeout       = null;
    
    return function(){
      create_selection();
      $rooms
        .bind('mouseover mouseout', function(){
          var $rooms = get_apartment( $(this) );
          $rooms.toggleClass('selected');
        })
        .bind('click.selectRoom', function(){
          $current_apartment = get_apartment( $(this) );
          current_choice = $current_apartment
                            .map(function(i,v){ return v.id.slice(5); })
                            .get()
                            .join(',');
        });
      $('#choose_rooms')
        .bind('click.chooseRooms', function(){
          var choices = $('.room-choices [name="choice[]"]')
                          .map(function(i,v){ return $(this).val(); })
                          .get();
          $.get( ajax_file, {
            action  : 'chooseRooms',
            choices : choices
          });
        });
    };
    
    function create_selection(){
      $rooms = $('.room:not(.taken,.disabled)');
      var h = '';
      for( var i=0; i<MAX_ROOMS_CHOICES; ++i ){
        h += '<label class="choice">\
                <span class="title">Option '+(i+1)+'</span>\
                <input type="button" value="'+no_choice+'" id="room-choice-'+i+'" />\
              </label>';
      }
      $selection
        .attr( 'id',  'apartment-selection' )
        .html( h )
        //.appendTo( 'body' )
        .find('.choice input')
        .bind('click.setChoice', function(){
          if( current_choice ){
            if( $(this).val() !== no_choice ){
              var old_apartment = $(this)
                                      .val()
                                      .split(',')
                                      .map(function(v){return '#room-'+v;})
                                      .join(',');
              $(old_apartment).removeClass('chosen');
            }
            $current_apartment.addClass( 'chosen' );
            $(this).val( current_choice );
            $('#input-'+$(this).attr('id')).val( current_choice );
            $selection.dialog('close');
            $current_apartment  = $();
            current_choice      = null;
          } else {
            $selection.dialog('open');
          }
        });

      $selection.dialog({
        modal     : true,
        title     : 'Which option should it be',
        show      : 'slide',
        autoOpen  : false,
        width     : 350,
        open      : function( e, ui ){
          $selection
            .find('input')
            .each(function(){
              var val = $('#input-'+$(this).attr('id')).val();
              var val = val != '' ? val : no_choice;
              $(this).val( val );
            });
        }
      });
      
      $rooms.bind('click.showDialog', function(){
        $selection.dialog('open');
      });
    }
  })();
  
})( jQuery );