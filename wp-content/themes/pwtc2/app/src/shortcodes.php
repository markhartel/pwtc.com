<?php
add_shortcode('date', function($atts){
    $atts = shortcode_atts( array(
        'time' => 'now',
        'format' => 'Y'
    ), $atts, 'date');

    $date = new DateTime($atts['time']);
    return $date->format($atts['format']);
});

add_shortcode('social', function() use($container) {
    /** @var $timber Timber */
    $timber = $container->get('timber');
    $context = $timber::get_context();
    ob_start();
    $timber::render('partials/social.html.twig', $context);
    return ob_get_clean();
});
