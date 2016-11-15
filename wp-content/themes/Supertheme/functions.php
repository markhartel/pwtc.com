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