<?php
require_once __DIR__.'/../app/bootstrap.php';
use Timber\Timber;
use Timber\PostQuery;

/** @var $timber Timber */
$timber = $container->get('timber');
$data = $timber::get_context();
$data['post'] = $timber::get_post();

$data['is_published'] = get_post_status() == 'publish';
$data['is_pending'] = get_post_status() == 'pending';

if(get_field('attach_map')) {
    $length = null;
    $maxLength = null;
    $terrain = [];
    $maps = [];
    foreach (get_field('maps') as $map) {
        $map_id = $map;

        if($length) {
            $length = min(get_field('length', $map_id), $length);
        } else {
            $length = get_field('length', $map_id);
        }


        if($maxLength) {
            $maxLength = max(get_field('max_length', $map_id), $maxLength, get_field('length', $map_id));
        } else {
            $maxLength = get_field('max_length', $map_id) ?: get_field('length', $map_id);
        }

        $terrain = array_merge($terrain, get_field('terrain', $map_id));
        $raw_map = get_field('maps', $map_id);
        $raw_map[0]['title'] = $map->post_title;
        $maps = array_merge($maps, $raw_map);
    }
    if($length == $maxLength) {
        $maxLength = null;
    }
    $data['terrain'] = array_unique($terrain);
    $data['length'] = $length;
    $data['max_length'] = $maxLength;
    $data['maps'] = $maps;
}
else {
    $data['terrain'] = get_field('terrain');
    $data['length'] = get_field('length');
    $data['max_length'] = get_field('max_length');
    $data['maps'] = false;
}

// Fetch the ride's description, break it into tokens delemited by whitespace
// and look for strings that start with "http://" or "https://". Convert those
// strings to HTML links using the following translation rules:
// 1) http://foo.bar.com becomes <a href="http://foo.bar.com">http://foo.bar.com</a>
// 2) http://foo.bar.com|foobar becomes <a href="http://foo.bar.com">foobar</a>
// 3) http://foo.bar.com|foobar|. becomes <a href="http://foo.bar.com">foobar</a>.
$message = get_field('description');
$desc = wp_kses($message, array('br' => array(), 'em' => array(), 'strong' => array()));
$desc2 = "";
$tok = strtok($desc, " \n\t\r");
while ($tok !== false) {
    if (0 === strpos($tok, 'http://') or 0 === strpos($tok, 'https://')) {
        $idx = strpos($tok, '<');
        if ($idx !== false) {
	        $link = substr($tok, 0, $idx);
	        $rem = substr($tok, $idx);
	        $tok = $link;
        }
        else {
	        $rem = "";
        }
        $strings = explode("|", $tok, 3);
        $ref = $strings[0];
        $label = $ref;
        $end = "";
        if (count($strings) > 1) {
            if (strlen($strings[1]) > 0) {
                $label = str_replace("_", " ", $strings[1]);
            }
            if (count($strings) > 2) {
                if (strlen($strings[2]) > 0) {
                    $end = $strings[2];
                }
            }
        }
        $desc2 .= '<a href="' . $ref . '" target="_blank">' . $label . '</a>' . $end . $rem;
    }
    else {
        $desc2 .= $tok;
    }
    $desc2 .= " ";
    $tok = strtok(" \n\t\r");
}
$data['description'] = $desc2;
$data['current_url'] = get_permalink();

// render
$timber->render('pages/single-ride_template.html.twig', $data);
