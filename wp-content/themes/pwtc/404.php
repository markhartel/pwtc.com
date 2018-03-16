<?php
/**
 * Template Name: Two Columns
 *
 */
require_once __DIR__.'/includes/bootstrap.php';

// get services
/** @var \Symfony\Component\DependencyInjection\Container $container */
/** @var Twig_Environment $twig */
$twig = $container->get("twig.environment");

// preg global twig data
$data = require_once __DIR__ . '/includes/bootstrap-theme.php';

$args = [
    'numberposts' => 8,
    'offset' => 0,
    'category' => 0,
    'orderby' => 'post_date',
    'order' => 'DESC',
    'post_type' => 'post',
    'post_status' => 'publish',
    'suppress_filters' => true,
];
$recent_posts = wp_get_recent_posts($args);
$teasers = [];
foreach($recent_posts as $post) {
    setup_postdata($post['ID']);
    $teaser_data = [];
    $teaser_data['title'] = get_the_title($post['ID']);
    $teaser_data['excerpt'] = excerpt();
    $teaser_data['image'] = get_the_post_thumbnail($post['ID'], 'teaser');
    $teaser_data['link'] = get_the_permalink($post['ID']);
    $teaser_data['format'] = get_field('format', $post['ID']);
    $teasers[] = $twig->render("teasers/post.html.twig", $teaser_data);
}
wp_reset_postdata();
$data['teasers'] = $teasers;
$data['rows'] = $twig->render('rows/news.html.twig', $data);

// render
echo $twig->render('404.html.twig', $data);
