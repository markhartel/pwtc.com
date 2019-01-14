<?php
use Timber\Timber;

add_shortcode('pwtc_renew', function() {
    return Timber::compile('shortcodes/renew.html.twig', [
        'is_logged_in' => is_user_logged_in(),
    ]);
});
