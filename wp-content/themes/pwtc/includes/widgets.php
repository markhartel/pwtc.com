<?php
add_action( 'widgets_init', function(){
    register_widget( 'PWTC\Sharethis' );
    register_widget( 'PWTC\UpcomingRides' );
});