<?php
// register scripts/styles
add_action('wp_enqueue_scripts', function() use($container) {
    // styles
    if($container->hasParameter('wordpress.inline_styles')) {
        foreach ($container->getParameter('wordpress.inline_styles') as $args) {
            $file_path = $container->getParameterBag()->resolveValue($args['source']);
            if(file_exists($file_path)) {
                $content = file_get_contents($file_path);
                wp_register_style($args['id'], false);
                wp_enqueue_style($args['id']);
                wp_add_inline_style($args['id'], $content);
            }
        }
    }
    if($container->hasParameter('wordpress.styles')) {
        foreach ($container->getParameter('wordpress.styles') as $args) {
            wp_register_style($args['id'], $container->getParameterBag()->resolveValue($args['source']), $args['deps'], false, 'all');
            wp_enqueue_style($args['id']);
        }
    }

    // scripts
    if($container->hasParameter('wordpress.scripts')) {
        foreach ($container->getParameter('wordpress.scripts') as $args) {
            wp_register_script($args['id'], $container->getParameterBag()->resolveValue($args['source']), $args['deps'], false, !$args['header']);
            wp_enqueue_script($args['id']);
        }
    }

    $dist = get_theme_file_uri().'/..//dist';
    $manifestPath = get_theme_file_path().'/../dist/assets.json';
    $manifest = file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : [];

    wp_enqueue_style('sage/main.css', $dist.'/'.($manifest['styles/main.css'] ?? 'styles/main.css'), false, null);
    wp_enqueue_script('sage/main.js', $dist.'/'.($manifest['styles/main.css'] ?? 'scripts/main.js'), ['jquery'], null, true);
});
