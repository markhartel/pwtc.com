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


        add_action('acf/init', function () {
            acf_update_setting('google_api_key', 'AIzaSyCBrImntaffCdMu_Gq6tgeGkxkHSVgnu6k');
        });
}

add_action( 'widgets_init', function(){
    register_widget( 'App\Sharethis' );
});

add_action('init', function() {
    register_post_type('Ride Maps', [
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
        'menu_icon' => 'dashicons-schedule',
    ]);
    register_post_type('Scheduled Rides', [
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
        'menu_icon' => 'dashicons-calendar-alt',
    ]);

    add_filter('get_the_excerpt', function ($text) {
        return rtrim($text, '[&hellip;]') . '&hellip;';
    });
});

