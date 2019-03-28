<?php
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/src/check-requirements.php';
if(!function_exists('acf_add_options_page')) {
    return;
}

require_once __DIR__.'/src/build-container.php';
require_once __DIR__.'/src/logged-out-class.php';
require_once __DIR__.'/src/timber.php';
require_once __DIR__.'/src/assets.php';
require_once __DIR__.'/src/remove-assets.php';
require_once __DIR__.'/src/session.php';
require_once __DIR__.'/src/post-types.php';
require_once __DIR__.'/src/taxonomies.php';
require_once __DIR__.'/src/options-page.php';
require_once __DIR__.'/src/replace-jquery.php';
require_once __DIR__.'/src/remove-body-margin.php';
require_once __DIR__.'/src/login-logo.php';
require_once __DIR__.'/src/translations.php';
require_once __DIR__.'/src/images.php';
require_once __DIR__.'/src/theme-support.php';
require_once __DIR__.'/src/shortcodes.php';
require_once __DIR__.'/src/sidebars.php';
require_once __DIR__.'/src/menus.php';
require_once __DIR__.'/src/acf.php';
require_once __DIR__.'/src/yoast.php';
require_once __DIR__.'/src/woocommerce.php';
require_once __DIR__.'/src/login.php';
require_once __DIR__.'/src/gravityforms.php';

require_once __DIR__.'/includes/acf.php'; // @TODO move to json
require_once __DIR__.'/includes/filters.php';
require_once __DIR__.'/includes/post-types.php'; // @TODO move to yaml
require_once __DIR__.'/includes/rides-ical-feed.php';
require_once __DIR__.'/includes/roles.php';// @TODO move to yaml
require_once __DIR__.'/includes/schedule-column.php';
require_once __DIR__.'/includes/schedule-rides.php';
require_once __DIR__.'/includes/shortcodes.php';
require_once __DIR__.'/includes/widgets.php';
require_once __DIR__.'/includes/widgets.php';
require_once __DIR__.'/includes/woocommerce.php';
require_once __DIR__.'/includes/newsletters.php';
