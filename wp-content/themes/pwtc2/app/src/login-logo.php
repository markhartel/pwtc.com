<?php
use Timber\Timber;

add_action('login_head', function ()  {
    if ($login_image = (get_field('login_logo', 'option') ?: get_field('logo', 'option'))) {
        Timber::render('admin/login.html.twig', ['logo' => $login_image]);
    }
});
