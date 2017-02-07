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

        $leaders = [];
        foreach(get_field('ride_leaders') as $leader) {
            $leaders[] = $leader;
        }

        update_field('date', $date->format('Y-m-d H:i:s'), $id);
        update_field('type', get_field('type'), $id);
        update_field('pace', get_field('pace'), $id);
        update_field('description', get_field('description', false, false), $id);
        update_field('start_location', get_field('start_location', false, false), $id);
        update_field('ride_leaders', $leaders, $id);
        update_field('attach_map', get_field('attach_map'), $id);
        update_field('maps', get_field('maps', false, false), $id);
        update_field('terrain', get_field('terrain'), $id);
        update_field('length', get_field('length'), $id);
        update_field('max_length', get_field('max_length'), $id);
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

    $result = civicrm_api3('Email', 'create', array(
        'sequential' => 1,
        'contact_id' => $contact_id,
        'id' => $email_id,
        'email' => $email,
        'location_type_id' => $email_location,
    ));

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
    }

    echo "Account has been updated";

    die();
}
add_action('wp_ajax_basic_info', 'basic_info');
add_action('wp_ajax_nopriv_basic_info', 'basic_info');

function delete_household(){
    $relationship = $_POST['id'];
    $name = $_POST['name'];
    civicrm_initialize();
    $result = civicrm_api3('Relationship', 'delete', array(
        'sequential' => 1,
        'id' => $relationship,
    ));

    echo "$name has been removed from the household";

    die();
}
add_action('wp_ajax_delete_household', 'delete_household');
add_action('wp_ajax_nopriv_delete_household', 'delete_household');

function add_household(){
    civicrm_initialize();
    $wordpress_user = get_userdata(get_current_user_id());

    // current user household
    $result = civicrm_api3('contact', 'get', array(
        'sequential' => 1,
        'email' => $wordpress_user->user_email,
    ));
    $contact_id = $result['values'][0]['contact_id'];
    $result = civicrm_api3('Relationship', 'get', array(
        'sequential' => 1,
        'relationship_type_id' => 6,
        'contact_id_a' => $contact_id,
    ));

    if($result['values']) {
        $household_id = $result['values'][0]['contact_id_b'];
    }

    if(!isset($household_id) || !$household_id) {
        $result = civicrm_api3('Contact', 'create', array(
            'sequential' => 1,
            'contact_type' => "Household",
            'household_name' => $wordpress_user->user_firstname . " " . $wordpress_user->user_lastname,
            'primary_contact_id' => $contact_id,
        ));
        $household_id = $result['values'][0]['"id"'];
    }

    // new user contact
    $email = $_POST['email'];
    $result = civicrm_api3('contact', 'get', array(
        'sequential' => 1,
        'email' => $email,
    ));

    if(!$result['values']) {
        // create blank contact
        $result = civicrm_api3('Contact', 'create', array(
            'sequential' => 1,
            'contact_type' => "Individual",
            'first_name' => $email,
        ));
        $household_member_id = $result['values'][0]['id'];
        // add email
        $result = civicrm_api3('Email', 'create', array(
            'sequential' => 1,
            'contact_id' => $household_member_id,
            'email' => $email,
        ));
        //@todo add primary address
        // create user if one doesnt exist
        $user_id = username_exists($email);
        if (!$user_id and email_exists($email) == false) {
            $random_password = wp_generate_password(12, false);
            $user_id = wp_create_user($email, $random_password, $email);
        }
    } else {
        $household_member_id = $result['values'][0]['contact_id'];
    }
    
    // new user household
    $result = civicrm_api3('Relationship', 'create', array(
        'sequential' => 1,
        'contact_id_a' => $household_member_id,
        'contact_id_b' => $household_id,
        'relationship_type_id' => 7,
    ));

    echo "$email has been added to the household";

    die();
}
add_action('wp_ajax_add_household', 'add_household');
add_action('wp_ajax_nopriv_add_household', 'add_household');