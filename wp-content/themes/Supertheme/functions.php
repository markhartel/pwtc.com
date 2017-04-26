<?php
require_once __DIR__.'/app/acf.php';
require_once __DIR__.'/app/bootstrap.php';
require_once __DIR__.'/src/functions.php';
require_once __DIR__.'/app/options-page.php';
require_once __DIR__.'/app/widgets.php';
require_once __DIR__.'/app/roles.php';
require_once __DIR__.'/app/excerpts.php';
require_once __DIR__.'/app/post-types.php';
require_once __DIR__.'/app/schedule-rides.php';
require_once __DIR__.'/app/schedule-column.php';
require_once __DIR__.'/app/woocommerce.php';

// add civicrm membership functionality to account page
$membership = new \App\Membership();

// add admin styles and scripts
add_action('admin_enqueue_scripts', function () {
    wp_register_script('custom_wp_admin_css', get_template_directory_uri() . '/web/scripts/admin.js', ['jquery']);
    wp_enqueue_script('custom_wp_admin_css');
});

// allow all users to update their profile pic
add_action('init', function() {
    update_option('basic_user_avatars_caps', true);
});

// forms processing
add_action( 'wp_enqueue_scripts', function() {
    wp_localize_script('app', 'civi', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
});

// redirect users to home page after login
add_filter('login_redirect', function ($redirect_to, $request, $user){
    return home_url();
}, 10, 3 );