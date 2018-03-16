<?php
require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

$container_file = __DIR__.'/../var/cache/container.php';
$containerConfigCache = new ConfigCache($container_file, WP_DEBUG);

if (!$containerConfigCache->isFresh()) {
    $container = new ContainerBuilder();
    $container->setParameter('template_dir', get_template_directory());
    $container->setParameter('template_uri', get_template_directory_uri());
    $container->setParameter('WP_DEBUG', WP_DEBUG);

    $loader = new YamlFileLoader($container, new FileLocator(get_template_directory()));
    $loader->load('config.container.yml');

    $container->compile();

    $dumper = new PhpDumper($container);
    $containerConfigCache->write(
        $dumper->dump(array('class' => 'MyCachedContainer')),
        $container->getResources()
    );
}

require_once $container_file;
$container = new MyCachedContainer();

/** @var $container \Symfony\Component\DependencyInjection\Container */
/** @var $twig \Twig_Environment */
$twig = $container->get('twig.environment');

// register scripts/styles
add_action('wp_enqueue_scripts', function() use($container) {
    // styles
    if($container->hasParameter('wordpress.styles')) {
        foreach ($container->getParameter('wordpress.styles') as $args) {
            wp_register_style($args['id'], $container->getParameterBag()->resolveValue($args['source']), $args['deps'], $args['version'], 'all');
            wp_enqueue_style($args['id']);
        }
    }

    // config
    if($container->hasParameter('wordpress.scripts')) {
        foreach ($container->getParameter('wordpress.scripts') as $args) {
            wp_register_script($args['id'], $container->getParameterBag()->resolveValue($args['source']), $args['deps'], $args['version'], $args['header']);
            wp_enqueue_script($args['id']);
        }
    }
});

add_action('init', function () use($container) {
    // always start a session
    if (!session_id()) {
        session_start();
    }

    // post types
    if($container->hasParameter('wordpress.post_types')) {
        foreach ($container->getParameter('wordpress.post_types', []) as $post_type => $args) {
            register_post_type($post_type, $args);
        }
    }
});

add_action('after_setup_theme', function() use($container) {
    // load languages directory
    if($container->hasParameter('wordpress.translations')) {
        load_theme_textdomain('supertheme', $container->getParameter('wordpress.translations'));
    }

    // image sizes from config
    if($container->hasParameter('wordpress.image_sizes')) {
        foreach ($container->getParameter('wordpress.image_sizes') as $name => $imageSize) {
            array_unshift($imageSize, $name);
            call_user_func_array('add_image_size', $imageSize);
        }
    }

    // theme supports
    if($container->hasParameter('wordpress.theme_support')) {
        foreach ($container->getParameter('wordpress.theme_support') as $support) {
            add_theme_support($support);
        }
    }

    // theme sidebars
    if($container->hasParameter('wordpress.sidebars')) {
        foreach ($container->getParameter('wordpress.sidebars') as $sidebar) {
            register_sidebar($sidebar);
        }
    }

    // menus from config
    if($container->hasParameter('wordpress.menus')) {
        register_nav_menus($container->getParameter('wordpress.menus'));
    }
});

// remove duplicate page link
add_filter('wp_before_admin_bar_render', function(){
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('duplicate_this');
});