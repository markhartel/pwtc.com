<?php
/**
 * Template Name: Two Columns
 *
 */
require_once __DIR__.'/../app/bootstrap.php';
use Timber\Timber;

$context = Timber::get_context();
$context['post'] = Timber::get_post();

// render
Timber::render('two-column.html.twig', $context);
