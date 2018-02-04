<?php

function fix_ride_desc_for_ical($desc) {
    $desc2 = "";
    $tok = strtok($desc, " \n\t\r");
    while ($tok !== false) {
        if (0 === strpos($tok, 'http://') or 0 === strpos($tok, 'https://')) {
            $strings = explode("|", $tok, 3);
            $desc2 .= $strings[0];
        }
        else {
            $desc2 .= $tok;
        }
        $desc2 .= " ";
        $tok = strtok(" \n\t\r");
    }
    return $desc2;
}

function echo_ical_longline($tag, $line) {
    // Wordwrap function deletes the spaces it wraps on - to protect,
    // replace spaces with ASCII non-printing character SUB.
    $output = str_replace(' ', chr(26), $tag.':'.$line);

    // Break long strings at 70 characters.
    $output = wordwrap($output, 70, "\r\n ", true);

    // Restore protected spaces.
    $output = str_replace(chr(26), ' ', $output);

    echo $output;
    echo "\r\n";
}

function echo_ical_shortline($tag, $line) {
    echo $tag;
    echo ":";
    echo $line;
    echo "\r\n";
}

add_action('init', function() {
    add_feed('ride-calendar-ical', function() {

        header('Content-type: text/calendar');
        header('Content-Disposition: attachment; filename="ical.ics"');

        echo_ical_shortline('BEGIN', 'VCALENDAR');
        echo_ical_shortline('VERSION', '2.0');
        echo_ical_shortline('PRODID', '-//PWTC//PWTC Ride Calendar//EN');
        echo_ical_shortline('CALSCALE', 'GREGORIAN');

        $today = new DateTime(null, new DateTimeZone(supertheme_get_timezone_string()));

        // Query to fetch all ride in the next 7 days.
        $later = clone $today;
        $later->add(new DateInterval('P7D'));
        $query_args = [
            'posts_per_page' => -1,
            'post_type' => 'scheduled_rides',
            'meta_query' => [
                [
                    'key' => 'date',
                    'value' =>  [$today->format('Y-m-d 00:00:00'), $later->format('Y-m-d 23:59:59')],
                    'compare' => 'BETWEEN',
                    'type' => 'DATETIME'
                ],
            ],
            'orderby' => ['date' => 'ASC'],
        ];  
        
        // Query to fetch the next 10 rides.
        $query_args2 = [
            'posts_per_page' => 10,
            'post_type' => 'scheduled_rides',
            'meta_query' => [
                [
                    'key' => 'date',
                    'value' =>  $today->format('Y-m-d 00:00:00'),
                    'compare' => '>=',
                    'type'	=> 'DATETIME'
                ],
            ],
            'orderby' => ['date' => 'ASC']
        ];

        $rides_query = new WP_Query($query_args2);
        while($rides_query->have_posts()) {
            $rides_query->the_post();
            echo_ical_shortline('BEGIN', 'VEVENT');

            $tstamp = get_gmt_from_date(get_the_modified_date('Y-m-d H:i:s'));
            $timestamp = date('Ymd\THis\Z', strtotime($tstamp));
            $datetime = get_gmt_from_date(get_field('date'));
            $icaltime = date('Ymd\THis\Z', strtotime($datetime));
            echo_ical_shortline('UID', get_the_id().'@pwtc.com');
            echo_ical_shortline('DTSTAMP', $timestamp);
            echo_ical_shortline('DTSTART', $icaltime);

            $status = 'CONFIRMED';
            if (get_field('is_canceled')) {
                $status = 'CANCELLED';
            }
            echo_ical_shortline('STATUS', $status);

            $location = get_field('start_location');
            echo_ical_longline('LOCATION', $location['address']);
            echo_ical_shortline('GEO', $location['lat'].';'.$location['lng']);

            echo_ical_longline('SUMMARY', html_entity_decode(get_the_title()));

            //$description = fix_ride_desc_for_ical(trim(get_field('description', false, false)));
            //echo_ical_longline('DESCRIPTION', $description);

            echo_ical_longline('DESCRIPTION', 
                'Click <a href="' . get_the_permalink() . '">here</a> for details.');
            echo_ical_longline('URL', get_the_permalink());

            //foreach (get_field('ride_leaders') as $leader) {
            //    echo_ical_shortline('CONTACT', 
            //        $leader['user_firstname'].' '.$leader['user_lastname']);
            //}

            echo_ical_shortline('END', 'VEVENT');
        }
        wp_reset_query();

        echo_ical_shortline('END', 'VCALENDAR');

    });
});