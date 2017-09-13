<?php

/*
Returns an array of arrays that contains the membership list. 
The interor array contains a membership record structured thus:
array[0] - member ID (string)
array[1] - first name (string)
array[2] - last name (string)
array[3] - expiration date (string with PHP date format 'Y-m-d')
*/
function pwtc_mileage_fetch_membership() {
/*
    $users = get_users();
    $users_array = array();
    foreach ( $users as $item ) {
        $firstname = $item->user_firstname;
        $lastname = $item->user_lastname;
        $memberid = get_field('rider_number', 'user_' . $item->ID);
        $expirdate = get_field('expir_date', 'user_' . $item->ID);
        array_push($users_array, array($memberid, $firstname, $lastname, $expirdate));
    }
    return $users_array;
*/
    return array();
}

/*
Returns a string that contains the member ID of the logged on user.
(Throws an exception if the user is not logged on or his member ID is not set.)
*/
function pwtc_mileage_get_member_id() {
    $id = null;
    $current_user = wp_get_current_user();
    if ( 0 == $current_user->ID ) {
        throw new Exception('notloggedin');
    }
    else {
        $test_date = PwtcMileage::get_date_for_expir_check();
        $result = PwtcMileage_DB::fetch_riders_by_name(trim($current_user->user_lastname), 
            trim($current_user->user_firstname), $test_date);
        $count = count($result);
        if ($count == 0) {
            throw new Exception('idnotfound');
        }
        else if ($count > 1) {
            throw new Exception('multidfound');
        }
        $id = $result[0]['member_id'];
    }
    return $id;
}

/*
Returns an array of arrays that contains the posted rides without ridesheets. 
The interor array contains a posted ride record structured thus:
array[0] - post ID (string)
array[1] - title (string)
array[2] - start date (string with PHP date format 'Y-m-d')
array[3] - post guid (url string)
*/
function pwtc_mileage_fetch_posts($select_sql, $lookback_date) {
    global $wpdb;
    //$ride_post_type = 'rideevent';
    //$ride_date_metakey = 'start_date';
    $ride_post_type = 'scheduled_rides';
    $ride_date_metakey = 'date';
    $sql_stmt = $wpdb->prepare(
        'select p.ID, p.post_title, date_format(m.meta_value, %s) as start_date, p.guid' . 
        ' from ' . $wpdb->posts . ' as p inner join ' . $wpdb->postmeta . 
        ' as m on p.ID = m.post_id where p.post_type = %s and p.post_status = \'publish\'' . 
        ' and m.meta_key = %s and (cast(m.meta_value as date) between %s and curdate())' . 
        ' and p.ID not in (' . $select_sql . ') order by m.meta_value', 
        '%Y-%m-%d', $ride_post_type, $ride_date_metakey, $lookback_date);
    $results = $wpdb->get_results($sql_stmt, ARRAY_N);
    return $results;
}

/*
Returns the guid of the post. 
(Return a null if the post cannot be found.)
*/
function pwtc_mileage_fetch_post_guid($post_id) {
    $post = get_post($post_id);
    $guid = null;
    if ($post !== null) {
        $guid = $post->guid;
    }
    return $guid;
}

/*
Returns an array that contains the rider ids of the ride leaders of the posted ride. 
*/
function pwtc_mileage_fetch_ride_leader_ids($post_id) {
    $leaders_array = array();

    $leaders = get_field('ride_leaders', $post_id);
    if ($leaders) {
        //pwtc_mileage_write_log($leaders);
        foreach ($leaders as $leader) {
            $fname = $leader['user_firstname'];
            $lname = $leader['user_lastname'];
            $test_date = PwtcMileage::get_date_for_expir_check();
            $result = PwtcMileage_DB::fetch_riders_by_name(trim($lname), trim($fname), $test_date);
            if (count($result) == 1) {
                $id = $result[0]['member_id'];
                array_push($leaders_array, $id);
            }
        }
    }

/*
    $leaders = get_field('ride_leader', $post_id);
    if ($leaders) {
        //pwtc_mileage_write_log($leaders);
        foreach ($leaders as $leader) {
            $riderid = get_field('rider_number', $leader->ID);
            array_push($leaders_array, $riderid);
        }
    }
*/
    return $leaders_array;
}

/*
Returns an array that contains the names of the ride leaders of the posted ride. 
*/
function pwtc_mileage_fetch_ride_leader_names($post_id) {
    $leaders_array = array();

    $leaders = get_field('ride_leaders', $post_id);
    if ($leaders) {
        pwtc_mileage_write_log($leaders);
        foreach ($leaders as $leader) {
            $fname = $leader['user_firstname'];
            $lname = $leader['user_lastname'];
 	        //$fname = get_user_meta($leader->ID, 'first_name', true);
 	        //$lname = get_user_meta($leader->ID, 'last_name', true);
            $name = $fname . ' ' . $lname;
            array_push($leaders_array, $name);
        }
    }

/*    
    $leaders = get_field('ride_leader', $post_id);
    if ($leaders) {
        //pwtc_mileage_write_log($leaders);
        foreach ($leaders as $leader) {
            $name = $leader->post_title;
            array_push($leaders_array, $name);
        }
    }
*/
    return $leaders_array;
}

function pwtc_mileage_create_stat_role() {
    $stat = get_role('statistician');
    if ($stat === null) {
        //$subscriber = get_role('subscriber');
        //$stat = add_role('statistician', 'Statistician', $subscriber->capabilities);
        $stat = add_role('statistician', 'Statistician');
        pwtc_mileage_write_log('PWTC Mileage plugin added statistician role');
    }
    if ($stat !== null) {
        $stat->add_cap(PwtcMileage::VIEW_MILEAGE_CAP);
        $stat->add_cap(PwtcMileage::EDIT_MILEAGE_CAP);
        $stat->add_cap(PwtcMileage::EDIT_RIDERS_CAP);
        $stat->add_cap(PwtcMileage::DB_OPS_CAP);
        pwtc_mileage_write_log('PWTC Mileage plugin added capabilities to statistician role');
    }    
}

function pwtc_mileage_remove_stat_role() {
    $users = get_users(array('role' => 'statistician'));
    if (count($users) > 0) {
        $stat = get_role('statistician');
        $stat->remove_cap(PwtcMileage::VIEW_MILEAGE_CAP);
        $stat->remove_cap(PwtcMileage::EDIT_MILEAGE_CAP);
        $stat->remove_cap(PwtcMileage::EDIT_RIDERS_CAP);
        $stat->remove_cap(PwtcMileage::DB_OPS_CAP);
        pwtc_mileage_write_log('PWTC Mileage plugin removed capabilities from statistician role');
    }
    else {
        $stat = get_role('statistician');
        if ($stat !== null) {
            remove_role('statistician');
            pwtc_mileage_write_log('PWTC Mileage plugin removed statistician role');
        }
    }
}

if (!function_exists('pwtc_mileage_write_log')) {
    function pwtc_mileage_write_log ( $log )  {
        if ( true === WP_DEBUG ) {
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ) );
            } else {
                error_log( $log );
            }
        }
    }
}