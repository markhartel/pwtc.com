<?php
require_once __DIR__.'/../app/bootstrap.php';
use Timber\Timber;

/** @var $timber Timber */
$timber = $container->get('timber');

$context            = $timber::get_context();
$context['sidebar'] = $timber::get_widgets('shop-sidebar');

if (is_singular('product')) {
    $context['post']    = $timber::get_post();
    $product            = wc_get_product($context['post']->ID);
    $context['product'] = $product;

    $timber::render('pages/single-product.html.twig', $context);
} else {
    $posts = $timber::get_posts();
    $context['products'] = $posts;

    if (is_product_category()) {
        $queried_object = get_queried_object();
        $term_id = $queried_object->term_id;
        $context['category'] = get_term($term_id, 'product_cat');
        $context['title'] = single_term_title('', false);
    }

    $timber::render('pages/archive-products.html.twig', $context);
}