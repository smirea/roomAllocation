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

$(function(){
  
  var $rooms = $('.room');
  
  $('<div />').qtip({
    overwrite : false,
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
  
  $('.small-group-id')
    .each(function(){
      var $clone  = $('#small-group-'+$(this).attr('group_id')).clone();
      $clone
        .appendTo($(this))
        .hide()
        .find('.group_id,.choice')
        .remove();
      $(this).data('expansion', $clone);
      $(this).data('expanded', false);
      $(this).css('cursor', 'pointer');
    })
    .bind('click.expand', function(){
      if( $(this).data('expanded') ){
        $(this).data('expansion').hide();
      } else {
        $(this).data('expansion').show();
      }
      $(this).data('expanded', !$(this).data('expanded'));
    });
  
  $('#menu a').eq(0).trigger('click');
  
}); 

function setView( link, $element ){
  $('.view').hide();
  $element.show();
  $(link).siblings().removeClass('selected');
  $(link).addClass('selected');
}