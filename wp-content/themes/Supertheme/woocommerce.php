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
ob_start();
woocommerce_content();
$data['woocommerce'] = ob_get_clean();

// render
echo $twig->render('woocommerce.html.twig', $data);
