<?php
use Supertheme\WordPress\AccordionMenuWalker;
use Supertheme\WordPress\DropDownMenuWalker;

require_once __DIR__.'/App/bootstrap.php';

$data = [];
$data['isLoggedIn'] = is_user_logged_in();
$data['avatar'] = get_avatar(get_current_user_id(), 32);
$data['editProfileLink'] = get_edit_user_link(get_current_user_id());
$data['loginLink'] = wp_login_url();
$data['logoutLink'] = wp_logout_url();
$data['desktopMenu'] = wp_nav_menu([
    'echo' => false,
    'container' => false,
    'items_wrap' => '<ul id="%1$s" class="%2$s dropdown menu hide-for-small-only float-right" data-dropdown-menu>%3$s</ul>',
    'theme_location' => 'primary_menu',
    'walker' => new DropDownMenuWalker(),
]);
$data['mobileMenu'] = wp_nav_menu([
    'echo' => false,
    'container' => false,
    'items_wrap' => '<ul id="%1$s" class="%2$s vertical menu" data-accordion-menu>%3$s</ul>',
    'theme_location' => 'primary_menu',
    'walker' => new AccordionMenuWalker(),
]);
$data['footerMenu'] = wp_nav_menu([
    'echo' => false,
    'container' => false,
    'items_wrap' => '<ul id="%1$s" class="%2$s vertical medium-horizontal menu">%3$s</ul>',
    'theme_location' => 'footer_menu',
]);

$twig = $container->get("twig.environment");
echo $twig->render('basic.html.twig', $data);
