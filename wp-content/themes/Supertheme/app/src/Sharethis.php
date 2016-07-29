<?php
namespace App;

class Sharethis extends \WP_Widget {

    /**
     * Register widget with WordPress.
     */
    function __construct() {
        parent::__construct(
            'sharethis',
            __( 'Share This', 'text_domain' ),
            array( 'description' => __( 'Share This', 'text_domain' ), )
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
        echo <<<HTML
            <span st_processed="yes" class="st_twitter_large" displaytext="Tweet">
                <span class="stButton" style="text-decoration:none;color:#000000;display:inline-block;cursor:pointer;">
                    <span style="background-image: url(&quot;http://w.sharethis.com/images/twitter_32.png&quot;);" class="stLarge"></span>
                </span>
            </span>
            <span st_processed="yes" class="st_facebook_large" displaytext="Facebook">
                <span class="stButton" style="text-decoration:none;color:#000000;display:inline-block;cursor:pointer;">
                    <span style="background-image: url(&quot;http://w.sharethis.com/images/facebook_32.png&quot;);" class="stLarge"></span>
                </span>
            </span>
            <span st_processed="yes" class="st_reddit_large" displaytext="Reddit">
                <span class="stButton" style="text-decoration:none;color:#000000;display:inline-block;cursor:pointer;">
                    <span style="background-image: url(&quot;http://w.sharethis.com/images/reddit_32.png&quot;);" class="stLarge"></span>
                </span>
            </span>
            <span st_processed="yes" class="st_sharethis_large" displaytext="ShareThis">
                <span class="stButton" style="text-decoration:none;color:#000000;display:inline-block;cursor:pointer;">
                    <span style="background-image: url(&quot;http://w.sharethis.com/images/sharethis_32.png&quot;);" class="stLarge"></span>
                </span>
            </span>
HTML;
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