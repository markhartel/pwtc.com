<?php
/**
 * Template Name: Two Columns
 *
 */
require_once __DIR__.'/app/bootstrap.php';
// get services
/** @var \Symfony\Component\DependencyInjection\Container $container */
/** @var Twig_Environment $twig */
$twig = $container->get("twig.environment");

// preg global twig data
$data = require_once __DIR__ . '/app/bootstrap-theme.php';

// time for a little magic
// note that current date is refering to the selected month not the current date.
// if none is selected it will default to the current date

// get current month
$current_datetime = new DateTime(date('F'));
$data['invalid_date'] = false;
if(isset($_GET['month']) && $_GET['month']) {
    $valid_date = DateTime::createFromFormat('M', $_GET['month']);
    if (!$valid_date) {
        $data['invalid_date'] = true;
    } else {
        $current_datetime = $valid_date;
    }
}

// get the first and last days of the selected month so we can get the previous/next month by adding or subtracting one day
// using months would add/suntract 31 days which would cause issues for february
// we will also need these to fill the calendar with the dates of the other months
$current_first_day = new DateTime($current_datetime->format('F').' 1st');
$current_last_day = new DateTime($current_datetime->format('F t'));
$previous_month_datetime = $current_first_day->sub(new DateInterval('P1D'));
$next_month_datetime = $current_last_day->add(new DateInterval('P1D'));

// set some data for twig
$data['month_current'] = $current_datetime->format('F');
$data['year_current'] = $current_datetime->format('Y');
$data['month_previous'] = $previous_month_datetime->format('F');
$data['month_next'] = $next_month_datetime->format('F');
$data['month_current_numeric'] = $current_datetime ->format('n');

// get the first and last days for the calendar
$calendar_start_datetime = $current_first_day->sub(new DateInterval('P'.$current_first_day->format('w').'D'));
$calendar_end_datetime = $current_last_day->add(new DateInterval('P'.(6-$current_last_day->format('w')).'D'));

// create values to build the calandar array
$now_datetime = new DateTime();
$loop_datetime = $current_first_day;
$loop_until_datetime = $current_last_day->add(new DateInterval('P1D'));
$current_last_day->sub(new DateInterval('P1D'));

// get all the scheduled events in the time range
$data['args'] = [
    'month' => $current_datetime->format('F'),
    // populated later
    's' => '', 
    'terrain' => '',
    'speed' => '',
    'length' => '',
];
$query_args = [
    'posts_per_page' => -1,
    'post_type' => 'scheduled_rides',
    'meta_query' => [
        [
            'key' => 'date',
            'value' =>  [$loop_datetime->format('Y-m-d 00:00:00'), $loop_until_datetime->format('Y-m-d 00:00:00')],
            'compare' => 'BETWEEN',
            'type' => 'DATETIME'
        ],
    ],
    'orderby' => ['date' => 'ASC'],
];
// search term
if(isset($_GET['s']))
{
    $data['args']['s'] = $query_args['s'] = $_GET['s'];
}
// search terrain
if(isset($_GET['terrain'])){
    switch($_GET['terrain']) {
        case 'a':
        case 'b':
        case 'c':
        case 'd':
        case 'e':
            $data['args']['terrain'] = $_GET['terrain'];
            $query_args['meta_query'][] = [
                'key' => 'terrain',
                'value' =>  $_GET['terrain'],
                'compare' => 'LIKE',
            ];
            break;
        default:
            ;
        break;
    }
}
// search speed
if(isset($_GET['speed'])){
    switch($_GET['speed']) {
        case 'slow':
        case 'leisurely':
        case 'moderate':
        case 'fast':
            $data['args']['speed'] = $_GET['speed'];
            $query_args['meta_query'][] = [
                'key' => 'pace',
                'value' =>  $_GET['speed'],
                'compare' => '=',
            ];
            break;
        default:
            ;
            break;
    }
}
// search length
if(isset($_GET['length'])){
    switch($_GET['length']) {
        case 1:
            $data['args']['length'] = $_GET['length'];
            $query_args['meta_query'][] = [
                'key' => 'length',
                'value' =>  [0, 2],
                'compare' => 'BETWEEN',
            ];
            break;
        case 2:
            $data['args']['length'] = $_GET['length'];
            $query_args['meta_query'][] = [
                'key' => 'length',
                'value' =>  [2, 5],
                'compare' => 'BETWEEN',
            ];
            break;
        case 3:
            $data['args']['length'] = $_GET['length'];
            $query_args['meta_query'][] = [
                'key' => 'length',
                'value' =>  [5, 10],
                'compare' => 'BETWEEN',
            ];
            break;
        case 4:
            $data['args']['length'] = $_GET['length'];
            $query_args['meta_query'][] = [
                'key' => 'length',
                'value' =>  10,
                'compare' => '>',
            ];
            break;
        default:
            ;
            break;
    }
}
// fill scheduled rides array with results
$query = new WP_Query($query_args);
$scheduled_rides = [];
while($query->have_posts()) {
    $query->the_post();
    $datetime = DateTime::createFromFormat('Y-m-d H:i:s', get_field('date'));
    $date = $datetime->format('Y-m-d');
    if(!isset($scheduled_rides[$date])){
        $scheduled_rides[$date] = [];
    }
    $scheduled_rides[$date][] = [
        'title' => get_the_title(),
        'link' => get_the_permalink(),
        'date' => $datetime->getTimestamp(),
        'time' => $datetime->getTimestamp(),
    ];
}
wp_reset_postdata();
// build the array
$calendar = [];
while($loop_datetime->format('ymd') <= $loop_until_datetime->format('ymd')){
    $day = [
        'date' => $loop_datetime->getTimestamp(),
        'previous' => ($loop_datetime->format('ymd') < $now_datetime->format('ymd')),
        'next' => ($loop_datetime->format('n') > $current_datetime->format('n')),
        'weekend' => ($loop_datetime->format('N') >= 6),
        'current' => ($loop_datetime->format('y/m/d') == $now_datetime->format('y/m/d')),
        'events' => []
    ];
    if(isset($scheduled_rides[$loop_datetime->format('Y-m-d')])) {
        $day['events'] = $scheduled_rides[$loop_datetime->format('Y-m-d')];
    }
    $calendar[] = $day;
    $loop_datetime->add(new DateInterval('P1D'));
}

// set data for twig
$data['calendar'] = $calendar;

// render
echo $twig->render('ride-calendar.html.twig', $data);
