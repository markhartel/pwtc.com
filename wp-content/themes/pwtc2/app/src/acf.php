<?php
// show/hide acf menus
add_filter('acf/settings/show_admin', function ($show) use($container) {
    return $container->getParameter('wordpress.acf_menu');
});

// json location
add_filter('acf/settings/save_json', function ($path) use($container) {
    $path = $container->getParameter('wordpress.acf_path');

    return $container->getParameterBag()->resolveValue($path);
});

// create pages
if($container->hasParameter('wordpress.acf_pages')) {
    add_action('acf/init', function () use($container) {
        foreach($container->getParameter('wordpress.acf_pages') as $page) {
            acf_add_options_page($page);
        }
    });
}

// create sub pages
if($container->hasParameter('wordpress.acf_sub_pages')) {
    add_action('acf/init', function () use($container) {
        foreach($container->getParameter('wordpress.acf_sub_pages') as $page) {
            acf_add_options_sub_page($page);
        }
    });
}