<?php
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/customizer.php';

add_filter('timber/context', function($context) {
    // the plugin doesnt load the avatar correctly with timber so we are defning it globably
    $context['avatar'] = get_avatar(get_current_user_id(), 32);
    $context['options'] = get_fields('option');
    ob_start();
    dynamic_sidebar( 'right_sidebar' );
    $data['right_sidebar'] = ob_get_clean();
    ob_start();
    dynamic_sidebar( 'left_sidebar' );
    $data['left_sidebar'] = ob_get_clean();

    return $context;
});

function pwtc_get_timezone_string() {
    if ($timezone = get_option('timezone_string'))
        return $timezone;

    if (0 === ($utc_offset = get_option('gmt_offset', 0)))
        return 'UTC';

    $utc_offset *= 3600;
    if ($timezone = timezone_name_from_abbr('', $utc_offset, 0)) {
        return $timezone;
    }

    $is_dst = date('I');
    foreach (timezone_abbreviations_list() as $abbr) {
        foreach ($abbr as $city) {
            if ($city['dst'] == $is_dst && $city['offset'] == $utc_offset)
                return $city['timezone_id'];
        }
    }

    return 'UTC';
}

function excerpt() {
    $error_level = error_reporting();
    error_reporting($error_level & ~E_NOTICE);

    $text = get_the_content();
    $raw_excerpt = $text;
    $text = strip_shortcodes( $text );
    $text = apply_filters( 'the_content', $text );
    $text = str_replace(']]>', ']]&gt;', $text);
    $excerpt_length = apply_filters( 'excerpt_length', 55 );
    $excerpt_more = apply_filters( 'excerpt_more', ' ...' );
    $text = wp_trim_words( $text, $excerpt_length, $excerpt_more );

    error_reporting($error_level);

    return apply_filters( 'wp_trim_excerpt', $text, $raw_excerpt );
}

add_action( 'pre_get_posts', function ( $query ) {
    if ( $query->is_home() && $query->is_main_query() ) {
        $query->set( 'cat', '-40' );
    }
});
