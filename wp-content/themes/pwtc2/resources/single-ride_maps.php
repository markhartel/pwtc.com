<?php
require_once __DIR__.'/../app/bootstrap.php';
use Timber\Timber;
use Timber\PostQuery;

/** @var $timber Timber */
$timber = $container->get('timber');
$data = $timber::get_context();
$data['post'] = $timber::get_post();

$data['is_published'] = get_post_status() == 'publish';
$data['is_pending'] = get_post_status() == 'pending';
$data['terrain'] = get_field('terrain');
$data['length'] = get_field('length');
$data['max_length'] = get_field('max_length');
$raw_map = get_field('maps');
if ($raw_map[0]['type'] == 'link') {
	$raw_map[0]['title'] = $raw_map[0]['link'];
}
else {
	$raw_map[0]['title'] = $raw_map[0]['file']['filename'];
}
$data['maps'] = $raw_map;
$data['current_url'] = get_permalink();

if (function_exists('pwtc_mapdb_get_map_metadata')) {
    $metadata = pwtc_mapdb_get_map_metadata();
    $data['edit_map_url'] = $metadata['edit_map_url'];
}
else {
    $data['edit_map_url'] = false;
}

// render
$timber->render('pages/single-ride_map.html.twig', $data);
