<?php
require_once __DIR__.'/app/acf.php';
require_once __DIR__.'/app/bootstrap.php';
require_once __DIR__.'/src/functions.php';

$twig = $container->get('twig.environment');

if( function_exists('acf_add_options_page') ) {
    $option_page = acf_add_options_page(array(
        'page_title' 	=> 'Theme General Settings',
        'menu_title' 	=> 'Theme Settings',
        'menu_slug' 	=> 'theme-general-settings',
        'capability' 	=> 'edit_posts',
        'redirect' 	=> false
    ));

    add_action('acf/init', function () {
        acf_update_setting('google_api_key', 'AIzaSyCBrImntaffCdMu_Gq6tgeGkxkHSVgnu6k');
    });
}

add_action( 'widgets_init', function(){
    register_widget( 'App\Sharethis' );
    register_widget( 'App\UpcomingRides' );
});

add_action('init', function() {
    register_post_type('ride_maps', [
        'public' => true,
        'labels'  => [
            'name' => 'Ride Maps',
            'singular_name' => 'Ride Map',
        ],
        'description' => "Bike Ride Maps",
        'menu_position' => 27,
        'supports' => [
            'title',
            'editor',
            'thumbnail',
        ],
        'has_archive' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-location-alt',
    ]);
    register_post_type('ride_template', [
        'public' => true,
        'labels'  => [
            'name' => 'Ride Templates',
            'singular_name' => 'Ride Template',
        ],
        'description' => "Ride Template",
        'menu_position' => 26,
        'supports' => [
            'title',
            'editor',
            'thumbnail',
        ],
        'has_archive' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-schedule',
    ]);
    register_post_type('scheduled_rides', [
        'public' => true,
        'labels'  => [
            'name' => 'Scheduled Rides',
            'singular_name' => 'Scheduled Ride',
        ],
        'description' => "Scheduled Bike Rides",
        'menu_position' => 27,
        'supports' => [
            'title',
            'editor',
            'thumbnail',
        ],
        'has_archive' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-calendar-alt',
    ]);

    add_filter('get_the_excerpt', function ($text) {
        return rtrim($text, '[&hellip;]') . '&hellip;';
    });
});

add_action('template_redirect', function(){
    $current_post_type = get_post_type();
    if(in_array($current_post_type, ['ride_maps', 'ride_templates']) && !is_user_logged_in()) {
        wp_safe_redirect(get_site_url());
    }
});

add_action('admin_enqueue_scripts', function () {
    wp_register_script('custom_wp_admin_css', get_template_directory_uri() . '/web/scripts/admin.js', ['jquery']);
    wp_enqueue_script('custom_wp_admin_css');
});


// run after ACF saves
add_action('acf/save_post', function ($post_id) {
    if(get_post_type() != "ride_template" || get_post_status() != 'publish')
    {
        return;
    }
    if(!isset($_POST['events']) || !$_POST['events'] || !is_array($_POST['events']))
    {
        return;
    }


    $ride_ids = [];
    foreach($_POST['events'] as $event)
    {
        $date = DateTime::createFromFormat('Y/n/j g:i a', $event.' '.get_field('time'));
        $id = wp_insert_post([
            'post_type' => 'scheduled_rides',
            'post_title' => get_the_title(),
            'post_status' => 'publish',
        ]);

        update_field('date', $date->format('Y-m-d H:i:s'), $id);
        update_field('type', get_field('type'), $id);
        update_field('pace', get_field('pace'), $id);
        update_field('description', get_field('description', false, false), $id);
        update_field('start_location', get_field('start_location', false, false), $id);
        update_field('ride_leaders', $leaders, $id);
        update_field('attach_map', get_field('attach_map'), $id);
        update_field('maps', get_field('maps'), $id);
        update_field('terrain', get_field('terrain'), $id);
        update_field('length', get_field('length'), $id);
        update_field('max_length', get_field('max_length'), $id);

        $leaders = [];
        foreach(get_field('ride_leaders') as $leader) {
            $leaders[] = $leader['ID'];
        }
    }

    update_field('schedule_rides', false);
    update_field('from', false);
    update_field('to', false);
}, 20);



function mytheme_excerpt_length() {
    return 50;
}
add_filter('excerpt_length','mytheme_excerpt_length');

// forms processing
add_action( 'wp_enqueue_scripts', function() {
    wp_localize_script('app', 'civi', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
});
function basic_info(){
    // update wordpress info
    $wordpress_user = get_userdata(get_current_user_id());
    if(!$wordpress_user) {
        echo "Must be logged in";
        die();
    }
    $first = $_POST['first'];
    $last = $_POST['last'];
    $email = $_POST['email'];
    $email_id = $_POST['email_id'];
    $email_location = $_POST['email_location'];
    $wordpress_update = [
        'ID' => $wordpress_user->ID,
        'first_name' => $first,
        'last_name' => $last,
    ];
    if($wordpress_user->user_email != $email) {
        $wordpress_update['user_email'] = $email;
    }
    $result = wp_update_user($wordpress_update);
    if(!$result) {
        echo "WordPress Error <hr />";
        var_dump($result);
    }

    // update civicrm infor
    $contact_id = $_POST['contact_id'];
    $result = civicrm_api3('Contact', 'create', array(
        'sequential' => 1,
        'id' => $contact_id,
        'first_name' => $first,
        'last_name' => $last,
    ));
    echo "Civi Contact Update <hr />";
    var_dump($result);

    $result = civicrm_api3('Email', 'create', array(
        'sequential' => 1,
        'contact_id' => $contact_id,
        'id' => $email_id,
        'email' => $email,
        'location_type_id' => $email_location,
    ));
    echo "Civi email update <hr />";
    var_dump($result);

    // update phones
    for($i = 0; $i < count($_POST['phone']); $i++) {
        $phone_id = $_POST['phone_id'][$i];
        $phone_number = $_POST['phone'][$i];
        $phone_location = $_POST['phone_location'][$i];
        $phone_type = $_POST['phone_type'][$i];
        $primary = $_POST['phone_primary'] == $phone_id ? 1 : 0;

        $result = civicrm_api3('Phone', 'create', array(
            'sequential' => 1,
            'contact_id' => $contact_id,
            'id' => $phone_id,
            'phone' => $phone_number,
            'location_type_id' => $phone_location,
            'is_primary' => $primary,
            'phone_type_id' => $phone_type,
        ));
        echo "Civi phone update <hr />";
        var_dump($result);
    }

    // update addressses
    for($i = 0; $i < count($_POST['address_type']); $i++) {
        $address_id = $_POST['address_id'][$i];
        $address_type = $_POST['address_type'][$i];
        $address_1 = $_POST['address_1'][$i];
        $address_2 = $_POST['address_2'][$i];
        $city = $_POST['address_city'][$i];
        $state = $_POST['address_state'][$i];
        $zip = $_POST['address_zip'][$i];
        $primary = $_POST['phone'][$i] ? 1 : 0;

        $result = civicrm_api3('Address', 'create', array(
            'sequential' => 1,
            'contact_id' => $contact_id,
            'id' => $address_id,
            'location_type_id' => $address_type,
            'street_address' => $address_1,
            'supplemental_address_1' => $address_2,
            'city' => $city,
            'state_province_id' => $state,
            'postal_code' => $zip,
            'is_primary' => $primary,
        ));
        echo "Civi address update <hr />";
        var_dump($result);
    }

    die();
}
add_action('wp_ajax_basic_info', 'basic_info');
add_action('wp_ajax_nopriv_basic_info', 'basic_info');