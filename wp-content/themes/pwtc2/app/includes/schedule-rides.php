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
        update_field('start_location_comment', get_field('start_location_comment'), $id);
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


// can cancel ride
function can_cancel_ride($post_id) {
    $user = wp_get_current_user();
    if(user_can($user,'edit_published_rides')) {
        return true;
    } elseif (in_array('ride_leader', (array) $user->roles)) {
        $leaders = get_field('ride_leaders', $post_id);
        foreach($leaders as $leader) {
            if($leader['ID'] == $user->ID) {
                return true;
            }
        }
    }

    return false;
}

function get_actual_ride_terrain($post_id=false) {
    if (get_field('attach_map', $post_id)) {
        $terrain = [];
        foreach (get_field('maps', $post_id) as $map) {
            $map_id = $map;
            $terrain = array_merge($terrain, get_field('terrain', $map_id));
        }
        return array_unique($terrain);
    }
    else {
        return get_field('terrain', $post_id);
    }
}

function get_actual_ride_length($post_id=false) {
    if (get_field('attach_map', $post_id)) {
         $length = null;
         foreach (get_field('maps', $post_id) as $map) {
            $map_id = $map;
            if ($length) {
                $length = min(get_field('length', $map_id), $length);
            } else {
                $length = get_field('length', $map_id);
            }
        }
        return $length;
    }
    else {
        return get_field('length', $post_id);
    }
}

function get_actual_ride_maxlength($post_id=false) {
    if (get_field('attach_map', $post_id)) {
         $maxlength = null;
         foreach (get_field('maps', $post_id) as $map) {
            $map_id = $map;
            if ($maxlength) {
                $maxlength = max(get_field('max_length', $map_id), $maxlength, get_field('length', $map_id));
            } 
            else {
                $maxlength = get_field('max_length', $map_id) ?: get_field('length', $map_id);
            }
        }
        return $maxlength;
    }
    else {
        return get_field('max_length', $post_id);
    }
}
