<?php
require_once __DIR__.'/app/bootstrap.php';

// get services
/** @var \Symfony\Component\DependencyInjection\Container $container */
/** @var Twig_Environment $twig */
$twig = $container->get("twig.environment");
global $wp_query;

// preg global twig data
$data = require_once __DIR__ . '/app/bootstrap-theme.php';
ob_start();
wp_title('');
$data['title'] = ob_get_clean();
$data['page'] = (get_query_var('paged')) ? get_query_var('paged') : 1;
$data['pages'] = $wp_query->max_num_pages;

$rows = [];
$teasers = [];
while(have_posts()) {
    the_post();
    $id = get_the_ID();
    $teaser_data = [];
    $teaser_data['title'] = get_the_title($id);
    $teaser_data['excerpt'] = get_the_excerpt($id);
    $teaser_data['image'] = get_the_post_thumbnail($id, 'teaser');
    $teaser_data['link'] = get_the_permalink($id);
    $teaser_data['format'] = get_field('format');
    $teasers[] = $twig->render("teasers/post.html.twig", $teaser_data);
}
$data['teasers'] = $teasers;
$rows[] = $twig->render('post.html.twig', $data);
$data['rows'] = $rows;

// render
echo $twig->render('basic.html.twig', $data);
