<?php
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
        'menu_icon' => 'dashicons-calendar-alt',
    ]);

    add_filter('get_the_excerpt', function ($text) {
        return rtrim($text, '[&hellip;]') . '&hellip;';
    });
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
            'post_title' => get_the_title() . ' - ' . $date->format('F jS'),
            'post_status' => 'publish',
        ]);

        update_field('date', $date->getTimestamp(), $id);
        update_field('type', get_field('type'), $id);
        update_field('pace', get_field('pace'), $id);
        update_field('description', get_field('description', false, false), $id);

        $leaders = [];
        foreach(get_field('ride_leaders') as $leader) {
            $leaders[] = $leader['ID'];
        }
        update_field('ride_leaders', $leaders, $id);

        $length = null;
        $maxLength = null;
        $address = false;
        $terrain = [];
        $maps = [];

        foreach(get_field('maps') as $map) {
            $map_id = $map->ID;
            echo $map_id . "<br />";

            // set length to the lowest length
            if(!$length) {$length = get_field('length', $map_id); }
            else if($length && get_field('length', $map_id) < $length) { $length = get_field('length', $map_id); }

            // set max length to the highest max length
            if(!$maxLength) {$maxLength = get_field('max_length', $map_id); }
            else if($maxLength && get_field('max_length', $map_id) < $maxLength) { $maxLength = get_field('max_length', $map_id); }

            // set address to the first found address
            if(!$address && get_field('start_address_street', $map_id)) {
                update_field('start_address_street', get_field('start_address_street', $map_id), $id);
                update_field('start_address_unit', get_field('start_address_unit', $map_id), $id);
                update_field('start_address_state', get_field('start_address_state', $map_id), $id);
                update_field('start_address_city', get_field('start_address_city', $map_id), $id);
                update_field('start_address_zip', get_field('start_address_zip', $map_id), $id);
                update_field('start_location', get_field('start_location', $map_id), $id);
                $address = true;
            }

            $terrain = array_merge($terrain, get_field('terrain', $map_id));
            $maps = array_merge($maps, get_field('maps', $map_id));
        }


        update_field('terrain', $terrain, $id);
        update_field('length', $length, $id);
        update_field('max_length', $maxLength, $id);
        update_field('field_57bb66366797b', $maps, $id);// map links and files

        $ride_ids[] = $id;
    }

    update_field('schedule_rides', false);
    update_field('from', get_field('to'));
    update_field('to', false);
}, 20);
