<?php
require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

$container = new ContainerBuilder();
$container->setParameter('template_dir', get_template_directory());
$container->setParameter('template_uri', get_template_directory_uri());
$container->setParameter('WP_DEBUG', WP_DEBUG);

$loader = new YamlFileLoader($container, new FileLocator(get_template_directory()));
$loader->load('app/config/config.yml');

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