<?php
require_once __DIR__ . '/../app/bootstrap.php';

use Timber\Timber;
use Timber\PostQuery;

/** @var $timber Timber */
$timber = $container->get('timber');
$context = $timber::get_context();

// time for a little magic
// note that current date is refering to the selected month not the current date.
// if none is selected it will default to the current date

// get timezone
$timezone = new \DateTimeZone(pwtc_get_timezone_string());

$daily_view = false;
if (isset($_GET['view']) && $_GET['view']) {
    if ($_GET['view'] == 'daily') {
        $daily_view = true;
    }
}

if ($daily_view) {
    $template = 'pages/archive-rides-map.html.twig';

    // get current date (use WP 'current_time' function to return local time instead of UTC time)
    $current_datetime = new DateTime(date('Y-m-d', current_time('timestamp')), $timezone);
    if (isset($_GET['date']) && $_GET['date']) {
        $valid_date = DateTime::createFromFormat('Y-m-d', $_GET['date']);
        if ($valid_date) {
            $current_datetime = $valid_date;
        }
    }

    // clone the current date and subtract one day to get the previous day
    $previous_day_datetime = clone $current_datetime;
    $previous_day_datetime->sub(new DateInterval('P1D'));

    // clone the current date and add one day to get the next day
    $next_day_datetime = clone $current_datetime;
    $next_day_datetime->add(new DateInterval('P1D'));

    $context['day_pretty'] = $current_datetime->format('l M j Y');
    $context['month_val'] = $current_datetime->format('Y-m');
    $context['month_name'] = $current_datetime->format('F');
    $context['day_previous_val'] = $previous_day_datetime->format('Y-m-d');
    $context['day_previous'] = $previous_day_datetime->format('l');
    $context['day_next_val'] = $next_day_datetime->format('Y-m-d');
    $context['day_next'] = $next_day_datetime->format('l');

    $query_args = [
        'posts_per_page' => -1,
        'post_type' => 'scheduled_rides',
        'meta_query' => [
            [
                'key' => 'date',
                'value' => [$current_datetime->format('Y-m-d 00:00:00'), $current_datetime->format('Y-m-d 23:59:59')],
                'compare' => 'BETWEEN',
                'type' => 'DATETIME'
            ],
        ],
        'orderby' => ['date' => 'ASC'],
    ];

    $query = new WP_Query($query_args);
    $locations = [];
    $num_rides = 0;
    while ($query->have_posts()) {
        $query->the_post();
        $num_rides++;
        $start_location = get_field('start_location');
        $address = $start_location['address'];
        $lat = $start_location['lat'];
        $lng = $start_location['lng'];
        $location = $lat . '|' . $lng;
        if (!isset($locations[$location])) {
            $locations[$location] = [
                'address' => $address,
                'lat' => $lat,
                'lng' => $lng,
                'events' => []
            ];
        }
        $locations[$location]['events'][] = [
            'title' => get_the_title(),
            'link' => get_the_permalink(),
            'time' => DateTime::createFromFormat('Y-m-d H:i:s', get_field('date'))->getTimestamp(),
            'is_canceled' => get_field('is_canceled')
        ];
    }
    wp_reset_postdata();

    if ($num_rides == 1) {
        $context['ride_msg'] = 'is one ride';
    } else if ($num_rides > 1) {
        $context['ride_msg'] = 'are ' . $num_rides . ' rides';
    }

    $context['locations'] = $locations;
} else {
    $template = 'pages/archive-rides-calendar.html.twig';

    // get current month (use WP 'current_time' function to return local time instead of UTC time)
    $current_datetime = new DateTime(date('Y-m-01', current_time('timestamp')), $timezone);
    $context['invalid_date'] = false;
    if (isset($_GET['month']) && $_GET['month']) {
        $valid_date = DateTime::createFromFormat('Y-m-d', $_GET['month'] . '-01');
        if (!$valid_date) {
            $context['invalid_date'] = true;
        } else {
            $current_datetime = $valid_date;
        }
    }

    // get current time
    $now_datetime = new DateTime(null, $timezone);

    // get the first and last days of the selected month so we can get the previous/next month by adding or subtracting one day
    // using months would add/suntract 31 days which would cause issues for february
    // we will also need these to fill the calendar with the dates of the other monthsÒ
    $current_first_day = new DateTime($current_datetime->format('Y-m') . '-01', $timezone);
    $current_last_day = new DateTime($current_datetime->format('Y-m-t'), $timezone);

    // clone the first day of the month and subtract one day to get the previous month
    $previous_month_datetime = clone $current_first_day;
    $previous_month_datetime->sub(new DateInterval('P1D'));

    // clone the last day of the month and add one day to get the next month
    $next_month_datetime = clone $current_last_day;
    $next_month_datetime->add(new DateInterval('P1D'));

    // set some data for twig
    $context['month_current'] = $current_datetime->format('F');
    $context['year_current'] = $current_datetime->format('Y');
    $context['month_previous'] = $previous_month_datetime->format('F');
    $context['month_previous_val'] = $previous_month_datetime->format('Y-m');
    $context['month_next'] = $next_month_datetime->format('F');
    $context['month_next_val'] = $next_month_datetime->format('Y-m');
    $context['month_current_numeric'] = $current_datetime->format('Ym');
    $context['month_now_numeric'] = $now_datetime->format('Ym');

    // clone the first day of the month and subtract days to retard it to the previous Sunday
    $calendar_start_datetime = clone $current_first_day;
    $calendar_start_datetime->sub(new DateInterval('P' . $current_first_day->format('w') . 'D'));

    // clone the last day of the month and add days to advance it to the next Saturday
    $calendar_end_datetime = clone $current_last_day;
    $calendar_end_datetime->add(new DateInterval('P' . (6 - $current_last_day->format('w')) . 'D'));

    // create start and end dates to build the calandar array
    $loop_datetime = clone $calendar_start_datetime;
    $loop_until_datetime = clone $calendar_end_datetime;

    // get all the scheduled events in the time range
    $context['args'] = [
        'month' => $current_datetime->format('Y-m'),
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
                'value' => [$loop_datetime->format('Y-m-d 00:00:00'), $loop_until_datetime->format('Y-m-d 23:59:59')],
                'compare' => 'BETWEEN',
                'type' => 'DATETIME'
            ],
        ],
        'orderby' => ['date' => 'ASC'],
    ];
    // search term
    if (isset($_GET['title'])) {
        $context['args']['s'] = $query_args['s'] = $_GET['title'];
    }
    // search terrain
    if (isset($_GET['terrain'])) {
        switch ($_GET['terrain']) {
            case 'a':
            case 'b':
            case 'c':
            case 'd':
            case 'e':
                $context['args']['terrain'] = $_GET['terrain'];
                $query_args['meta_query'][] = [
                    'key' => 'terrain',
                    'value' => $_GET['terrain'],
                    'compare' => 'LIKE',
                ];
                break;
            default:
                ;
                break;
        }
    }
    // search speed
    if (isset($_GET['speed'])) {
        switch ($_GET['speed']) {
            case 'slow':
            case 'leisurely':
            case 'moderate':
            case 'fast':
                $context['args']['speed'] = $_GET['speed'];
                $query_args['meta_query'][] = [
                    'key' => 'pace',
                    'value' => $_GET['speed'],
                    'compare' => '=',
                ];
                break;
            default:
                ;
                break;
        }
    }
    // search length
    if (isset($_GET['length'])) {
        switch ($_GET['length']) {
            case 1:
                $context['args']['length'] = $_GET['length'];
                $query_args['meta_query'][] = [
                    'key' => 'length',
                    'value' => [0, 2],
                    'compare' => 'BETWEEN',
                ];
                break;
            case 2:
                $context['args']['length'] = $_GET['length'];
                $query_args['meta_query'][] = [
                    'key' => 'length',
                    'value' => [2, 5],
                    'compare' => 'BETWEEN',
                ];
                break;
            case 3:
                $context['args']['length'] = $_GET['length'];
                $query_args['meta_query'][] = [
                    'key' => 'length',
                    'value' => [5, 10],
                    'compare' => 'BETWEEN',
                ];
                break;
            case 4:
                $context['args']['length'] = $_GET['length'];
                $query_args['meta_query'][] = [
                    'key' => 'length',
                    'value' => 10,
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
    while ($query->have_posts()) {
        $query->the_post();
        $datetime = DateTime::createFromFormat('Y-m-d H:i:s', get_field('date'));
        $date = $datetime->format('Y-m-d');
        if (!isset($scheduled_rides[$date])) {
            $scheduled_rides[$date] = [];
        }
        $scheduled_rides[$date][] = [
            'title' => get_the_title(),
            'link' => get_the_permalink(),
            'date' => $datetime->getTimestamp(),
            'time' => $datetime->getTimestamp(),
            'is_canceled' => get_field('is_canceled'),
        ];
    }
    wp_reset_postdata();
    // build the array
    $calendar = [];
    while ($loop_datetime->format('ymd') <= $loop_until_datetime->format('ymd')) {
        $day = [
            'date' => $loop_datetime->getTimestamp(),
            'previous' => ($loop_datetime->format('ymd') < $now_datetime->format('ymd')),
            'next' => ($loop_datetime->format('n') > $current_datetime->format('n')),
            'weekend' => ($loop_datetime->format('N') >= 6),
            'current' => ($loop_datetime->format('y/m/d') == $now_datetime->format('y/m/d')),
            'events' => []
        ];
        if (isset($scheduled_rides[$loop_datetime->format('Y-m-d')])) {
            $day['events'] = $scheduled_rides[$loop_datetime->format('Y-m-d')];
        }
        $calendar[] = $day;
        $loop_datetime->add(new DateInterval('P1D'));
    }

    // set data for twig
    $context['calendar'] = $calendar;
}

$timber->render($template, $context);
