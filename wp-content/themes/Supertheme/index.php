<?php
require_once __DIR__.'/app/bootstrap.php';

// get services
/** @var \Symfony\Component\DependencyInjection\Container $container */
/** @var Twig_Environment $twig */
$twig = $container->get("twig.environment");

// preg global twig data
$data = require_once __DIR__ . '/app/bootstrap-theme.php';
// get content rows
$rows = [];
while(have_rows('content_rows')) {
    the_row();
    if(get_row_layout() == "news") {
        $args = [
            'numberposts' => 8,
            'offset' => 0,
            'category' => 0,
            'orderby' => 'post_date',
            'order' => 'DESC',
            'post_type' => 'post',
            'post_status' => 'publish',
            'suppress_filters' => true,
        ];
        $recent_posts = wp_get_recent_posts($args, ARRAY_A);
        $teasers = [];
        foreach($recent_posts as $post) {
            setup_postdata($post['ID']);
            $teaser_data = [];
            $teaser_data['title'] = get_the_title($post['ID']);
            $teaser_data['excerpt'] = excerpt();
            $teaser_data['image'] = get_the_post_thumbnail($post['ID'], 'teaser');
            $teaser_data['link'] = get_the_permalink($post['ID']);
            $teaser_data['format'] = get_field('format', $post['ID']);
            $teasers[] = $twig->render("teasers/post.html.twig", $teaser_data);
        }
        wp_reset_postdata();
        $data['teasers'] = $teasers;
    }
    elseif(get_row_layout() == "rides")
    {
        $today = new DateTime();
        $rides_query = new WP_Query([
            'posts_per_page'	=> 10,
            'post_type' => 'scheduled_rides',
            'meta_query' => [
                [
                    'key' => 'date',
                    'value' =>  $today->format('Y-m-d 00:00:00'),
                    'compare' => '>=',
                    'type'	=> 'DATETIME'
                ],
            ],
            'orderby' => ['date' => 'ASC'],
        ]);
        $rides_data = [];
        while($rides_query->have_posts()){
            $rides_query->the_post();
            $datetime = DateTime::createFromFormat('Y-m-d H:i:s', get_field('date'));
            $date = $datetime->format('Y-m-d');
            if(!isset($rides_data[$date])){
                $rides_data[$date] = [];
            }
            $rides_data[$date][] = [
                'title' => get_the_title(),
                'link' => get_the_permalink(),
                'date' => $datetime->getTimestamp(),
                'time' => $datetime->getTimestamp(),
            ];
        }
        $data['rides'] = $rides_data;
        wp_reset_query();
    }
    $rows[] = $twig->render('rows/'.get_row_layout().'.html.twig', $data);
}
$data['rows'] = $rows;

$data['title'] = get_the_title();

// render
echo $twig->render('basic.html.twig', $data);
