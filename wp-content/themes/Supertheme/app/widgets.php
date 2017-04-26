<?php
add_action( 'widgets_init', function(){
    register_widget( 'App\Sharethis' );
    register_widget( 'App\UpcomingRides' );
});