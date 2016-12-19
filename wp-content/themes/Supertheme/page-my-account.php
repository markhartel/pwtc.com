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

// use the api to find the user
civicrm_initialize();
$result = civicrm_api3('contact', 'get', array(
    'sequential' => 1,
    'email' => $current_user->user_email,
));

// get the user object from civi
$contact_id = $result['values'][0]['contact_id'];
$params = array('contact_id' => $contact_id);
$defaults =[];
$civi_contact = CRM_Contact_BAO_Contact::retrieve($params, $defaults);
$data['user'] = $civi_contact;

// get the membership information
$memberships = civicrm_api3('Membership', 'get', array(
    'sequential' => 1,
    'contact_id' => $contact_id,
));
$data['membership'] = [];
foreach($memberships['values'] as $membership) {
    if(isset($data['membership']['join_date']) && $data['membership']['join_date']) {
        $data['membership']['join_date'] = min($data['membership']['join_date'], $membership['join_date']);
    } else {
        $data['membership']['join_date'] = $membership['join_date'];
    }

    if(isset($data['membership']['start_date']) && $data['membership']['start_date']) {
        $data['membership']['start_date'] = min($data['membership']['start_date'], $membership['start_date']);
    } else {
        $data['membership']['join_date'] = $membership['join_date'];
    }

    if(isset($data['membership']['end_date']) && $data['membership']['end_date']) {
        $data['membership']['end_date'] = max($data['membership']['end_date'], $membership['end_date']);
    } else {
        $data['membership']['end_date'] = $membership['end_date'];
        $data['membership']['membership_name'] = $membership['membership_name'];
    }
}

// use the api to find the households
$data['household_members'] = [];
if($civi_contact->relationship["data"]) {
    $relationships = [];
    foreach($civi_contact->relationship["data"] as $relationship) {
        $relationships[] = $relationship["cid"];
    }
    $result = civicrm_api3('Relationship', 'get', array(
        'sequential' => 1,
        'relationship_type_id' => array('IN' => array(6, 7)),
        'contact_id_a' => array("!=" => $result['values'][0]['contact_id']),
        'contact_id_b' => array("IN" => $relationships),
    ));
    // get data on all users in the household
    if($result['values']) {
        foreach($result['values'] as $member) {
            $member_result = civicrm_api3('contact', 'get', array(
                'sequential' => 1,
                'contact_id' => $member['contact_id_a'],
            ));

            if($member_result['values']) {
                $params = array('contact_id' => $member_result['values'][0]['contact_id']);
                $defaults = array();
                $data['household_members'][] = ['relationship_id' => $member['id'], 'data' => CRM_Contact_BAO_Contact::retrieve($params, $defaults)];
            }
        }
    }
}
$data['contact_id'] = $contact_id;
// render
echo $twig->render('my-account.html.twig', $data);
