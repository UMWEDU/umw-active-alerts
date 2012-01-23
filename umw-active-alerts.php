<?php
/*
Plugin Name: UMW Active Alerts
Description: Inserts the active alert on the home page if there is an active alert.
Version: 0.2a
Author: Curtiss Grymala
Author URI: http://ten-321.com/
License: GPL2
*/
if( !class_exists( 'umw_active_alerts' ) ) {
	class umw_active_alerts {
		var $ad_id = 0;
		var $ad_cat = null;
		
		function __construct() {
			$this->set_values();
			add_action( 'wp_ajax_nopriv_check_active_alert', array( $this, 'insert_active_alert' ) );
			add_action( 'wp_ajax_check_active_alert', array( $this, 'insert_active_alert' ) );
			if( !is_admin() ) {
				add_action( 'wp_print_styles', array( $this, 'print_styles' ) );
				add_action( 'wp_print_scripts', array( $this, 'localize_js' ) );
			}
			add_action( 'save_post', array( $this, 'clear_active_alert' ), 99, 2 );
			add_action( 'trash_post', array( $this, 'clear_active_alert' ), 99, 2 );
		}
		
		function set_values() {
			$this->ad_id	= $this->get_value( 'umw_advisories_blog_id' );
			if( empty( $this->ad_id ) )
				$this->ad_id = 2281;
			
			$this->ad_cat	= $this->get_value( 'umw_advisories_active_cat' );
			if( empty( $this->ad_cat ) )
				$this->ad_cat = 'current';
		}
		
		/* Get value functon
		------------------------------------------------------------ */
		function get_value( $key = '' ) {
			return stripslashes( htmlspecialchars( function_exists( 'get_mnetwork_option' ) ? get_mnetwork_option( $key ) : get_site_option( $key ) ) );
		}
		
		function insert_active_alert() {
			header( "Content-Type: application/json" );
			if( false === ( $aa = $this->active_alert() ) )
				echo json_encode( array( 'html' => -1 ) );
			
			$h = array( 'html' => $aa, 'ID' => $GLOBALS['post']->ID );
			echo json_encode( $h );
			
			exit;
		}
		
		function print_styles() {
			wp_enqueue_style( 'umw-active-alerts', plugins_url( '/css/umw-active-alerts.css', __FILE__ ), array(), '0.1.11a', 'all' );
		}
		
		function localize_js() {
			wp_enqueue_script( 'umw-active-alerts', plugins_url( '/js/umw-active-alerts.js', __FILE__ ), array( 'jquery' ), '0.1.18a', true );
			wp_localize_script( 'umw-active-alerts', 'umwActAlerts', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		}
		
		/**
		 * Check for active advisories
		 */
		function active_alert() {
			if( !function_exists( 'get_mnetwork_option' ) ) {
				error_log( '[Active Alerts Debug]: The get_mnetwork_option function is not yet defined' );
			}
			if( function_exists( 'get_mnetwork_option' ) && $alert = get_mnetwork_option( 'umw_active_alert', false ) )
				if( is_array( $alert ) && array_key_exists( 'content', $alert ) )
					return $alert['content'];
			
			global $wpdb;
			
			if( $this->ad_id != $GLOBALS['blog_id'] )
				$org_blog = $wpdb->set_blog_id( $this->ad_id );
				
			$args = array(
				'post_type'		=> 'post', 
				'post_status'	=> 'publish', 
				'category_name'	=> $this->ad_cat,
				'posts_per_page'=> 1,
				'orderby'		=> 'post_date',
				'order'			=> 'DESC',
			);
			$alerts = get_posts( $args );
			
			if( isset( $org_blog ) )
				$wpdb->set_blog_id( $org_blog );
			
			if( empty( $alerts ) ) {
				if( function_exists( 'update_mnetwork_option' ) )
					update_mnetwork_option( 'umw_active_alert', array( 'content' => false, 'ID' => 0 ) );
				return false;
			}
			
			$alert = array_shift( $alerts );
			$bloginfo = get_blog_details( $this->ad_id );
			$alert_excerpt = empty( $alert->post_excerpt ) ? strip_tags( $alert->post_content ) : strip_tags( $alert->post_excerpt );
			if( str_word_count( $alert_excerpt ) > 16 ) {
				$alert_excerpt = explode( ' ', $alert_excerpt );
				$alert_excerpt = array_slice( $alert_excerpt, 0, 15 );
				$alert_excerpt = implode( ' ', $alert_excerpt ) . '&hellip;';
			}
			$a = '<div class="active-alert"><span class="alert-icon alert-icon-left"></span><h1 class="alert-title"><a href="' . trailingslashit( $bloginfo->siteurl ) . '?p=' . $alert->ID . '">' . apply_filters( 'the_title', $alert->post_title ) . '</h1><div class="alert-content"><a href="' . trailingslashit( $bloginfo->siteurl ) . '?p=' . $alert->ID . '">' . $alert_excerpt . '</a></div><span class="alert-icon alert-icon-right"></span></div>';
			if( function_exists( 'update_mnetwork_option' ) )
				update_mnetwork_option( 'umw_active_alert', array( 'content' => $a, 'ID' => $alert->ID ) );
			
			return $a;
		}
		
		function clear_active_alert( $post_ID, $post=null ) {
			if( !function_exists( 'delete_mnetwork_option' ) )
				return $post_ID;
			
			if( $GLOBALS['blog_id'] != $this->ad_id )
				return $post_ID;
			if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return $post_ID;
			if( 'auto-draft' == $post->post_status || 'inherit' == $post->post_status )
				return $post_ID;
			if( empty( $post ) )
				$post = get_post( $post_ID );
			if( is_object( $post ) && 'post' != $post->post_type )
				return $post_ID;
			
			delete_mnetwork_option( 'umw_active_alert' );
		}
	}
}

add_action( 'plugins_loaded', 'init_umw_active_alerts' );
function init_umw_active_alerts() {
	$umwaa = new umw_active_alerts();
	return $umwaa;
}
?>