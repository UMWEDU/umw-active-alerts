<?php
if ( ! class_exists( 'active_alert_widget' ) ) {
	class active_alert_widget extends WP_Widget {
		function __construct() {
			/**
			 * Make sure the global instance of the umw_active_alerts object exists
			 */
			global $umwaa;
			if ( ! isset( $umwaa ) || ! is_object( $umwaa ) )
				return;
			
			$this->WP_Widget( 'umw-alert', 'UMW Alerts', array( 'classname' => 'umw-alert', 'description' => __( 'If there is a current active alert in the selected category, this widget will display that alert. If there are no active alerts in the selected category, nothing will be displayed.' ) ) );
		}
		
		function defaults() {
			return apply_filters( 'active-alert-widget-defaults', array(
				'category' => 'uncategorized', 
			) );
		}
		
		function form( $instance ) {
			$instance = wp_parse_args( $instance, $this->defaults() );
			global $umwaa, $switched;
			switch_to_blog( $umwaa->ad_id );
			$cats = get_terms( 'category', array( 
				'hide_empty' => false,
			) );
			restore_current_blog();
			
?>
<p><label for="<?php $this->get_field_id( 'category' ) ?>"><?php _e( 'Display alerts from the following category:' ) ?></label>
	<select name="<?php echo $this->get_field_name( 'category' ) ?>" id="<?php echo $this->get_field_id( 'category' ) ?>" class="widefat">
<?php
			foreach ( $cats as $cat ) {
?>
		<option value="<?php echo $cat->slug ?>"<?php selected( $cat->slug, $instance['category'] ) ?>><?php echo $cat->name ?></option>
<?php
			}
?>
	</select></p>
<?php
		}
		
		function update( $new_instance, $old_instance ) {
			$instance = wp_parse_args( $old_instance, $this->defaults() );
			
			$instance['category'] = ! empty( $new_instance['category'] ) ? $new_instance['category'] : null;
			
			return $instance;
		}
		
		function widget( $args, $instance ) {
			extract( $args );
			$instance = wp_parse_args( $instance, $this->defaults() );
			
			$alert = $this->get_current_alert( $instance );
			if ( empty( $alert ) )
				return false;
			
			$title = apply_filters('widget_title', $instance['title'] );
			
			echo $before_widget;
			if ( ! empty( $title ) )
				echo $before_title . $title . $after_title;
			echo $alert;
			echo $after_widget;
		}
		
		function get_current_alert( $instance ) {
			$alerts = array();
			$alert = false;
			if ( function_exists( 'get_mnetwork_option' ) ) {
				$alerts = get_mnetwork_option( 'current_local_alerts', array() );
				if ( array_key_exists( $instance['category'], $alerts ) )
					return $alerts[$instance['category']];
			}
			
			global $umwaa;
			if ( ! isset( $umwaa ) || ! is_object( $umwaa ) )
				return false;
			
			switch_to_blog( $umwaa->ad_id );
			$posts = get_posts( array( 'category' => $instance['category'], 'numberposts' => 1, 'post_type' => 'post', 'post_status' => 'publish', 'orderby' => 'post_date', 'order' => 'DESC' ) );
			if ( empty( $posts ) ) {
				$alerts[$instance['category']] = false;
				update_mnetwork_option( 'current_local_alerts', $alerts );
				restore_current_blog();
				return false;
			}
			
			$post = array_shift( $posts );
			
			$alert = '
			<div class="current-alert">
				<h3 class="alert-title">
					<a href="' . get_permalink( $post->ID ) . '">' . apply_filters( 'the_title', $post->post_title ) . '</a>
				</h3>
				<p class="alert-date">
					<a href="' . get_permalink( $post->ID ) . '?p=' . $post->ID . '">[' . __( 'Posted: ' ) . get_post_time( get_option( 'date_format' ), false, $post ) . ' at ' . get_post_time( get_option( 'time_format' ), false, $post ) . ']</a>
				</p>
			</div>';
			
			$alerts[$instance['category']] = $alert;
			update_mnetwork_option( 'current_local_alerts', $alerts );
			restore_current_blog();
			
			return $alert;
		}
	}
}
?>