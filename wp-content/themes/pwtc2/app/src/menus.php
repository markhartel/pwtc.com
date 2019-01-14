<?php
add_action('after_setup_theme', function() use($container) {
    // menus from config
    if($container->hasParameter('wordpress.menus')) {
        $menus = $container->getParameter('wordpress.menus');
        register_nav_menus($menus);

        // register to timber
        add_filter('timber/context', function($data) use($menus) {
            foreach($menus as $menu_id => $menu_description) {
                $data[$menu_id] = new Timber\Menu($menu_id);
            }

            return $data;
        });
    }
});
