<?php
add_action('init', function() {
    add_filter('get_the_excerpt', function ($text) {
        return rtrim($text, '[&hellip;]') . '&hellip;';
    });

    add_filter('excerpt_length', function(){
        return 50;
    });
});