<?php
if(!function_exists('get_product')) {
    return;
}
add_filter('woocommerce_breadcrumb_defaults', function() {
    return array(
        'delimiter'   => ' &#47; ',
        'wrap_before' => '<nav aria-label="You are here:" role="navigation"><ul class="breadcrumbs">',
        'wrap_after'  => '</ul></nav>',
        'before'      => '<li>',
        'after'       => '</li>',
        'home'        => _x( 'Home', 'breadcrumb', 'woocommerce' ),
    );
});

add_filter('woocommerce_breadcrumb_defaults', function ($defaults) {
    $defaults['delimiter'] = '';
    return $defaults;
});

function timber_set_product($post) {
    global $product; //grab the global $product
    $product = get_product($post->ID); //over-ride it with the current product in the loop
}