<?php
if(function_exists('acf_add_options_page')) {
    $option_page = acf_add_options_page(array(
        'page_title' 	=> 'Theme General Settings',
        'menu_title' 	=> 'Theme Settings',
        'menu_slug' 	=> 'theme-general-settings',
        'capability' 	=> 'edit_posts',
        'redirect' 	=> false
    ));
}

add_action('acf/init', function () {
    acf_update_setting('google_api_key', 'AIzaSyD-fZDMA7HRQAY7Vu9WhbpQeaMc_zV7aJ8');
});