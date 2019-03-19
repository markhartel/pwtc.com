<?php
// custom column for scheduled rides
add_filter('manage_posts_columns', function($defaults) {
    if(get_current_screen()->id == "edit-scheduled_rides") {
        $defaults['schedule_date'] = 'Schedule Date';
    }
    return $defaults;
});
add_filter('manage_edit-scheduled_rides_sortable_columns', function ($sortable_columns) {
    $sortable_columns[ 'schedule_date' ] = 'schedule_date';
    return $sortable_columns;
});
add_action('pre_get_posts', function ($query) {
    if(!is_admin()) {
        return;
    }

    $orderby = $query->get('orderby');
    if( 'schedule_date' == $orderby ) {
        $query->set('meta_key','date');
        $query->set('orderby','meta_value');
    }
});
add_action('manage_posts_custom_column', function($column_name, $post_ID){
    if($column_name != 'schedule_date') {
        return;
    }

    $date = DateTime::createFromFormat('Y-m-d H:i:s', get_field('date', $post_ID, false));

    if(!$date) {
        return;
    }

    echo $date->format('D F j, Y \a\t g:i a');
}, 10, 2);
