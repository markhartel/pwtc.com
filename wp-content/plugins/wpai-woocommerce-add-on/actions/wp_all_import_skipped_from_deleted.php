<?php
/**
 * @param $skipp_from_deletion
 * @param $import
 */
function pmwi_wp_all_import_skipped_from_deleted( $skipp_from_deletion, $import ){

    if ( $import->options['custom_type'] == 'product' && ! empty($skipp_from_deletion) && class_exists('WooCommerce') )
    {
        global $wpdb;

        foreach ( $skipp_from_deletion as $pid ){

            $post_to_delete = get_post($pid);

            if ( $post_to_delete->post_type == 'product' )
            {
                $children = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $wpdb->posts . " WHERE post_type = %s AND post_parent = %d;", 'product_variation', $pid) );

                if ( count($children) ){
                    wp_delete_post($pid);
                }
            }
        }
    }
}