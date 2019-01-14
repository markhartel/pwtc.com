<?php
$vendor =  __DIR__.'/../../vendor/autoload.php';

$theme_requirements = [];
if(!function_exists('acf_add_options_page') || !function_exists('get_field')) {
    $theme_requirements[] = __( 'The Theme requires "Advanced Custom Fields Pro" to function properly. Please download and activate it', 'stellar' );
}

if(!class_exists('\Timber\Timber')) {
    $theme_requirements[] = __("Timber is required for this theme. It can be installed with their plugin or with composer.", 'stellar');
}

if(!file_exists($vendor)) {
    $theme_requirements[] = __("Cant locate composer autoload file. Did you run composer install?", 'stellar');
} else {
    require_once $vendor;
}

if($theme_requirements) {
    add_action('admin_notices', function() use($theme_requirements)
    {
        foreach($theme_requirements as $requirement) {
            echo '<div class="notice notice-error notice-large"><div class="notice-title">' . $requirement . '</div></div>';
        }
    });

    add_action('template_redirect', function() use($theme_requirements)
    {
        wp_die(implode('<br />', $theme_requirements));
    }, 0);
}