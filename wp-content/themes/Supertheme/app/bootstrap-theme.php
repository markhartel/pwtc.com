<?php
use Supertheme\WordPress\AccordionMenuWalker;
use Supertheme\WordPress\DropDownMenuWalker;

$data = [];

$data['isLoggedIn'] = is_user_logged_in();
$data['avatar'] = get_avatar(get_current_user_id(), 32);
$data['editProfileLink'] = get_edit_user_link(get_current_user_id());
$data['loginLink'] = wp_login_url();
$data['logoutLink'] = wp_logout_url();
$data['url'] = get_site_url();
$data['desktopMenu'] = wp_nav_menu([
    'echo' => false,
    'container' => false,
    'items_wrap' => '<ul id="%1$s" class="%2$s dropdown menu hide-for-small-only float-right" data-dropdown-menu data-close-on-click-inside="false">%3$s</ul>',
    'theme_location' => 'primary_menu',
    'walker' => new DropDownMenuWalker(),
]);
$data['memberMenu'] = wp_nav_menu([
    'echo' => false,
    'container' => false,
    'items_wrap' => '<ul id="%1$s" class="%2$s menu">%3$s</ul>',
    'theme_location' => 'member_menu',
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
$data['layout'] = get_field('layout') ?: "one";
$data['blog_url'] = get_option('show_on_front') == 'page' ? get_permalink(get_option('page_for_posts')) : get_bloginfo('url');
ob_start();
dynamic_sidebar( 'right_sidebar' );
$data['right_sidebar'] = ob_get_clean();
ob_start();
dynamic_sidebar( 'left_sidebar' );
$data['left_sidebar'] = ob_get_clean();
$data['footer_form'] = gravity_form(1, false, false, false, null, true, 1, false);

return $data;