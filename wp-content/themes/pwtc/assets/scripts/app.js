// sharethis
if(typeof(stLight) != "undefined") {
    stLight.options({
        publisher: "31abfba6-0978-4139-8479-d6e96f61d25f",
        doNotHash: true,
        doNotCopy: true,
        hashAddressBar: false
    });
}

// general
jQuery(function() {
    // load foundation
    jQuery(document).foundation();

    // fancybox
    jQuery('.fancybox').fancybox({
        scrolling: "no",
        fitToView: false
    });

    // fancybox
    jQuery('.fancybox-media').fancybox({
        openEffect  : 'none',
        closeEffect : 'none',
        helpers : {
            media : {}
        }
    });

    //slick
    jQuery('.slick').slick({
        autoplay: true,
        autoplaySpeed: 4000,
        fade: true,
        speed: 500,
        dots: true
    });

    // scroll to contact
    jQuery('.scroll-to-contact').click(function(e){
        e.preventDefault();
        jQuery('html, body').animate({
            scrollTop: jQuery(".footer form").offset().top
        }, 2000, function() {
            jQuery(".footer form [type='email']").focus();
        });
    });

    // prev days toggle
    var is_prev_days_shown = false;
    jQuery('.toggle-previous-days').click(function(){
        jQuery('.day.previous').toggle();
        is_prev_days_shown = !is_prev_days_shown;

        if(is_prev_days_shown) {
            jQuery(this).html("Hide previous days");
        } else {
            jQuery(this).html("Show hidden days");
        }
    });

    // scroll to top
    jQuery(window).on("scroll", function(){
        if(jQuery(document).scrollTop() > jQuery(window).height()) {
            jQuery('a.scroll-to-top').fadeIn();
        } else {
            jQuery('a.scroll-to-top').fadeOut();
        }
    });
    jQuery('a.scroll-to-top').on("click", function () {
        jQuery("html, body").animate({ scrollTop: 0 }, "slow");
        return false;
    });
});


// forms
jQuery(function() {
    jQuery('#basicInfo').on('submit', function(e){
        jQuery.ajax({
            url : civi.ajax_url,
            type: 'post',
            data: jQuery("#basicInfo").serialize(),
            success : function( response ) {
                response += "<p>The page will automatically refresh in a few seconds</p>";
                jQuery.fancybox(response);
                setTimeout(function(){ window.location.reload(); }, 3000);
            }
        });
        e.preventDefault();
    });

    jQuery('.household-delete .alert.button').on('click', function(e){
        jQuery.ajax({
            url : civi.ajax_url,
            type: 'post',
            data: jQuery(this).parent('div').next('form').serialize(),
            success : function( response ) {
                response += "<p>The page will automatically refresh in a few seconds</p>";
                jQuery.fancybox(response);
                setTimeout(function(){ window.location.reload(); }, 3000);
            }
        });
        e.preventDefault();
    });

    jQuery('#addHousehold').on('submit', function(e) {
        e.preventDefault();
    });
    jQuery('#addHousehold').on('formvalid.zf.abide', function(e){
        jQuery.ajax({
            url : civi.ajax_url,
            type: 'post',
            data: jQuery(this).serialize(),
            success : function( response ) {
                response += "<p>The page will automatically refresh in a few seconds</p>";
                jQuery.fancybox(response);
                setTimeout(function(){ window.location.reload(); }, 3000);
            }
        });
        e.preventDefault();
    })
});

// acf google maps
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
            mapTypeId	: google.maps.MapTypeId.ROADMAP
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
            map			: map
        });

        // add to array
        map.markers.push( marker );

        // if marker contains HTML, add it to an infoWindow
        if( $marker.html() )
        {
            // create info window
            var infowindow = new google.maps.InfoWindow({
                content		: $marker.html()
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