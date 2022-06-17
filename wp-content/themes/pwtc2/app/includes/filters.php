<?php
add_action('init', function() {
    add_filter('get_the_excerpt', function ($text) {
        return rtrim($text, '[&hellip;]') . '&hellip;';
    });

    add_filter('excerpt_length', function(){
        return 50;
    });
});

/* After login, redirect all users that are not administrators to the home page. */
add_filter('login_redirect', function ($redirect_to, $request, $user) {
    if ( isset( $user->roles ) && is_array( $user->roles ) ) {
        if ( in_array( 'administrator', $user->roles ) ) {
            //error_log( 'login_redirect: administrator, redirect to: ' . $redirect_to);
            return $redirect_to;
        } else {
            //error_log( 'login_redirect: not administrator, redirect to: ' . home_url());
            return home_url();
        }
    } else {
        //error_log( 'login_redirect: role not available, redirect to: ' . $redirect_to);
        return $redirect_to;
    }
}, 999999, 3);
