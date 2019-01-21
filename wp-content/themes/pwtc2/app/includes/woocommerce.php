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

            $fields['account']['account_directory'] = [
                'type'              => 'checkbox',
                'label'             => __( 'Exclude member from the Membership Directory listing.', 'woocommerce' ),
                'required'          => false,
            ];

            $fields['account']['account_release'] = [
                'type'              => 'checkbox',
                'label'             => __( 'Legal release statement has been accepted by member.', 'woocommerce' ),
                'required'          => true,
            ];

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
