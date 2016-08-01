<?php
require_once __DIR__.'/app/bootstrap.php';
require_once __DIR__.'/src/functions.php';

if( function_exists('acf_add_options_page') ) {
    $option_page = acf_add_options_page(array(
        'page_title' 	=> 'Theme General Settings',
        'menu_title' 	=> 'Theme Settings',
        'menu_slug' 	=> 'theme-general-settings',
        'capability' 	=> 'edit_posts',
        'redirect' 	=> false
    ));
}

add_action( 'widgets_init', function(){
    register_widget( 'App\Sharethis' );
});

add_action('init', function() {
    register_post_type('rides', [
        'public' => true,
        'labels'  => [
            'name' => 'Rides',
            'singular_name' => 'Ride',
        ],
        'description' => "Bike Ride",
        'menu_position' => 26,
        'supports' => [
            'title',
            'editor',
            'thumbnail',
        ],
        'has_archive' => true,
        'menu_icon' => 'dashicons-location-alt',
    ]);
    register_post_type('ride_schedules', [
        'public' => true,
        'labels'  => [
            'name' => 'Ride Schedules',
            'singular_name' => 'Ride Schedule',
        ],
        'description' => "Bike Ride Schedule",
        'menu_position' => 27,
        'supports' => [
            'title',
            'editor',
            'thumbnail',
        ],
        'has_archive' => true,
        'menu_icon' => 'dashicons-calendar-alt',
    ]);
});
