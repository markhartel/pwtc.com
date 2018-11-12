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

    add_filter('woocommerce_registration_errors', function ($reg_errors, $sanitized_user_login, $user_email){
        global $woocommerce;
        extract($_POST);

        if (strcmp($password, $password2) !== 0) {
            return new WP_Error('registration-error', __('Passwords do not match.', 'woocommerce'));
        }

        return $reg_errors;
    }, 10,3);

    add_action('woocommerce_register_form', function (){
        ?>
        <p class="form-row form-row-wide">
            <label for="reg_password2"><?php _e('Password Repeat', 'woocommerce'); ?> <span class="required">*</span></label>
            <input type="password" class="input-text" name="password2" id="reg_password2" value="<?php if (!empty($_POST['password2'])) echo esc_attr($_POST['password2']); ?>" />
        </p>
        <?php
    });


    add_action('woocommerce_after_checkout_validation', function($posted){
        $checkout = WC()->checkout;
        if (!is_user_logged_in() && ($checkout->must_create_account || ! empty($posted['createaccount']))) {
            if (strcmp($posted['account_password'], $posted['account_confirm_password']) !== 0) {
                wc_add_notice(__('Passwords do not match.', 'woocommerce'), 'error');
            }
        }
    }, 10, 2);

    add_action( 'woocommerce_checkout_init', function ($checkout) {
        if (get_option('woocommerce_registration_generate_password') == 'no') {
            $fields = $checkout->get_checkout_fields();

            $fields['account']['account_confirm_password'] = [
                'type'              => 'password',
                'label'             => __( 'Confirm password', 'woocommerce' ),
                'required'          => true,
                'placeholder'       => _x( 'Confirm Password', 'placeholder', 'woocommerce' )
            ];

            $checkout->__set('checkout_fields', $fields);
        }
    }, 10, 1);
});