<?php
/**
 * Template Name: Two Columns
 *
 */
require_once __DIR__.'/app/bootstrap.php';
// get services
/** @var \Symfony\Component\DependencyInjection\Container $container */
/** @var Twig_Environment $twig */
$twig = $container->get("twig.environment");

// preg global twig data
$data = require_once __DIR__ . '/app/bootstrap-theme.php';

if(get_field('attach_map')) {
    $length = null;
    $maxLength = null;
    $terrain = [];
    $maps = [];
    foreach (get_field('maps') as $map) {
        $map_id = $map;
        if($length) {
            $length = min(get_field('length', $map_id), $length);
        } else {
            $length = get_field('length', $map_id);
        }
        if($maxLength) {
            $maxLength = max(get_field('max_length', $map_id), $maxLength);
        } else {
            $maxLength = get_field('max_length', $map_id);
        }
        $terrain = array_merge($terrain, get_field('terrain', $map_id));
        $maps = array_merge($maps, get_field('maps', $map_id));
    }
}
$data['terrain'] = isset($terrain) ? array_unique($terrain) : get_field('terrain');
$data['length'] = isset($length) ? $length : get_field('length');
$data['max_length'] = isset($maxLength) ? $maxLength : get_field('max_length');
$data['maps'] = isset($maps) ? $maps : false;
// render
echo $twig->render('ride-details.html.twig', $data);
