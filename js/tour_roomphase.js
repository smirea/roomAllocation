var setup_tutorial = function(exports) {
    
    exports.tutorial_running = false;

    var tourdata = [
      {
        html: "Welcome to the Room Allocation phase. <br /> This is a step by step tutorial. <br />You can use the commands bellow every message to <b>stop</b>/<b>pause</b>/<b>rewind</b> and <b>fast forward</b> the tutorial.<br /> Enjoy!",
        overlayOpacity: 0.8
       },
       {
        html: "Make sure this is you!"
        element: $('.my-profile'),
        overlayOpacity: 0.8,
        expose: true,
        position: 's'
       }
       {
        html: "You find your current roommates here.",
        element: $('#current-roommates'),
        overlayOpacity: 0.8,
        expose: true,
        position: 'e',
        onBeforeShow: function (element) {
          if ($('.roommate-profile').length === 0) {
            $('#roommate_dummy').show();
          }
        },
        onAfterShow: function (element) {
          $('#roommate_dummy').hide();
        }
       },
       {
        html: "If you have current roommate requests you will find them here.",
        element: $('#requests-received'),
        overlayOpacity: 0.8,
        expose: true,
        position: 'e',
        onBeforeShow: function (element) {
          if ($('.request-recieved-profile').length === 0) {
            $('#request_recv_dummy').show();
          }
        },
        onAfterShow: function (element) {
          $('#request_recv_dummy').hide();
        }
       },
       {
        html: "The requests that you have sent and are still without a response are here :)",
        element: $('#requests-sent'),
        overlayOpacity: 0.8,
        expose: true,
        position: 'e',
        onBeforeShow: function (element) {
          if ($('.request-sent-profile').length === 0) {
            $('#request_sent_dummy').show();
          }
        },
        onAfterShow: function (element) {
          $('#request_sent_dummy').hide();
        }
       },
       {
        html: "The points that you and your roommate(s) have are displayed in this table.",
        element: $('.points'),
        overlayOpacity: 0.8,
        expose: true,
        position: 'e',
       },
       {
        html: 'The first part shows the individual points per person. Representing the sum of points for the time spent at Jacobs and your College Spirit Point.',
        element: $('.individual-points'),
        overlayOpacity: 0.8,
        position: 'e',
        expose: true
       },
       {
        html: 'The other part shows the points that you and your roommate(s) get depending on different majors, world regions and countries.',
        element: $('.bonus-points'),
        overlayOpacity: 0.8,
        position: 'e',
        expose: true
       },
       {
        html: 'The last line shows the points that you have in total to apply for rooms.',
        element: $('.total-points'),
        overlayOpacity: 0.8,
        position: 'e',
        expose: true
       },
       {
        html: "After each action your preferences are automatically shown and a confirmation message for storing will be shown on top.",
        overlayOpacity: 0.8,
        onBeforeShow: function(element) {
          message("info", "College prefences updated!")
        },
       },
       {
          html: 'When in doubt, you can always re-start the tour from here',
          element: $('#beginTour'),
          overlayOpacity: 0.8,
          position: 'w',
          expose: true
        }
    ];

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