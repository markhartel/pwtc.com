<?php
require_once __DIR__.'/app/acf.php';
require_once __DIR__.'/app/bootstrap.php';
require_once __DIR__.'/src/functions.php';
require_once __DIR__.'/app/options-page.php';
require_once __DIR__.'/app/widgets.php';
require_once __DIR__.'/app/roles.php';
require_once __DIR__.'/app/excerpts.php';
require_once __DIR__.'/app/post-types.php';
require_once __DIR__.'/app/schedule-rides.php';
require_once __DIR__.'/app/schedule-column.php';
require_once __DIR__.'/app/woocommerce.php';

// add civicrm membership functionality to account page
$membership = new \App\Membership();

// add admin styles and scripts
add_action('admin_enqueue_scripts', function () {
    wp_register_script('custom_wp_admin_css', get_template_directory_uri() . '/web/scripts/admin.js', ['jquery']);
    wp_enqueue_script('custom_wp_admin_css');
});

// allow all users to update their profile pic
add_action('init', function() {
    update_option('basic_user_avatars_caps', true);
});

// forms processing
add_action( 'wp_enqueue_scripts', function() {
    wp_localize_script('app', 'civi', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
});

// redirect users to home page after login
add_filter('login_redirect', function ($redirect_to, $request, $user){
    return home_url();
}, 10, 3 );

add_filter( 'login_redirect', function ($redirect_to, $request, $user) {
    return home_url();
}, 10, 3 );

/**
 * Returns the timezone string for a site, even if it's set to a UTC offset
 *
 * Adapted from http://www.php.net/manual/en/function.timezone-name-from-abbr.php#89155
 *
 * @return string valid PHP timezone string
 */
function supertheme_get_timezone_string() {

    // if site timezone string exists, return it
    if ( $timezone = get_option( 'timezone_string' ) )
        return $timezone;

    // get UTC offset, if it isn't set then return UTC
    if ( 0 === ( $utc_offset = get_option( 'gmt_offset', 0 ) ) )
        return 'UTC';

    // adjust UTC offset from hours to seconds
    $utc_offset *= 3600;

    // attempt to guess the timezone string from the UTC offset
    if ( $timezone = timezone_name_from_abbr( '', $utc_offset, 0 ) ) {
        return $timezone;
    }

    // last try, guess timezone string manually
    $is_dst = date( 'I' );

    foreach ( timezone_abbreviations_list() as $abbr ) {
        foreach ( $abbr as $city ) {
            if ( $city['dst'] == $is_dst && $city['offset'] == $utc_offset )
                return $city['timezone_id'];
        }
    }

    // fallback to UTC
    return 'UTC';
}

add_filter('jpeg_quality', function() { return 100; });