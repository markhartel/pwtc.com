<?php
require_once __DIR__.'/../app/bootstrap.php';
use Timber\Timber;
use Timber\PostQuery;

/** @var $timber Timber */
$timber = $container->get('timber');
$context = $timber::get_context();
$template = 'base.html.twig';

if(is_singular())
{
    $context['post'] = $timber::get_post();

    if(is_front_page())
    {
        $template = 'pages/page.html.twig';
        $rows = [];
        while(have_rows('content_rows')) {
            the_row();
            if(get_row_layout() == "news") {
                $context['news'] = Timber::get_posts([
                    'posts_per_page' => 8,
                    'orderby' => array(
                        'date' => 'DESC'
                    )
                ]);
            }
            elseif(get_row_layout() == "rides")
            {
                $today = new DateTime(null, new DateTimeZone(pwtc_get_timezone_string()));
                $rides_query = new WP_Query([
                    'posts_per_page' => 6,
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
                        'date' => get_field('date'),
                        'time' => get_field('date'),
                        'is_canceled' => get_field('is_canceled'),
                    ];
                }
                $context['rides'] = $context['news'] = Timber::get_posts($rides_query);
                wp_reset_query();
            }
        }
        $context['rows'] = $rows;
    }
    else if(is_single())
    {
        if(is_page())
        {
            $template = 'pages/page.html.twig';
        }
        elseif(get_post_type() == "scheduled_rides")
        {
            $template = 'pages/single-ride.html.twig';
        }
        else
        {
            $template = 'two-column.html.twig';
        }
    }
}
elseif(get_post_type() == 'newsletter')
{
    $template = 'pages/archive.html.twig';
    $context['title'] = get_the_archive_title();
    if($context['title'] == "Archives") { $context['title'] = "Newsletters"; }
    $context['posts'] = new PostQuery();
}
else
{
    $template = 'pages/archive.html.twig';
    $context['title'] = get_the_archive_title();
    if($context['title'] == "Archives") { $context['title'] = "News"; }
    $context['posts'] = new PostQuery();
}

$timber::render($template, $context);
