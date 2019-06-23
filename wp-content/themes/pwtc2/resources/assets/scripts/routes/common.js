export default {
  init() {
      jQuery(function() {

          // foundation
          jQuery(document).foundation();

          // fancybox
          jQuery('.fancybox a, a.fancybox').fancybox({
          });

          // slick
          jQuery('.slick').slick({
              arrows: true,
              pauseOnHover: true,
              autoplay: true,
              autoplaySpeed: 4000,
              fade: true,
              speed: 500,
              dots: true,
          });

          // scroll to contact
          jQuery('.scroll-to-contact').click(function(e){
              e.preventDefault();
              jQuery('html, body').animate({
                  scrollTop: jQuery('.footer form').offset().top,
              }, 2000, function() {
                  jQuery('.footer form [type="email"]').focus();
              });
          });

          // prev days toggle
          var is_prev_days_shown = false;
          jQuery('.toggle-previous-days').click(function(){
              jQuery('.day.previous').toggle();
              is_prev_days_shown = !is_prev_days_shown;

              if(is_prev_days_shown) {
                  jQuery(this).html('Hide previous days');
              } else {
                  jQuery(this).html('Show hidden days');
              }
          });

          // scroll to top
          jQuery('.scroll-top').on('click', e => {
              jQuery('html, body').animate({ scrollTop: 0 }, 'slow');
              e.preventDefault();
              return false;
          });

          jQuery(window).scroll(() => {
              if (jQuery(document).scrollTop() > 400) {
                  jQuery('.scroll-top').fadeIn(250);
              }
              else {
                  jQuery('.scroll-top').fadeOut(250);
              }
          });

          // teams
          if(jQuery('#team_name'))
          {
              jQuery('#team_name').val(Date.now());
          }
      });

      // acf google maps
      /* eslint-disable */
      (function($) {

          /*
           *  new_map
           *
           *  This function will render a Google Map onto the selected jQuery element
           *
           *  @type	function
           *  @date	8/11/2013
           *  @since	4.3.0
           *
           *  @param	$el (jQuery element)
           *  @return	n/a
           */

          function new_map( $el ) {

              // var
              var $markers = $el.find('.marker');


              // vars
              var args = {
                  zoom		: 16,
                  center		: new google.maps.LatLng(0, 0),
                  mapTypeId	: google.maps.MapTypeId.ROADMAP,
              };


              // create map
              var map = new google.maps.Map( $el[0], args);


              // add a markers reference
              map.markers = [];


              // add markers
              $markers.each(function(){

                  add_marker( $(this), map );

              });


              // center map
              center_map( map );


              // return
              return map;

          }

          /*
           *  add_marker
           *
           *  This function will add a marker to the selected Google Map
           *
           *  @type	function
           *  @date	8/11/2013
           *  @since	4.3.0
           *
           *  @param	$marker (jQuery element)
           *  @param	map (Google Map object)
           *  @return	n/a
           */

          function add_marker( $marker, map ) {

              // var
              var latlng = new google.maps.LatLng( $marker.attr('data-lat'), $marker.attr('data-lng') );

              // create marker
              var marker = new google.maps.Marker({
                  position	: latlng,
                  map			: map,
              });

              // add to array
              map.markers.push( marker );

              // if marker contains HTML, add it to an infoWindow
              if( $marker.html() )
              {
                  // create info window
                  var infowindow = new google.maps.InfoWindow({
                      content		: $marker.html(),
                  });

                  // show info window when marker is clicked
                  google.maps.event.addListener(marker, 'click', function() {

                      infowindow.open( map, marker );

                  });
              }

          }

          /*
           *  center_map
           *
           *  This function will center the map, showing all markers attached to this map
           *
           *  @type	function
           *  @date	8/11/2013
           *  @since	4.3.0
           *
           *  @param	map (Google Map object)
           *  @return	n/a
           */

          function center_map( map ) {

              // vars
              var bounds = new google.maps.LatLngBounds();

              // loop through all markers and create bounds
              $.each( map.markers, function( i, marker ){

                  var latlng = new google.maps.LatLng( marker.position.lat(), marker.position.lng() );

                  bounds.extend( latlng );

              });

              // only 1 marker?
              if( map.markers.length == 1 )
              {
                  // set center of map
                  map.setCenter( bounds.getCenter() );
                  map.setZoom( 16 );
              }
              else
              {
                  // fit to bounds
                  map.fitBounds( bounds );
              }

          }

          /*
           *  document ready
           *
           *  This function will render each map when the document is ready (page has loaded)
           *
           *  @type	function
           *  @date	8/11/2013
           *  @since	5.0.0
           *
           *  @param	n/a
           *  @return	n/a
           */
            // global var
          var map = null;

          $(document).ready(function(){

              $('.acf-map').each(function(){

                  // create map
                  map = new_map( $(this) );

              });

          });

      })(jQuery);
      /* eslint-enable */
  },
  finalize() {
    // JavaScript to be fired on all pages, after page specific JS is fired
  },
};
