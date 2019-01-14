<?php
add_action('after_setup_theme', function() use($container) {
    // theme sidebars
    if ($container->hasParameter('wordpress.sidebars')) {
        foreach ($container->getParameter('wordpress.sidebars') as $sidebar) {
            register_sidebar($sidebar);

            // register to timber
            add_filter('timber/context', function($data) use($sidebar) {
                $data[$sidebar['id']] = Timber::get_widgets($sidebar['id']);

                return $data;
            });
        }
    }
});
