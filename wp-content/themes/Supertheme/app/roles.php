<?php
add_action('init', function() {
    add_role('current_member', 'Current Member', ['read' => false]);
    add_role('expired_member', 'Expired Member', ['read' => false]);
    add_role('ride_leader', 'Ride Leader', ['read' => true]);
    add_role('ride_captain', 'Ride Captain', ['read' => true]);

    $ride_captain_role = get_role('ride_captain');
    $additional_ride_captain_roles = [
        'access_uploaded_files',
        'delete_others_rides',
        'delete_private_rides',
        'delete_published_rides',
        'delete_rides',
        'edit_others_rides',
        'edit_private_rides',
        'edit_published_rides',
        'edit_rides',
        'publish_rides',
        'read_private_rides',
        'register_for_events',
        'sign_civicrm_petition',
        'upload_files',
        'view_event_info',
        'access_all_custom_data',
        'access_civimail_subscribe_unsubscribe_pages',
        'make_online_contributions',
        'profile_create',
        'profile_edit',
        'profile_view',
        'view_public_civimail_content',
    ];
    foreach ($additional_ride_captain_roles as $role) {
        $ride_captain_role->add_cap($role);
    }

    $editor_role = get_role('editor');
    $additional_editor_roles = [
        'delete_others_rides',
        'delete_private_rides',
        'delete_published_rides',
        'delete_rides',
        'edit_others_rides',
        'edit_private_rides',
        'edit_published_rides',
        'edit_rides',
        'publish_rides',
        'read_private_rides',
    ];
    foreach ($additional_editor_roles as $role) {
        $editor_role->add_cap($role);
    }

    $admin_role = get_role('administrator');
    $additional_admin_roles = [
        'manage_woocommerce' => true,
        'view_woocommerce_reports' => true,
    ];
    foreach ($additional_admin_roles as $role) {
        $admin_role->add_cap($role);
    }
});

add_action('template_redirect', function(){
    $current_post_type = get_post_type();
    if(in_array($current_post_type, ['ride_maps', 'ride_templates']) && !is_user_logged_in()) {
        wp_safe_redirect(get_site_url());
    }
});