<?php
namespace PWTC;

class UpcomingRides extends \WP_Widget {

    /**
     * Register widget with WordPress.
     */
    function __construct() {
        parent::__construct(
            'rides',
            __( 'Upcoming Rides', 'text_domain' ),
            array( 'description' => __( 'Upcoming Rides', 'text_domain' ), )
        );
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget( $args, $instance ) {
        echo $args['before_widget'];
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
        }

        $today = new \DateTime(null, new \DateTimeZone(pwtc_get_timezone_string()));
        echo "<p class='help-text'>(as of " . $today->format('n/d/Y g:i A') . ")</p>";
        $rides_query = new \WP_Query([
            'posts_per_page'	=> 6,
            'post_type' => 'scheduled_rides',
            'meta_query' => [
                [
                    'key' => 'date',
                    'value' =>  $today->format('Y-m-d 00:00:00'),
                    'compare' => '>=',
                    'type'	=> 'DATETIME'
                ],
            ],
            'orderby' => ['date' => 'ASC'],
        ]);
        if($rides_query->have_posts()) {
            echo "<ul class='vertical menu'>";
            while ($rides_query->have_posts()) {
                $rides_query->the_post();
                $class = "";
                if(get_field('is_canceled')) {
                    $class .= 'class="canceled"';
                }
                echo "<li><a href='" . get_the_permalink() . "' $class>" . get_the_title() . "</a></li>";
            }
            echo "</ul>";
            echo "<a href='" . site_url('scheduled_rides') . "' class='dark button'>More Rides</a>";
        } else {
            echo "No upcoming rides";
        }
        wp_reset_query();
        echo $args['after_widget'];
    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'New title', 'text_domain' );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( esc_attr( 'Title:' ) ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

        return $instance;
    }

}
