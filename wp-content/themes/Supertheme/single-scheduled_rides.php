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

        // set length to the lowest length
        if (!$length) {
            $length = get_field('length', $map_id);
        } else if ($length && get_field('length', $map_id) < $length) {
            $length = get_field('length', $map_id);
        }

        // set max length to the highest max length
        if (!$maxLength) {
            $maxLength = get_field('max_length', $map_id);
        } else if ($maxLength && get_field('max_length', $map_id) < $maxLength) {
            $maxLength = get_field('max_length', $map_id);
        }

        $terrain = array_merge($terrain, get_field('terrain', $map_id));
        $maps = array_merge($maps, get_field('maps', $map_id));
    }
}
$data['terrain'] = isset($terrain) ? $terrain : get_field('terrain');
$data['length'] = isset($length) ? $length : get_field('length');
$data['max_length'] = isset($maxLength) ? $maxLength : get_field('max_length');
$data['maps'] = isset($maps) ? $maps : false;
// render
echo $twig->render('ride-details.html.twig', $data);
