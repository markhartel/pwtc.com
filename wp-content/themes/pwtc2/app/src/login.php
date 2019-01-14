<?php
add_action('login_enqueue_scripts', function () {
    $logo = get_theme_mod('login_page_background_image', get_stylesheet_directory_uri().'/web/vectors/stellar-logo.svg');
    $bg_color = get_theme_mod('login_page_background_color', '#242121');
    $box_color = get_theme_mod('login_page_box_color', '#f1f1f1');
    $text_color = get_theme_mod('login_font_color', '#242121');
    $button_color = get_theme_mod('login_button_font_color', '#f1f1f1');
    $button_background_color = get_theme_mod('login_button_background_color', '#d7df23');
    $link_color = get_theme_mod('login_link_color', '#d7df23');
    echo <<<HTML

<style>

    body.login { background-color: $bg_color; }
    
    body.login div#login { padding-top: 124px; }
    body.login div#login h1 {}
    body.login div#login h1 a {
        width: 100%;
        height: 124px;
        background-size: contain;
        background-image: url('$logo');
    }
    
    body.login div#login form#loginform { background-color: $box_color; }
    body.login div#login form#loginform p { color: $text_color; }
    body.login div#login form#loginform p label { color: $text_color; }
    body.login div#login form#loginform input { color: $box_color; }
    body.login div#login form#loginform input#user_login { color: $text_color; }
    body.login div#login form#loginform input#user_pass { color: $text_color; }
    body.login div#login form#loginform p.forgetmenot {}
    body.login div#login form#loginform p.forgetmenot input#rememberme {}
    body.login div#login form#loginform p.submit {}
    body.login div#login form#loginform p.submit input#wp-submit { 
        border-color: transparent;
        border-radius: 0;
        background-color: $button_background_color;
        box-shadow: none;
        text-shadow: none;
        color: $button_color;
        font-weight: bold;
    }
    
    body.login div#login p#nav {}
    body.login div#login p#nav a { color: $link_color; }
    body.login div#login p#backtoblog {}
    body.login div#login p#backtoblog a { color: $link_color; }
</style>
HTML;
});