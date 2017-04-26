<?php
add_action('init', function() {
    add_filter('woocommerce_disable_admin_bar', function(){
        $user = wp_get_current_user();
        if (in_array('ride_captain', (array) $user->roles) || user_can($user, 'manage_options') || user_can($user, 'edit_posts')){
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
        if (in_array('ride_captain', (array) $user->roles) || user_can($user, 'manage_options') || user_can($user, 'edit_posts')){
            return false;
        }

        return $prevent_access;
    });
});