<?php
add_action('acf/save_post', function ($post_id) {
    if(get_post_type() != "ride_template" || get_post_status() != 'publish') {
        return;
    }
    if(!isset($_POST['events']) || !$_POST['events'] || !is_array($_POST['events'])) {
        return;
    }


    $ride_ids = [];
    foreach($_POST['events'] as $event) {
        $date = DateTime::createFromFormat('Y/n/j g:i a', $event.' '.get_field('time'));
        $id = wp_insert_post([
            'post_type' => 'scheduled_rides',
            'post_title' => get_the_title(),
            'post_status' => 'publish',
        ]);

        $leaders = [];
        foreach(get_field('ride_leaders') as $leader) {
            $leaders[] = $leader['ID'];
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