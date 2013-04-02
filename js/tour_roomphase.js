var setup_tutorial = function(exports) {
    
    exports.tutorial_running = false;

    var tourdata = [
      {
        html: "Welcome to the College Choice phase. <br /> This is a step by step tutorial. <br />You can use the commands bellow every message to <b>stop</b>/<b>pause</b>/<b>rewind</b> and <b>fast forward</b> the tutorial.<br /> Enjoy!",
        overlayOpacity: 0.8
       },
       {
        html: "Choose any college you want to move.",
        element: $('.college-choice:nth(1)'),
        overlayOpacity: 0.8,
        expose: true,
        position: 'e'
       },
       {
        html: "Drag the college into a different position.",
        element: $('.college-choice:nth(2)'),
        overlayOpacity: 0.8,
        expose: true,
        position: 'e',

       },
       {
        html: "The first place shows your favorite college for next semester.",
        element: $('.college-choice:nth(0)'),
        overlayOpacity: 0.8,
        expose: true,
        position: 'e',
       },
       {
        html: "The last place your least favorite college.",
        element: $('.college-choice:nth(3)'),
        overlayOpacity: 0.8,
        expose: true,
        position: 'e',
       },
       {
        html: 'If you are not going to be on campus for a semester or going on exchange, make sure to tick the box',
        element: $('#exchange'),
        overlayOpacity: 0.8,
        position: 'e',
        expose: true
       },
       {
        html: 'Also if you are considering choosing a room on a quiet floor, tick here. This is by no means binding, it is just so the College Masters have an idea on the overall preferences',
        element: $('#quiet_zone'),
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