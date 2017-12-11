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
Given the user's email address, this function looks up the CiviCRM contact record 
and returns it's rider ID. Null is returned if no rider ID is set or is valid. Before this
function is used you must first initialize the CiviCRM API by calling civicrm_initialize().
*/
function pwtc_mileage_fetch_civi_member_id($email) {
    $member_id = null;
    if (function_exists('civicrm_api3')) {
        $result = civicrm_api3('contact', 'get', array(
            'sequential' => 1,
            'contact_type' => 'Individual',
            'email' => $email
        ));
        if ($result['values']) {
            $contact_id = $result['values'][0]['contact_id'];
            $result = civicrm_api3('CustomValue', 'get', array(
                'sequential' => 1,
                'entity_id' => $contact_id,
                'return.custom_5' => 1
            ));
            if ($result['values']) {
                $member_id = trim($result['values'][0]['latest']);
                if (strlen($member_id) == 0) {
                    $member_id = null;    
                }
                else {
                    if (PwtcMileage::validate_member_id_str($member_id)) {
                        $result = PwtcMileage_DB::fetch_rider($member_id); 
                        if (count($result) == 0) {
                            $member_id = null;
                        } 
                    } 
                    else {
                        $member_id = null;
                    }   
                }
            }
        }
    }
    return $member_id;
}

/*
Given a CiviCRM contact ID, this function looks up the contact record 
and uses it's information to insert a new rider into the mileage database and
assign a rider ID. Should a rider ID already be assigned to the contact record
then the existing rider's information is only updated. Returns null if
successful, otherwise returns an error message string. Before this
function is used you must first initialize the CiviCRM API by calling civicrm_initialize().
*/
function pwtc_mileage_civi_update_rider($contact_id, $update_only=false) {
    $errormsg = null;
    if (function_exists('civicrm_api3')) {
        $result = civicrm_api3('contact', 'get', array(
            'sequential' => 1,
            'id' => $contact_id
        ));
        if ($result['values']) {
            $firstname = trim($result['values'][0]['first_name']);
            $lastname = trim($result['values'][0]['last_name']);
            $memberships = civicrm_api3('Membership', 'get', array(
                'sequential' => 1,
                'contact_id' => $contact_id,
            ));
            $expdate = ""; 
            if ($memberships['values']) {
                foreach ($memberships['values'] as $membership) {
                    if (isset($membership['end_date'])) {
                        if (strlen($expdate) == 0) {
                            $expdate = $membership['end_date'];
                        }
                        else {
                            $expdate = max($expdate, $membership['end_date']);
                        }
                    }
                }
            } 
            if (strlen($expdate) == 0) {  
                $expdate = date('Y-m-d', current_time('timestamp'));
            }       
            if (PwtcMileage::validate_date_str($expdate) and 
                PwtcMileage::validate_member_name_str($firstname) and 
                PwtcMileage::validate_member_name_str($lastname)) {
                $result = civicrm_api3('CustomValue', 'get', array(
                    'sequential' => 1,
                    'entity_id' => $contact_id,
                    'return.custom_5' => 1
                ));
                $member_id = "";
                if ($result['values']) {
                    $member_id = trim($result['values'][0]['latest']);
                }
                if (strlen($member_id) == 0) {
                    if (!$update_only) {
                        $member_id = PwtcMileage_DB::gen_new_member_id();
                        if (PwtcMileage::validate_member_id_str($member_id)) {
                            $result = PwtcMileage_DB::fetch_rider($member_id); 
                            if (count($result) == 0) {
                                $status = PwtcMileage_DB::insert_rider(
                                    $member_id, $lastname, $firstname, $expdate);
                                if (false === $status or 0 === $status) {
                                    $errormsg = "Cannot insert new rider ID " . $member_id . " for contact " . $contact_id . " into mileage DB.";
                                }
                                else {
                                    $result = civicrm_api3('CustomValue', 'create', array(
                                        'sequential' => 1,
                                        'entity_id' => $contact_id,
                                        'custom_5' => $member_id
                                    ));
                                }
                            }
                            else {
                                $errormsg = "Generated rider ID " . $member_id . " for contact " . $contact_id . " already in mileage DB.";
                            }
                        }
                        else {
                            $errormsg = "Generated rider ID " . $member_id . " for contact " . $contact_id . " not valid.";
                        }
                    }
                }
                else {
                    if (PwtcMileage::validate_member_id_str($member_id)) {
                        $result = PwtcMileage_DB::fetch_rider($member_id); 
                        if (count($result) == 1) {
                            PwtcMileage_DB::insert_rider(
                                $member_id, $lastname, $firstname, $expdate);
                        }
                        else {
                            $errormsg = "Rider ID " . $member_id . " for contact " . $contact_id . " not found in mileage DB.";
                        }
                    }
                    else {
                        $errormsg = "Rider ID " . $member_id . " for contact " . $contact_id . " not valid.";
                    }
                }
            }
            else {
                $errormsg = "Rider detail info for contact " . $contact_id . " not valid.";
            }
        }
        else {
            $errormsg = "Contact " . $contact_id . " not found.";  
        }
    }
    else {
        $errormsg = "CiviCRM not installed.";
    }
    return $errormsg;
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
        if (function_exists('civicrm_initialize')) {
            civicrm_initialize();
            $id = pwtc_mileage_fetch_civi_member_id($current_user->user_email);
            if (!$id) {
                throw new Exception('idnotfound');
            }
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
    }
    return $id;
}

