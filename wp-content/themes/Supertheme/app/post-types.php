<?php
add_action('init', function() {
    register_post_type('ride_maps', [
        'public' => true,
        'labels'  => [
            'name' => 'Ride Maps',
            'singular_name' => 'Ride Map',
        ],
        'description' => "Bike Ride Maps",
        'menu_position' => 28,
        'supports' => [
            'title',
            'editor',
            'thumbnail',
            'author',
        ],
        'capability_type' => ['ride', 'rides'],
        'map_meta_cap' => true,
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
        'menu_position' => 29,
        'supports' => [
            'title',
            'editor',
            'thumbnail',
            'author',
        ],
        'capability_type' => ['ride', 'rides'],
        'map_meta_cap' => true,
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
        'menu_position' => 30,
        'supports' => [
            'title',
            'editor',
            'thumbnail',
            'author',
        ],
        'capability_type' => ['ride', 'rides'],
        'map_meta_cap' => true,
        'has_archive' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-calendar-alt',
    ]);
    add_filter('wp_insert_post_data', function($data) {
        $types = array('ride_maps', 'ride_template', 'scheduled_rides');
        if (in_array($data['post_type'], $types)) {
            if (empty(trim($data['post_title']))) {
                $data['post_title'] = 'Untitled';
            }
        }
        return $data;
    });
});