<?php
require_once __DIR__.'/../app/bootstrap.php';
use Timber\Timber;
use Timber\PostQuery;

/** @var $timber Timber */
$timber = $container->get('timber');
$data = $timber::get_context();
$data['post'] = $timber::get_post();

$data['terrain'] = get_field('terrain');
$data['length'] = get_field('length');
$data['max_length'] = get_field('max_length');
$data['maps'] = get_field('maps');
$data['current_url'] = get_permalink();

// render
$timber->render('pages/single-ride_map.html.twig', $data);
