<?php
add_filter('body_class', function($classes) {
    if (!is_user_logged_in()) {
        $classes[] = 'logged-out';
    }

    return $classes;
});