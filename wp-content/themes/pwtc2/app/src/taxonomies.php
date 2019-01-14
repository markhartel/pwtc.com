<?php
add_action('init', function () use($container) {
    // post types
    if ($container->hasParameter('wordpress.taxonomies')) {
        foreach ($container->getParameter('wordpress.taxonomies', []) as $taxonomy => $args) {
            register_taxonomy($taxonomy, $args['post_types'], $args['options']);
        }
    }
});