/*
Returns an array of arrays that contains the posted rides without ridesheets. 
The interor array contains a posted ride record structured thus:
array[0] - post ID (string)
array[1] - title (string)
array[2] - start date (string with PHP date format 'Y-m-d')
*/
function pwtc_mileage_fetch_posted_rides($start_date, $end_date, $exclude_sql="") {
    global $wpdb;
    //$ride_post_type = 'rideevent';
    //$ride_date_metakey = 'start_date';
    $ride_post_type = 'scheduled_rides';
    $ride_date_metakey = 'date';
    $select_sql = "";
    if ($exclude_sql) {
        $select_sql = " and p.ID not in (" . $exclude_sql . ")";
    }
    $sql_stmt = $wpdb->prepare(
        'select p.ID, p.post_title, date_format(m.meta_value, %s) as start_date' . 
        ' from ' . $wpdb->posts . ' as p inner join ' . $wpdb->postmeta . 
        ' as m on p.ID = m.post_id where p.post_type = %s and p.post_status = \'publish\'' . 
        ' and m.meta_key = %s and (cast(m.meta_value as date) between %s and %s)' . 
        $select_sql . ' order by m.meta_value', 
        '%Y-%m-%d', $ride_post_type, $ride_date_metakey, $start_date, $end_date);
    $results = $wpdb->get_results($sql_stmt, ARRAY_N);
    return $results;
}

/*
Returns an array of arrays that contains the posted ride. 
The interor array contains a posted ride record structured thus:
array[0] - post ID (string)
array[1] - title (string)
array[2] - start date (string with PHP date format 'Y-m-d')
*/
function pwtc_mileage_fetch_posted_ride($post_id) {
    global $wpdb;
    //$ride_post_type = 'rideevent';
    //$ride_date_metakey = 'start_date';
    $ride_post_type = 'scheduled_rides';
    $ride_date_metakey = 'date';
    $sql_stmt = $wpdb->prepare(
        'select p.ID, p.post_title, date_format(m.meta_value, %s) as start_date' . 
        ' from ' . $wpdb->posts . ' as p inner join ' . $wpdb->postmeta . 
        ' as m on p.ID = m.post_id where p.ID = %d and p.post_type = %s' . 
        ' and p.post_status = \'publish\' and m.meta_key = %s order by m.meta_value', 
        '%Y-%m-%d', $post_id, $ride_post_type, $ride_date_metakey);
    $results = $wpdb->get_results($sql_stmt, ARRAY_N);
    return $results;
}

/*
Returns an array that contains the rider ids of the ride leaders of the posted ride. 
*/
function pwtc_mileage_fetch_ride_leader_ids($post_id) {
    $leaders_array = array();
    if (function_exists('get_field')) {
        $leaders = get_field('ride_leaders', $post_id);
        if ($leaders) {
            //pwtc_mileage_write_log($leaders);
            if (function_exists('civicrm_initialize')) {
                civicrm_initialize();
                foreach ($leaders as $leader) {
                    $id = pwtc_mileage_fetch_civi_member_id($leader['user_email']);
                    if ($id) {
                        array_push($leaders_array, $id);
                    }
                }
            }
            else {
                $test_date = PwtcMileage::get_date_for_expir_check();
                foreach ($leaders as $leader) {
                    $fname = $leader['user_firstname'];
                    $lname = $leader['user_lastname'];
                    $result = PwtcMileage_DB::fetch_riders_by_name(trim($lname), trim($fname), $test_date);
                    if (count($result) == 1) {
                        $id = $result[0]['member_id'];
                        array_push($leaders_array, $id);
                    }
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
    }
    return $leaders_array;
}

/*
Returns an array that contains the names of the ride leaders of the posted ride. 
*/
function pwtc_mileage_fetch_ride_leader_names($post_id) {
    $leaders_array = array();
    if (function_exists('get_field')) {
        $leaders = get_field('ride_leaders', $post_id);
        if ($leaders) {
            //pwtc_mileage_write_log($leaders);
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
    }
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