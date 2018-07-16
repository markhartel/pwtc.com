<?php
require_once __DIR__.'/includes/acf.php';
require_once __DIR__.'/includes/bootstrap.php';
require_once __DIR__.'/includes/options-page.php';
require_once __DIR__.'/includes/widgets.php';
require_once __DIR__.'/includes/roles.php';
require_once __DIR__.'/includes/shortcodes.php';
require_once __DIR__.'/includes/excerpts.php';
require_once __DIR__.'/includes/post-types.php';
require_once __DIR__.'/includes/schedule-rides.php';
require_once __DIR__.'/includes/schedule-column.php';
require_once __DIR__.'/includes/woocommerce.php';
require_once __DIR__.'/includes/rides-ical-feed.php';


// add civicrm membership functionality to account page
$membership = new \PWTC\Membership();

// add admin styles and scripts
add_action('admin_enqueue_scripts', function () {
    wp_register_script('custom_wp_admin_css', get_template_directory_uri() . '/assets/scripts/admin.js', ['jquery']);
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
add_filter('login_redirect', function ($redirect_to, $request, $user) {
    return home_url();
}, 10, 3 );

add_filter( 'login_redirect', function ($redirect_to, $request, $user) {
    return home_url();
}, 10, 3 );

// image quality
add_filter('jpeg_quality', function() { return 100; });

// remove password meter
add_action('wp_print_scripts', function(){
    if (wp_script_is('wc-password-strength-meter', 'enqueued') ) {
        wp_dequeue_script('wc-password-strength-meter');
    }
}, 100);

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

add_action( 'login_enqueue_scripts', function () { ?>
    <style type="text/css">
        body.login h1 a {
            background-image: url('<?php echo get_template_directory_uri(); ?>/logo.png');
            background-size: contain;
            width: 100%;
            max-width: 320px;
            height: 150px;
        }
        body.login {
            background-color: #BA383B;
            color: #fefefe;
        }
        body.login .message {
            color: #353535;
        }
        body.login #login_error {
            color: #353535;
        }
        body.login form {
            background-color: #353535;
        }
        body.login label {
            color: #fefefe;
        }
        body.wp-core-ui .button-primary {
            background-color: #BA383B;
            border-color: #BA383B;
            box-shadow: none;
            text-shadow: none;
            border-radius: 0;
        }
        body.wp-core-ui .button-primary:hover,
        body.wp-core-ui .button-primary:active  {
            background-color: #952d2f;
            border-color: #952d2f;
            box-shadow: none;
            text-shadow: none;
            border-radius: 0;
        }
        body.login #backtoblog a, body.login #nav a {
            color: #fefefe;
        }

        body.login #backtoblog a:hover, body.login #nav a:hover,
        body.login #backtoblog a:active, body.login #nav a:active {
            color: #cecece;
        }

        body.login #nav {
            font-size: 0;
        }
        body.login #nav a:last-of-type {
            font-size: 13px;
        }
    </style>
<?php });
