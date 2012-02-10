<?php
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
			global $umwaa;
			extract( $args );
			$instance = wp_parse_args( $instance, $this->defaults() );
			
			$alert = $umwaa->shortcode( $instance );
			if ( empty( $alert ) )
				return false;
			
			$title = apply_filters('widget_title', $instance['title'] );
			
			echo $before_widget;
			if ( ! empty( $title ) )
				echo $before_title . $title . $after_title;
			echo '
				<div class="umw-flag-content-widget-wrapper umw-red-flag-wrapper has_icon">
					<div class="umw-icon-wrapper umw-umw-mark-icon-wrapper">';
			echo $alert;
			echo '
					</div>
				</div>';
			echo $after_widget;
		}
		
	}
?>