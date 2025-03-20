<?php
require_once __DIR__.'/../app/bootstrap.php';
use Timber\Timber;
use Timber\PostQuery;

/** @var $timber Timber */
$timber = $container->get('timber');
$data = $timber::get_context();

$curauth = (isset($_GET['author_name'])) ? get_user_by('slug', $author_name) : get_userdata(intval($author));

$data['id'] = $curauth->ID;
$data['nickname'] = $curauth->nickname;
$data['first_name'] = $curauth->first_name;
$data['last_name'] = $curauth->last_name;
    
// render
$timber->render('pages/author.html.twig', $data);
