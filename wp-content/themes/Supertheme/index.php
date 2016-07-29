<?php
require_once __DIR__.'/app/bootstrap.php';

// get services
/** @var \Symfony\Component\DependencyInjection\Container $container */
/** @var Twig_Environment $twig */
$twig = $container->get("twig.environment");

// preg global twig data
$data = require_once __DIR__ . '/app/bootstrap-theme.php';

// get content rows
$rows = [];
while(have_rows('content_rows')) {
    the_row();
    $rows[] = $twig->render('rows/'.get_row_layout().'.html.twig');
}
$data['rows'] = $rows;

// render
echo $twig->render('basic.html.twig', $data);
