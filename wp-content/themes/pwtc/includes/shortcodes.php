<?php
add_shortcode('pwtc_renew', function() use($twig) {
    return $twig->render('shortcodes/renew.html.twig', [
        'is_logged_in' => is_user_logged_in(),
    ]);
});