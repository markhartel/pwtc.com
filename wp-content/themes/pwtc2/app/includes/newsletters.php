<?php
add_action('init', function(){
    register_post_type('newsletter', [
        'public' => true,
        'has_archive' => true,
        'label'  => 'Newsletters',
        'menu_position' => 5,
        'capability_type' => 'newsletter',
        'map_meta_cap' => true,
        'supports' => [
            'title',
            'editor',
            'thumbnail',
            'excerpt',
        ]
    ]);
});
