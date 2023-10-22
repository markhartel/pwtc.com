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

function can_view_signups($post_id) {
    $user = wp_get_current_user();
    if (user_can($user,'edit_published_rides')) {
        return true;
    } elseif (in_array('statistician', (array) $user->roles)) {
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

function get_actual_ride_maps($post_id=false) {
    if (get_field('attach_map', $post_id)) {
        $maps = [];
        foreach (get_field('maps', $post_id) as $map) {
            $map_id = $map;
            $raw_map = get_field('maps', $map_id);
            $raw_map[0]['title'] = $map->post_title;
            $maps = array_merge($maps, $raw_map);
        }
        return $maps;
    }
    else {
        return false;
    }
}

// Fetch the ride's description, break it into tokens delemited by whitespace
// and look for strings that start with "http://" or "https://". Convert those
// strings to HTML links using the following translation rules:
// 1) http://foo.bar.com becomes <a href="http://foo.bar.com">http://foo.bar.com</a>
// 2) http://foo.bar.com|foobar becomes <a href="http://foo.bar.com">foobar</a>
// 3) http://foo.bar.com|foobar|. becomes <a href="http://foo.bar.com">foobar</a>.
function convert_ride_desc_addr_to_link($post_id=false) {
    $message = get_field('description', $post_id);
    $desc = wp_kses($message, array('br' => array(), 'em' => array(), 'strong' => array()));
    $desc2 = "";
    $tok = strtok($desc, " \n\t\r");
    while ($tok !== false) {
        if (0 === strpos($tok, 'http://') or 0 === strpos($tok, 'https://')) {
            $idx = strpos($tok, '<');
            if ($idx !== false) {
	            $link = substr($tok, 0, $idx);
	            $rem = substr($tok, $idx);
	            $tok = $link;
            }
            else {
	            $rem = "";
            }
            $strings = explode("|", $tok, 3);
            $ref = $strings[0];
            $label = $ref;
            $end = "";
            if (count($strings) > 1) {
                if (strlen($strings[1]) > 0) {
                    $label = str_replace("_", " ", $strings[1]);
                }
                if (count($strings) > 2) {
                    if (strlen($strings[2]) > 0) {
                        $end = $strings[2];
                    }
                }
            }
            $desc2 .= '<a href="' . $ref . '" target="_blank">' . $label . '</a>' . $end . $rem;
        }
        else {
            $desc2 .= $tok;
        }
        $desc2 .= " ";
        $tok = strtok(" \n\t\r");
    }
    return $desc2;
}

