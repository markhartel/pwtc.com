<?php
require_once __DIR__.'/../app/bootstrap.php';
use Timber\Timber;
use Timber\PostQuery;

/** @var $timber Timber */
$timber = $container->get('timber');
$context = $timber::get_context();

$template = 'pages/search.html.twig';
$context['query'] = get_search_query();
$context['title'] = 'Search Results';
$context['posts'] = new PostQuery();

$timber::render($template, $context);
