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
		var $em_cat = null;
		
		function __construct() {
			$this->set_values();
			add_action( 'wp_ajax_nopriv_check_active_alert', array( $this, 'insert_active_alert' ) );
			add_action( 'wp_ajax_check_active_alert', array( $this, 'insert_active_alert' ) );
			if( !is_admin() ) {
				add_action( 'wp_print_styles', array( $this, 'print_styles' ) );
				add_action( 'wp_print_scripts', array( $this, 'localize_js' ) );
			}
			$this->check_categories();
			add_action( 'save_post', array( $this, 'clear_active_alert' ), 99, 2 );
			add_action( 'trash_post', array( $this, 'clear_active_alert' ), 99, 2 );
			
			if ( ! class_exists( 'active_alert_widget' ) )
				require_once( 'active-alert-widget.php' );
			
			add_shortcode( 'alert', array( $this, 'shortcode' ) );
			add_action( 'widgets_init', array( $this, 'init_widget' ) );
		}
		
		function set_values() {
			$this->ad_id	= $this->get_value( 'umw_advisories_blog_id' );
			if( empty( $this->ad_id ) )
				$this->ad_id = 2281;
			
			$this->ad_cat	= $this->get_value( 'umw_advisories_active_cat' );
			if( empty( $this->ad_cat ) )
				$this->ad_cat = 'current';
			
			$this->em_cat = $this->get_value( 'umw_advisories_emergency_cat' );
			if ( empty( $this->em_cat ) )
				$this->em_cat = 'emergency';
		}
		
		function check_categories() {
			if ( empty( $this->ad_id ) )
				return;
			if ( $this->ad_id != $GLOBALS['blog_id'] )
				return;
			
			$ad_cat = get_term_by( 'slug', 'current', 'category' );
			$em_cat = get_term_by( 'slug', 'emergency', 'category' );
			if ( empty( $ad_cat ) ) {
				$ad_cat = wp_insert_term( __( 'Current University-wide Alerts' ), 'category', array( 
					'description' => __( 'Current university-wide alerts that are not emergency notifications.' ), 
					'slug'        => 'current', 
				) );
				$this->ad_cat = 'current';
				
				if ( function_exists( 'update_mnetwork_option' ) )
					update_mnetwork_option( 'umw_advisories_active_cat', 'current' );
				else
					update_site_option( 'umw_advisories_active_cat', 'current' );
			}
			if ( empty( $em_cat ) ) {
				$em_cat = wp_insert_term( __( 'Current Emergency Notifications' ), 'category', array(
					'description' => __( 'Current university-wide emergency notifications' ),
					'slug'        => 'emergency',
				) );
				$this->em_cat = 'emergency';
				
				if ( function_exists( 'update_mnetwork_option' ) )
					update_mnetwork_option( 'umw_advisories_emergency_cat', 'emergency' );
				else
					update_site_option( 'umw_advisories_emergency_cat', 'emergency' );
			}
		}
		
		/* Get value functon
		------------------------------------------------------------ */
		function get_value( $key = '' ) {
			return stripslashes( htmlspecialchars( function_exists( 'get_mnetwork_option' ) ? get_mnetwork_option( $key ) : get_site_option( $key ) ) );
		}
		
		function insert_active_alert() {
			header( "Content-Type: application/json" );
			$aa = $this->active_alert();
			$ae = $this->active_emergency();
			if( false === $aa && false === $ae )
				echo json_encode( array( 'alert' => array( 'html' => -1 ), 'emergency' => array( 'html' => -1 ) ) );
			
			$h = array( 'html' => $aa, 'ID' => $GLOBALS['post']->ID );
			$e = array( 'html' => $ae, 'ID' => $GLOBALS['post']->ID );
			echo json_encode( array( 'alert' => $h, 'emergency' => $e ) );
			
			exit;
		}
		
		function print_styles() {
			wp_enqueue_style( 'umw-active-alerts', plugins_url( '/css/umw-active-alerts.css', __FILE__ ), array(), '0.1.42a', 'all' );
		}
		
		function localize_js() {
			wp_enqueue_script( 'umw-active-alerts', plugins_url( '/js/umw-active-alerts.js', __FILE__ ), array( 'jquery' ), '0.1.30a', true );
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
			error_log( '[Active Alert Debug]: Non-Emergency Post args: ' . print_r( $args, true ) );
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
			$alert_excerpt = empty( $alert->post_excerpt ) ? $alert->post_content : $alert->post_excerpt;
			$alert_excerpt = strip_tags( strip_shortcodes( $alert_excerpt ) );
			if( str_word_count( $alert_excerpt ) > 16 ) {
				$alert_excerpt = explode( ' ', $alert_excerpt );
				$alert_excerpt = array_slice( $alert_excerpt, 0, 15 );
				$alert_excerpt = implode( ' ', $alert_excerpt ) . '&hellip;';
			}
			$alert_excerpt = strip_tags( strip_shortcodes( $alert_excerpt ) );
			$a = '
			<div class="active-alert">
				<h1 class="alert-title"><a href="' . trailingslashit( $bloginfo->siteurl ) . '?p=' . $alert->ID . '">' . apply_filters( 'the_title', $alert->post_title ) . '</a></h1>
				<div class="alert-content">
					<a href="' . trailingslashit( $bloginfo->siteurl ) . '?p=' . $alert->ID . '">' . $alert_excerpt . '</a>
				</div>
				<div class="alert-date"><a href="' . trailingslashit( $bloginfo->siteurl ) . '?p=' . $alert->ID . '">[' . __( 'Posted: ' ) . get_post_time( get_option( 'date_format' ), false, $alert ) . ' at ' . get_post_time( get_option( 'time_format' ), false, $alert ) . ']</a></div>
			</div>';
			if( function_exists( 'update_mnetwork_option' ) )
				update_mnetwork_option( 'umw_active_alert', array( 'content' => $a, 'ID' => $alert->ID ) );
			
			return $a;
		}
		
		/**
		 * Check for active advisories
		 */
		function active_emergency() {
			if( !function_exists( 'get_mnetwork_option' ) ) {
				error_log( '[Active Alerts Debug]: The get_mnetwork_option function is not yet defined' );
			}
			if( function_exists( 'get_mnetwork_option' ) && $alert = get_mnetwork_option( 'umw_active_emergency', false ) )
				if( is_array( $alert ) && array_key_exists( 'content', $alert ) )
					return $alert['content'];
			
			global $wpdb;
			
			if( $this->ad_id != $GLOBALS['blog_id'] )
				$org_blog = $wpdb->set_blog_id( $this->ad_id );
				
			$args = array(
				'post_type'		=> 'post', 
				'post_status'	=> 'publish', 
				'category_name'	=> $this->em_cat,
				'posts_per_page'=> 1,
				'orderby'		=> 'post_date',
				'order'			=> 'DESC',
			);
			error_log( '[Active Alert Debug]: Emergency Post args: ' . print_r( $args, true ) );
			$alerts = get_posts( $args );
			
			if( isset( $org_blog ) )
				$wpdb->set_blog_id( $org_blog );
			
			if( empty( $alerts ) ) {
				if( function_exists( 'update_mnetwork_option' ) )
					update_mnetwork_option( 'umw_active_emergency', array( 'content' => false, 'ID' => 0 ) );
				return false;
			}
			
			$alert = array_shift( $alerts );
			$bloginfo = get_blog_details( $this->ad_id );
			$alert_excerpt = empty( $alert->post_excerpt ) ? $alert->post_content : $alert->post_excerpt;
			$alert_excerpt = strip_tags( strip_shortcodes( $alert_excerpt ) );
			if( str_word_count( $alert_excerpt ) > 16 ) {
				$alert_excerpt = explode( ' ', $alert_excerpt );
				$alert_excerpt = array_slice( $alert_excerpt, 0, 15 );
				$alert_excerpt = implode( ' ', $alert_excerpt ) . '&hellip;';
			}
			$alert_excerpt = strip_tags( strip_shortcodes( $alert_excerpt ) );
			$a = '
			<div class="emergency-alert">
				<span class="alert-icon alert-icon-left"></span>
				<h1 class="alert-title"><a href="' . trailingslashit( $bloginfo->siteurl ) . '?p=' . $alert->ID . '">' . apply_filters( 'the_title', $alert->post_title ) . '</a></h1>
				<div class="alert-content">
					<a href="' . trailingslashit( $bloginfo->siteurl ) . '?p=' . $alert->ID . '">' . $alert_excerpt . '</a>
				</div>
				<div class="alert-date"><a href="' . trailingslashit( $bloginfo->siteurl ) . '?p=' . $alert->ID . '">[' . __( 'Posted: ' ) . get_post_time( get_option( 'date_format' ), false, $alert ) . ' at ' . get_post_time( get_option( 'time_format' ), false, $alert ) . ']</a></div>
				<span class="alert-icon alert-icon-right"></span>
			</div>';
			if( function_exists( 'update_mnetwork_option' ) )
				update_mnetwork_option( 'umw_active_emergency', array( 'content' => $a, 'ID' => $alert->ID ) );
			
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
			delete_mnetwork_option( 'umw_active_emergency' );
			delete_mnetwork_option( 'current_local_alerts' );
		}
		
		function shortcode( $atts ) {
			$instance = shortcode_atts( array( 
				'category' => 'uncategorized', 
			), $atts );
			
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
			$posts = get_posts( array( 'category_name' => $instance['category'], 'numberposts' => 1, 'post_type' => 'post', 'post_status' => 'publish', 'orderby' => 'post_date', 'order' => 'DESC' ) );
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
					' . __( 'Posted: ' ) . get_post_time( get_option( 'date_format' ), false, $post ) . ' at ' . get_post_time( get_option( 'time_format' ), false, $post ) . '
				</p>
			</div>';
			
			$alerts[$instance['category']] = $alert;
			update_mnetwork_option( 'current_local_alerts', $alerts );
			restore_current_blog();
			
			return $alert;
		}
		
		function init_widget() {
			register_widget( 'active_alert_widget' );
		}
	}
}

add_action( 'plugins_loaded', 'init_umw_active_alerts' );
function init_umw_active_alerts() {
	global $umwaa;
	$umwaa = new umw_active_alerts();
	return $umwaa;
}
?>