var setup_tutorial = function(exports) {    
  exports.tutorial_running = false;

  var parts = {
    intro: [
      {
       html: "Welcome to the Room Allocation phase. <br /> This is a step by step tutorial. <br />You can use the commands bellow every message to <b>stop</b>/<b>pause</b>/<b>rewind</b> and <b>fast forward</b> the tutorial.<br /> Enjoy!",
       overlayOpacity: 0.8
       },
       {
        html: "Make sure this is you!",
        element: $('.my-profile'),
        overlayOpacity: 0.8,
        expose: true,
        position: 's'
      },
      {
        html: "You can send rommate requests from here",
        element: $('#searchBox'),
        overlayOpacity: 0.8,
        expose: true,
        position: 'e',
      },
      {
        html: "Make sure to check these options if they apply for you.",
        element: $('#options'),
        overlayOpacity: 0.8,
        expose: true,
        position: 'e',
      },
      {
        html: "You find your current roommates here.",
        element: $('#current-roommates'),
        overlayOpacity: 0.8,
        expose: true,
        position: 'w',
        onBeforeShow: function (element) {
          element.css('background', '#fff');
          if ($('.roommate-profile').length === 0) {
            $('#roommate_dummy').show();
          }
        },
        onBeforeHide: function (element) {
          $('#roommate_dummy').hide();
        }
      },
      {
        html: "If you have current roommate requests you will find them here.",
        element: $('#requests-received'),
        overlayOpacity: 0.8,
        expose: true,
        position: 'w',
        onBeforeShow: function (element) {
          element.css('background', '#fff');
          if ($('.request-recieved-profile').length === 0) {
            $('#request_recv_dummy').show();
          }
        },
        onBeforeHide: function (element) {
          $('#request_recv_dummy').hide();
        }
      },
      {
        html: "The requests that you send and are still without a response are here :)",
        element: $('#requests-sent'),
        overlayOpacity: 0.8,
        expose: true,
        position: 'w',
        onBeforeShow: function (element) {
          element.css('background', '#fff');
          if ($('.request-sent-profile').length === 0) {
            $('#request_sent_dummy').show();
          }
        },
        onBeforeHide: function (element) {
          $('#request_sent_dummy').hide();
        }
      },
      {
        html: "The total points that you and your roommate(s) have are displayed in this table.",
        element: $('.points'),
        position: 'e',
        overlayOpacity: 0.8,
        expose: true,
        onBeforeShow: function (element) {
          element.css('background', '#fff');
        }
      },
      {
        html: 'The first part shows the individual points per person. Representing the sum of points for the time spent at Jacobs and your College Spirit Point.',
        element: $('.individual-points'),
        position: 'e',
        overlayOpacity: 0
      },
      {
        html: 'The other part shows the points that you and your roommate(s) get depending on different majors, world regions and countries.',
        element: $('.bonus-points'),
        position: 'e',
        overlayOpacity: 0
      },
      {
        html: 'The last line shows your total points.',
        element: $('.total-points'),
        position: 'e',
        overlayOpacity: 0
      },
    ],
    floorplan: [
      {
        html: 'This is a floor plan. Use it to fill in your room choices',
        element: $('.level').eq(0),
        overlayOpacity: 0.8,
        position: 'n',
        expose: true
      },
      {
        html: 'Click on your prefered apartments. Take note that some apartments are <span class="room disabled" style="display:inline-block; padding:5px 10px; font-weight:bold;">disabled</span> and some others might already be <span class="room taken" style="display:inline-block; padding:5px 10px; font-weight:bold;">taken</span> in the previous rounds. <span class="room tall" style="display:inline-block; padding:5px 10px; font-weight:bold;">Tall rooms</span> are also highlighted on the floor plan',
        element: $('.room[apartment="'+$('.room[apartment]').eq(14).attr('apartment')+'"]'),
        overlayOpacity: 0.8,
        position: 'n',
        expose: true
      },
      {
        html: 'Once selected, your choices will be auto-completed here. When you are done, click <b>Save Changes</b>',
        element: $('.room-choices [name="choice[]"]'),
        overlayOpacity: 0.8,
        position: 'e',
        expose: true
      }
    ],
    floorplan_nordmetall: [
      {
        html: 'Select your desired apartments in your order of preference from the drop-down lists. When you are done, click <b>Save Changes</b>',
        element: $('.room-choices [name="choice[]"]'),
        overlayOpacity: 0.8,
        position: 'e',
        expose: true
      }
    ],
    end: [
      {
        html: 'Every time you click <b style="color:red">Save Changes</b>, your preferences are updated and a confirmation message will be shown on the top of the page. <br /><b style="color:red">Please take note of error messages!</b>',
        element: $('#choose_rooms'),
        overlayOpacity: 0.8,
        position: 'e',
        expose: true,
        onBeforeShow: function(element) {
          message("info", "College prefences updated!", 10 * 1000)
        },
        onBeforeHide: function () {
          $('.info.message').last().hide();
        }
      },
      {
        html: 'When in doubt, you can always re-start the tour from here',
        element: $('#beginTour'),
        overlayOpacity: 0.8,
        position: 'n',
        expose: true
      }
    ]
  };

  // manually bind parts together depending on if it is Nordmetall or not
  var tourdata = parts.intro;
  if ($('.floorPlan').length > 0) {
    tourdata = tourdata.concat(parts.floorplan);
  } else {
    tourdata = tourdata.concat(parts.floorplan_nordmetall);
  }
  tourdata = tourdata.concat(parts.end);

  var key_name = window.location.origin + window.location.pathname;
  var tutorial_opts = {
    axis: 'y',  // use only one axis prevent flickring on iOS devices
    autostart: true
  };
  if (window.localStorage) {
    if (window.localStorage[key_name]) {
      var storage = JSON.parse(window.localStorage[key_name]);
      if (storage.tutorial) {
        tutorial_opts.autostart = false;
      }
    }
    window.localStorage[key_name] = JSON.stringify({tutorial:true});
  }

  var tour = jTour(
    tourdata,
    Object.create(tutorial_opts, {
      onStart: function (current) {
        $("#college_choices_sort").sortable( "disable" );
      },
      onStop: function (current) {
        $("#college_choices_sort").sortable( "enable" );
      }
    })
  );

  exports.tutorial = tour;

  $('#beginTour').bind('click', function(){
    window.tutorial.start();
  });
}