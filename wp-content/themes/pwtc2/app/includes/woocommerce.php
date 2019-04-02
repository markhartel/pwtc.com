<?php
add_action('init', function() {
    add_filter('woocommerce_disable_admin_bar', function(){
        $user = wp_get_current_user();
        if (
            in_array('ride_captain', (array) $user->roles) ||
            in_array('statistician', (array) $user->roles) ||
            user_can($user, 'manage_options') ||
            user_can($user, 'edit_posts')
        ){
            return false;
        }
        return true;
    });

    add_filter('woocommerce_prevent_admin_access', function(){
        $prevent_access = false;

        if ( 'yes' === get_option( 'woocommerce_lock_down_admin', 'yes' ) && ! is_ajax() && basename( $_SERVER["SCRIPT_FILENAME"] ) !== 'admin-post.php' ) {
            $has_cap     = false;
            $access_caps = array( 'edit_posts', 'manage_woocommerce', 'view_admin_dashboard' );

            foreach ( $access_caps as $access_cap ) {
                if ( current_user_can( $access_cap ) ) {
                    $has_cap = true;
                    break;
                }
            }

            if ( ! $has_cap ) {
                $prevent_access = true;
            }
        }

        $user = wp_get_current_user();
        if (
            in_array('ride_captain', (array) $user->roles) ||
            in_array('statistician', (array) $user->roles) ||
            user_can($user, 'manage_options') ||
            user_can($user, 'edit_posts')
        ){
            return false;
        }

        return $prevent_access;
    });

    add_action( 'woocommerce_checkout_fields', function ($fields = []) {
        if (get_option('woocommerce_registration_generate_password') == 'no') {
            $fields['account']['account_confirm_password'] = [
                'type'              => 'password',
                'label'             => __( 'Confirm password', 'woocommerce' ),
                'required'          => true,
                'placeholder'       => _x( 'Confirm Password', 'placeholder', 'woocommerce' )
            ];

//            $fields['account']['account_directory'] = [
//                'type'              => 'checkbox',
//                'label'             => __( 'Exclude member from the Membership Directory listing.', 'woocommerce' ),
//                'required'          => false,
//            ];
//
//            $fields['account']['account_release'] = [
//                'type'              => 'checkbox',
//                'label'             => __( 'Legal release statement has been accepted by member.', 'woocommerce' ),
//                'required'          => true,
//            ];

            return $fields;
        }
    }, 10, 1);

    add_action('woocommerce_after_checkout_validation', function($posted){
        $checkout = WC()->checkout;
        if (!is_user_logged_in() && ($checkout->must_create_account || ! empty($posted['createaccount']))) {
            if (strcmp($posted['account_password'], $posted['account_confirm_password']) !== 0) {
                wc_add_notice(__('Passwords do not match.', 'woocommerce'), 'error');
            }
        }
    }, 10, 2);

    add_action('woocommerce_checkout_update_user_meta', function($customer_id, $posted){
        if (isset($posted['account_release'])) {
            update_field('release_accepted', (bool) $posted['account_release'], 'user_'.get_current_user_id());
        }

        if (isset($posted['account_directory'])) {
            update_field('directory_excluded', (bool) $posted['account_directory'], 'user_'.get_current_user_id());
        }
    }, 10, 2);

    add_action( 'woocommerce_edit_account_form', function(){
        $fields = [];
        $fields['account_directory'] = [
            'type'              => 'checkbox',
            'label'             => __( 'Exclude member from the Membership Directory listing.', 'woocommerce' ),
            'required'          => false,
            'default'           => (bool) get_field('directory_excluded', 'user_'.get_current_user_id()),
        ];

        foreach ($fields as $key => $field_args) {
            woocommerce_form_field($key, $field_args);
        }
    }, 10 );

    add_action('woocommerce_save_account_details', function($customer_id) {
        if (isset($_POST['account_directory'])) {
            update_field('directory_excluded', (bool) $_POST['account_directory'], 'user_'.get_current_user_id());
        } else {
            update_field('directory_excluded', false, 'user_'.get_current_user_id());
        }
    });

    add_filter( 'woocommerce_account_menu_items', function ( $items ) {
        unset($items['downloads']);
        return $items;
    });

    remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
    remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering',30 );
    remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
    remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
});

add_action('woocommerce_before_my_account', function(){
    $user_id = get_current_user_id();

    //set the display name
    $info = get_userdata( $user_id );

    $display_name = trim($info->first_name . ' ' . $info->last_name);
    if(!$display_name)
        $display_name = $info->user_login;

    $args = array(
        'ID' => $user_id,
        'display_name' => $display_name
    );

    wp_update_user( $args ) ;
});


add_filter('wp_insert_post', function($data, $postarr){
    if($postarr->post_type != "wc_memberships_team") {
        return $data;
    }

    $user = get_userdata($postarr->post_author);
    $data['post_title'] = $user->first_name . ' ' . $user->last_name;

    return $data;
}, 10, 2);


add_filter('wc_memberships_for_teams_new_team_data', function($team_post_data) {
    $user_data = get_userdata($team_post_data['post_author']);

    if (!$user_data) {
        $team_post_data['post_title'] = 'Unknown';
    }

    else {
        $team_post_data['post_title'] = $user_data->last_name . ', ' . $user_data->first_name;
    }

    return $team_post_data;
});

add_action('wp_print_scripts', function(){
    if (wp_script_is('wc-password-strength-meter', 'enqueued') ) {
        wp_dequeue_script('wc-password-strength-meter');
    }
}, 100);

add_filter('wc_memberships_members_area_my-memberships_actions', function ($actions) {
    unset($actions['cancel']);

    return $actions;
});

add_filter('wc_memberships_members_area_my-membership-details_actions', function ($actions) {
    unset($actions['cancel']);

    return $actions;
});
