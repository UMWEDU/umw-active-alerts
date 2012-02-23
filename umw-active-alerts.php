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
		var $version = '0.2a';
		
		/**
		 * Build the umw_active_alerts object
		 * @uses umw_active_alerts::set_values()
		 * @uses add_action() to set up the AJAX insertion of active/emergency alerts throughout the install
		 & @uses add_action() to insert the appropriate styles/JavaScript for alerts
		 * @uses add_action() to set up a function to clear out cached alerts whenever a post is saved
		 * @uses add_action() to register the advisory post type on all sites except the advisories site
		 * @uses add_action() to hook the 'genesis_before_content' and 'wptouch_body_top' actions to insert 
		 * 		active local alerts above the content of a page
		 */
		function __construct() {
			$this->set_values();
			add_action( 'wp_ajax_nopriv_check_active_alert', array( $this, 'insert_active_alert' ) );
			add_action( 'wp_ajax_check_active_alert', array( $this, 'insert_active_alert' ) );
			if( !is_admin() ) {
				add_action( 'wp_print_styles', array( $this, 'print_styles' ) );
				add_action( 'wp_print_scripts', array( $this, 'localize_js' ) );
			}
			if ( is_admin() )
				$this->check_categories();
				
			add_action( 'save_post', array( $this, 'clear_active_alert' ), 99, 2 );
			add_action( 'trash_post', array( $this, 'clear_active_alert' ), 99, 2 );
			
			add_action( 'init', array( $this, 'register_post_type' ) );
			
			if ( $this->ad_id != $GLOBALS['blog_id'] ) {
				add_action( 'genesis_before_content', array( $this, 'insert_local_alert' ) );
				add_action( 'wptouch_body_top', array( $this, 'insert_local_alert' ) );
			}
			
			add_action( 'add_meta_boxes', array( $this, 'add_expires_meta_box' ) );
			wp_register_script( 'jquery-ui-timepicker-addon', plugins_url( '/js/jquery-ui-timepicker-addon.js', __FILE__ ), array( 'jquery-ui-datepicker', 'jquery-ui-slider' ), '0.9.9', true );
			wp_register_script( 'umw-active-alerts-admin', plugins_url( '/js/umw-active-alerts.admin.js', __FILE__ ), array( 'jquery-ui-timepicker-addon' ), '0.1.6', true );
			wp_register_style( 'wp-jquery-ui-datepicker', plugins_url( '/css/smoothness/jquery-ui-1.8.17.custom.css', __FILE__ ), array(), '0.1', 'screen' );
			wp_register_style( 'jquery-ui-timepicker', plugins_url( '/css/jquery-ui-timepicker-addon.css', __FILE__ ), array( 'wp-jquery-ui-datepicker' ), '0.1', 'screen' );
			/*if ( ! class_exists( 'active_alert_widget' ) )
				require_once( 'active-alert-widget.php' );
			
			add_shortcode( 'alert', array( $this, 'shortcode' ) );
			add_action( 'widgets_init', array( $this, 'init_widget' ) );*/
		}
		
		/**
		 * Set the object's property values
		 * @uses umw_active_alerts::get_value() to retrieve the value for each property
		 * @uses umw_active_alerts::$ad_id as the ID of the advisories site
		 * @uses umw_active_alerts::$ad_cat as the slug of the active alerts category
		 * @uses umw_active_alerts::$em_cat as the slug of the emergency alerts category
		 */
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
		
		/**
		 * Ensure the appropriate categories exist on the advisories site
		 * @uses umw_active_alerts::$ad_id to determine whether or not this is the advisories site
		 * @uses get_term_by() to see if the categories exist
		 * @uses wp_insert_term() to insert the categories if they don't exist
		 * @uses wp_update_term() to update the name/description of the categories if they aren't current
		 *
		 * @todo integrate this better with the pre-existing settings and vars
		 */
		function check_categories() {
			if ( version_compare( $this->get_value( '_umwaa_updated_categories' ), $this->version, '>=' ) )
				return;
				
			if ( empty( $this->ad_id ) )
				return;
			if ( $this->ad_id != $GLOBALS['blog_id'] )
				return;
			
			$ad_cat = (array) get_term_by( 'slug', 'current', 'category' );
			$em_cat = (array) get_term_by( 'slug', 'emergency', 'category' );
			$local_cat = get_term_by( 'slug', 'local-alerts', 'category' );
			if ( empty( $ad_cat ) ) {
				$ad_cat = wp_insert_term( __( 'Current University-wide Non-Emergency Alerts' ), 'category', array( 
					'description' => __( 'Current university-wide alerts that are not emergency notifications.' ), 
					'slug'        => 'current', 
				) );
				$this->ad_cat = 'current';
				
				if ( function_exists( 'update_mnetwork_option' ) )
					update_mnetwork_option( 'umw_advisories_active_cat', 'current' );
				else
					update_site_option( 'umw_advisories_active_cat', 'current' );
			} else if ( __( 'Current University-wide Non-Emergency Alerts' ) !== $ad_cat['name'] ) {
				$ad_cat['name'] = __( 'Current University-wide Non-Emergency Alerts' );
				$ad_cat['description'] = __( 'Current university-wide alerts that are not emergency notifications.' );
				wp_update_term( $ad_cat['term_id'], 'category', (array) $ad_cat );
			}
			if ( empty( $em_cat ) ) {
				$em_cat = wp_insert_term( __( 'Current University-wide Emergency Notifications' ), 'category', array(
					'description' => __( 'Emergency notifications that are broadcast to the entire university website' ),
					'slug'        => 'emergency',
				) );
				$this->em_cat = 'emergency';
				
				if ( function_exists( 'update_mnetwork_option' ) )
					update_mnetwork_option( 'umw_advisories_emergency_cat', 'emergency' );
				else
					update_site_option( 'umw_advisories_emergency_cat', 'emergency' );
			} else if ( __( 'Current University-wide Emergency Notifications' ) !== $em_cat['name'] ) {
				$em_cat['name'] = __( 'Current University-wide Emergency Notifications' );
				$em_cat['description'] = __( 'Emergency notifications that are broadcast to the entire university website' );
				wp_update_term( $em_cat['term_id'], 'category', (array) $em_cat );
			}
			if ( empty( $local_cat ) ) {
				$local_cat = wp_insert_term( __( 'Department, Division and Program Alerts' ), 'category', array( 
					'description' => __( 'Advisories and announcements related to specific departments, divisions and programs within the University.' ), 
					'slug'        => 'local-alerts',
				) );
			}
			
			if ( function_exists( 'update_mnetwork_option' ) )
				update_mnetwork_option( '_umwaa_updated_categories', $this->version );
			else
				update_site_option( '_umwaa_updated_categories', $this->version );
		}
		
		/**
		 * Retrieve the value of an option in this plugin
		 * @param string $key the key of the option to retrieve
		 * @uses get_mnetwork_option() if it exists
		 * @uses get_site_option() if get_mnetwork_option() doesn't exist
		 */
		function get_value( $key = '' ) {
			return stripslashes( htmlspecialchars( function_exists( 'get_mnetwork_option' ) ? get_mnetwork_option( $key ) : get_site_option( $key ) ) );
		}
		
		/**
		 * Insert any active emergency/non-emergency university-wide alerts into the page
		 * Echos JSON content that is retrieved and interpreted through the AJAX request
		 * @uses umw_active_alerts::active_alert() to retrieve the current non-emergency data
		 * @uses umw_active_alerts::active_emergency() to retrieve the current emergency data
		 */
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
		
		/**
		 * Enqueue the style sheet for the front end
		 * @uses wp_enqueue_style() to register/enqueue the stylesheet
		 */
		function print_styles() {
			wp_enqueue_style( 'umw-active-alerts', plugins_url( '/css/umw-active-alerts.css', __FILE__ ), array(), '0.1.45a', 'all' );
		}
		
		/**
		 * Enqueue the script that handles the AJAX calls on the front end
		 * @uses wp_enqueue_script() to register/enqueue the script file
		 * @uses wp_localize_script() to set the ajaxurl parameter in script
		 */
		function localize_js() {
			wp_enqueue_script( 'umw-active-alerts', plugins_url( '/js/umw-active-alerts.js', __FILE__ ), array( 'jquery' ), '0.1.30a', true );
			wp_localize_script( 'umw-active-alerts', 'umwActAlerts', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		}
		
		/**
		 * Check for active university-wide non-emergency advisories
		 * @return string|bool the HTML for the alert or false if the alert is empty
		 *
		 * @uses get_mnetwork_option() to retrieve cache data
		 * @uses get_site_option() if get_mnetwork_option() doesn't exist
		 * @uses $GLOBALS['wpdb']
		 * @uses umw_active_alerts::$ad_id to determine whether this is the advisories site or not
		 * @uses WPDB->set_blog_id() to switch to the advisories site and back
		 * @uses umw_active_alerts::$ad_cat
		 * @uses get_posts() to retrieve the current alert post
		 * @uses update_mnetwork_option() to update the cache data
		 * @uses update_site_option() if update_mnetwork_option() doesn't exist
		 * @uses get_blog_details() to retrieve information about the advisories site
		 * @uses strip_shortcodes() to remove any shortcodes from the alert excerpt
		 * @uses get_post_time() to retrieve the date and time the advisory was published
		 */
		function active_alert() {
			if( !function_exists( 'get_mnetwork_option' ) && WP_DEBUG ) {
				error_log( '[Active Alerts Debug]: The get_mnetwork_option function is not yet defined' );
			}
			if( function_exists( 'get_mnetwork_option' ) ) {
				$alert = get_mnetwork_option( 'umw_active_alert', false );
			} else {
				$alert = get_site_option( 'umw_active_alert', false );
			}
			if ( is_array( $alert ) && array_key_exists( 'content', $alert ) ) {
				return $alert['content'];
			}
			
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
			if ( WP_DEBUG )
				error_log( '[Active Alert Debug]: Non-Emergency Post args: ' . print_r( $args, true ) );
				
			$alerts = get_posts( $args );
			
			if( isset( $org_blog ) )
				$wpdb->set_blog_id( $org_blog );
			
			if( empty( $alerts ) ) {
				if( function_exists( 'update_mnetwork_option' ) )
					update_mnetwork_option( 'umw_active_alert', array( 'content' => false, 'ID' => 0 ) );
				else
					update_site_option( 'umw_active_alert', array( 'content' => false, 'ID' => 0 ) );
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
			else
				update_site_option( 'umw_active_alert', array( 'content' => $a, 'ID' => $alert->ID ) );
			
			return $a;
		}
		
		/**
		 * Check for active university-wide emergency advisories
		 * @return string|bool the HTML for the alert or false if the alert is empty
		 *
		 * @uses get_mnetwork_option() to retrieve cache data
		 * @uses get_site_option() if get_mnetwork_option() doesn't exist
		 * @uses $GLOBALS['wpdb']
		 * @uses umw_active_alerts::$ad_id to determine whether this is the advisories site or not
		 * @uses WPDB->set_blog_id() to switch to the advisories site and back
		 * @uses umw_active_alerts::$em_cat
		 * @uses get_posts() to retrieve the current emergency alert post
		 * @uses update_mnetwork_option() to update the cache data
		 * @uses update_site_option() if update_mnetwork_option() doesn't exist
		 * @uses get_blog_details() to retrieve information about the advisories site
		 * @uses strip_shortcodes() to remove any shortcodes from the alert excerpt
		 * @uses get_post_time() to retrieve the date and time the advisory was published
		 */
		function active_emergency() {
			if( !function_exists( 'get_mnetwork_option' ) && WP_DEBUG ) {
				error_log( '[Active Alerts Debug]: The get_mnetwork_option function is not yet defined' );
			}
			$alert = false;
			if( function_exists( 'get_mnetwork_option' ) ) {
				$alert = get_mnetwork_option( 'umw_active_emergency', false );
			} else {
				$alert = get_site_option( 'umw_active_emergency', false );
			}
			if ( is_array( $alert ) && array_key_exists( 'content', $alert ) ) {
				return $alert['content'];
			}
			
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
			if ( WP_DEBUG )
				error_log( '[Active Alert Debug]: Emergency Post args: ' . print_r( $args, true ) );
				
			$alerts = get_posts( $args );
			
			if( isset( $org_blog ) )
				$wpdb->set_blog_id( $org_blog );
			
			if( empty( $alerts ) ) {
				if( function_exists( 'update_mnetwork_option' ) )
					update_mnetwork_option( 'umw_active_emergency', array( 'content' => false, 'ID' => 0 ) );
				else
					update_site_option( 'umw_active_emergency', array( 'content' => false, 'ID' => 0 ) );
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
			else
				update_site_option( 'umw_active_emergency', array( 'content' => $a, 'ID' => $alert->ID ) );
			
			return $a;
		}
		
		/**
		 * Delete cache data for existing emergency/non-emergency university-wide alerts
		 * @param int $post_ID the ID of the post being modified
		 * @param stdClass $post a WordPress post object representing the post being modified
		 * @return mixed the $post_ID if no action is taken, otherwise void
		 *
		 * @uses delete_mnetwork_option() if it exists to remove the cache data
		 * @uses delete_site_option() if delete_mnetwork_option() doesn't exist
		 */
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
			
			if ( function_exists( 'delete_mnetwork_option' ) ) {
				delete_mnetwork_option( 'umw_active_alert' );
				delete_mnetwork_option( 'umw_active_emergency' );
				delete_mnetwork_option( 'current_local_alerts' );
			} else {
				delete_site_option( 'umw_active_alert' );
				delete_site_option( 'umw_active_emergency' );
				delete_site_option( 'current_local_alerts' );
			}
		}
		
		/**
		 * Register an "Alerts" post type on sites outside of the main advisories site
		 * @uses register_post_type() to register the advisory post type
		 * @uses register_taxonomy() to register the alert-type taxonomy for the advisory post type
		 * @uses wp_insert_term() to insert the two possible values of the alert-type taxonomy
		 * @uses add_action() to hook into the save_post action to syndicate the post and apply appropriate taxonomy term
		 * @uses add_action() to hook into the save_post action to set the appropriate expiration data
		 * @uses add_action() to hook into the manage_edit-{post_type}_columns and manage_{post_type}_posts_custom_column 
		 * 		actions to add the expiration data to the posts list table
		 */
		function register_post_type() {
			if ( $this->ad_id == $GLOBALS['blog_id'] )
				return;
			
			$labels = array(
				'name' => _x( 'Advisories', 'post type general name' ),
				'singular_name' => _x( 'Advisory', 'post type singular name' ),
				'add_new' => _x( 'Add New', 'advisory' ),
				'add_new_item' => __( 'Add New Advisory' ),
				'edit_item' => __( 'Edit Advisory' ),
				'new_item' => __( 'New Advisory' ),
				'all_items' => __( 'All Advisories' ),
				'view_item' => __( 'View Advisory' ),
				'search_items' => __( 'Search Advisories' ),
				'not_found' =>  __( 'No advisories found' ),
				'not_found_in_trash' => __( 'No advisories found in Trash' ), 
				'parent_item_colon' => '',
				'menu_name' => 'Advisories'
			);
			$args = array(
				'labels' => $labels,
				'public' => true,
				'publicly_queryable' => true,
				'show_ui' => true, 
				'show_in_menu' => true, 
				'query_var' => true,
				'rewrite' => true,
				'capability_type' => 'page', 
				'has_archive' => true, 
				'hierarchical' => false, 
				'menu_position' => null, 
				'supports' => array( 'title', 'editor', 'author', 'thumbnail' ), 
			);
			register_post_type( 'advisory', $args );
			
			$labels = array(
				'name'              => _x( 'Alert Types', 'taxonomy general name' ),
				'singular_name'     => _x( 'Alert Type', 'taxonomy singular name' ),
				'search_items'      =>  __( 'Search Alert Types' ),
				'all_items'         => __( 'All Alert Types' ),
				'parent_item'       => __( 'Parent Alert Type' ),
				'parent_item_colon' => __( 'Parent Alert Type:' ),
				'edit_item'         => __( 'Edit Alert Type' ), 
				'update_item'       => __( 'Update Alert Type' ),
				'add_new_item'      => __( 'Add New Alert Type' ),
				'new_item_name'     => __( 'New Alert Type Name' ),
				'menu_name'         => __( 'Alert Type' ),
			);
			$args = array(
				'hierarchical' => true, 
				'labels'       => $labels, 
				'public'       => false, 
				'show_ui'      => false, 
				'query_var'    => true, 
				'rewrite'      => false, 
			);
			register_taxonomy( 'alert-type', array( 'advisory' ), $args );
			
			if ( version_compare( get_option( '_umwaa_advisory_tax_set', false ), $this->version, '<' ) ) {
				wp_insert_term( 'Active', 'alert-type', array( 'description' => 'Active advisories that are displayed throughout this section of the website.', 'slug' => 'active' ) );
				wp_insert_term( 'Previous', 'alert-type', array( 'description' => 'Advisories for this section of the website that are no longer active.', 'slug' => 'previous' ) );
				update_option( '_umwaa_advisory_tax_set', $this->version );
			}
			
			add_action( 'save_post', array( $this, 'syndicate_post' ), 99999, 2 );
			add_action( 'trash_post', array( $this, 'syndicate_post' ), 99999, 2 );
			
			add_action( 'save_post', array( $this, 'set_expires_time' ), 99, 2 );
			
			add_action( 'manage_edit-advisory_columns', array( $this, 'add_advisory_table_columns' ) );
			add_action( 'manage_advisory_posts_custom_column', array( $this, 'output_custom_table_columns' ), 10, 2 );
			/*add_action( 'quick_edit_custom_box', array( $this, 'quick_edit_expires_box' ), 10, 2 );
			add_action( 'bulk_edit_custom_box', array( $this, 'bulk_edit_expires_box' ), 10, 2 );*/
			/*add_action( 'edit_post', array( $this, 'syndicate_post' ), 99999, 2 );
			add_action( 'edit_post', array( $this, 'set_expires_time' ), 99, 2 );*/
			/*add_action( 'wp_get_object_terms', array( $this, 'check_expiration_terms' ), 99, 4 );*/
		}
		
		/**
		 * Copy any "alert" posts to the advisories site
		 * @param int $post_ID the ID of the post being syndicated
		 * @param stdClass $post the WordPress post object for the post being syndicated
		 * @return int the ID of the post
		 *
		 * @uses get_post() to retrieve a post object if none were sent
		 * @uses umw_active_alerts::set_object_terms() to set the appropriate expiration data
		 * @uses umw_active_alerts::add_copy() to syndicate the post if it is published
		 * @uses umw_active_alerts::remove_copy() to delete the syndicated copy if it's not published
		 */
		function syndicate_post( $post_ID, $post=NULL ) {
			/**
			 * Make sure we're supposed to do this
			 */
			if ( $this->ad_id == $GLOBALS['blog_id'] )
				return $post_ID;
			if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return $post_ID;
			if( 'auto-draft' == $post->post_status || 'inherit' == $post->post_status )
				return $post_ID;
			
			/**
			 * Make sure the post var is set
			 */
			if( empty( $post ) )
				$post = get_post( $post_ID );
			
			if( is_object( $post ) && 'advisory' != $post->post_type )
				return $post_ID;
			
			if ( 'publish' == $post->post_status ) {
				$this->set_object_terms( $post );
			}
			
			/**
			 * Set up an identifier that will help us find copies of this post on the advisories site
			 */
			$guid = 'advisory.' . $GLOBALS['blog_id'] . '.' . $post->ID;
			
			if( 'trash' == $post->post_status ) {
				return $this->remove_copy( $post_ID, $post, $guid );
			}
			
			$pid = $this->add_copy( $post_ID, $post, $guid );
		}
		
		/**
		 * Delete an alert post on the Advisories site if it's removed from the originating site
		 * @param int $post_ID the ID of the local alert being unpublished
		 * @param stdClass $post the WordPress post object of the alert being unpublished
		 * @param string $guid the GUID to search
		 * @return int the ID of the post being modified
		 *
		 * @uses get_post() to retrieve a post object if none were sent
		 * @uses switch_to_blog() to switch to the advisories site
		 * @uses restore_current_blog() to switch back
		 * @uses $GLOBALS['wpdb']
		 * @uses WPDB::get_var() to retrieve the ID of the existing post on the advisories site
		 * @uses wp_delete_post() to remove the copy of the post
		 */
		function remove_copy( $post_ID, $post=null, $guid=null ) {
			if ( is_null( $post ) )
				$post = get_post( $post_ID );
			if ( is_null( $guid ) )
				$guid = 'advisory.' . $GLOBALS['blog_id'] . '.' . $post_ID;
			
			$post->guid = $guid;
			switch_to_blog( $this->ad_id );
			global $wpdb;
			$existing = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE guid=%s", esc_url( $post->guid ) ) );
			if ( $existing )
				wp_delete_post( $existing, true );
				
			restore_current_blog();
			
			return $post_ID;
		}
		
		/**
		 * Insert a post on the Advisories site as a copy of the alert post on the originating site
		 * @param int $post_ID the ID of the post being modified
		 * @param stdClass $post the WordPress post object being modified
		 * @param string $guid the GUID to use for this post
		 *
		 * @uses get_post() to retrieve a post object if none were sent
		 * @uses $GLOBALS['blog_id']
		 * @uses esc_url() to escape the guid value (since it will be escaped when sent to the database)
		 * @uses $GLOBALS['wpdb']
		 * @uses WPDB->blog_id
		 * @uses switch_to_blog() to switch to the advisories site
		 * @uses restore_current_blog() to switch back
		 * @uses get_term_by() to retrieve the ID of the local-alerts category on the advisories site and the category for this site
		 * @uses wp_insert_category() to insert the local-alerts category if it doesn't already exist
		 * @uses wp_delete_post() to delete an existing post on the advisories site if the alert being modified is not published
		 * @uses delete_post_meta() to remove any global meta data associated with this post before modifying it
		 * @uses wp_insert_post() to insert a new post as a copy of the local alert that was published
		 * @uses add_post_meta() to insert any global meta data for the post
		 */
		function add_copy( $post_ID, $post=null, $guid=null ) {
			if ( $GLOBALS['blog_id'] == $this->ad_id )
				return $post_ID;
			
			if ( empty( $post ) )
				$post = get_post( $post_ID );
			
			if ( 'advisory' != $post->post_type )
				return;
			
			if ( empty( $guid ) )
				$guid = 'advisory.' . $GLOBALS['blog_id'] . '.' . $post_ID;
			
			$post->guid = esc_url( $guid );
			
			global $wpdb;
			
			$global_meta = array();
			$global_meta['blogid'] = $org_blog_id = $wpdb->blogid; // org_blog_id
			
			$cat = array( 'cat_name' => get_bloginfo( 'name' ), 'description' => 'Alerts and advisories related to ' . get_bloginfo( 'name' ) );
			
			switch_to_blog( $this->ad_id );
			$local_alerts_parent_cat = get_term_by( 'slug', 'local-alerts', 'category' );
			$cat['category_parent'] = $local_alerts_parent_cat->term_id;
			$post->post_type = 'post';
			$category = wp_insert_category( $cat, true );
			if ( is_wp_error( $category ) ) {
				if ( WP_DEBUG )
					error_log( '[Alerts Debug]: Attempted to assign a category that threw an error. ' . $category->get_error_message() );
				$category = get_term_by( 'name', $cat['cat_name'], 'category' );
				$category = $category->term_id;
			}
			
			ob_start();
			var_dump( $category );
			$tmpcat = ob_get_clean();
			
			error_log( '[Alerts Debug]: Attempting to insert a new advisory with a category with an ID of ' . $tmpcat );
			
			$post->post_category = array( intval( $category ) );
			$existing = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE guid=%s", $post->guid ) );
			if ( is_wp_error( $existing ) )
				$existing = false;
			if ( $existing && 'publish' != $post->post_status ) {
				wp_delete_post( $existing, true );
			} else {
				if ( $existing ) {
					$post->ID = $existing;
				
					foreach ( array_keys( $global_meta ) as $key )
						delete_post_meta( $existing->ID, $key );
				} else {
					unset( $post->ID );
				}
			}
			
			if ( 'publish' == $post->post_status ) {
				$post->ping_status = $post->comment_status = 'closed';
				
				$p = wp_insert_post( $post );
				foreach ( $global_meta as $key => $value )
					if ( $value )
						add_post_meta( $p, $key, $value );
			}
			
			restore_current_blog();
			
			return $post_ID;
		}
		
		/**
		 * Insert the HTML for an active local alert
		 * @return void
		 *
		 * @uses get_posts() to retrieve all local alerts
		 * @uses get_transient() to figure out whether or not the current alert is active
		 * @uses umw_active_alerts::set_object_terms() to update the taxonomy/active info
		 * @uses strip_shortcodes() to remove any shortcodes from the content of the alert
		 * @uses get_permalink() to retrieve the permalink to the active alert
		 * @uses apply_filters() to apply any necessary filters to the_title
		 * @uses get_post_time() to retrieve the date and time the alert was published
		 */
		function insert_local_alert() {
			$args = array(
				'post_type'  => 'advisory', 
				'numberpost' => -1, 
				'tax_query'  => array(
					array( 
						'taxonomy' => 'alert-type', 
						'field'    => 'slug', 
						'terms'    => 'active', 
					), 
				), 
			);
			$posts = get_posts( $args );
			if ( empty( $posts ) )
				return false;
			
			foreach ( $posts as $post ) {
				$is_active = get_transient( 'advisory-' . $post->ID . '-active' );
				/**
				 * If the transient doesn't exist, or is empty, we make sure the "active" alert-type term is not 
				 * 		applied to this post and we continue the loop with the next item
				 */
				if ( ! $is_active ) {
					$this->set_object_terms( $post, false );
					continue;
				}
				
				$alert_excerpt = empty( $post->post_excerpt ) ? $post->post_content : $post->post_excerpt;
				$alert_excerpt = strip_tags( strip_shortcodes( $alert_excerpt ) );
				if( str_word_count( $alert_excerpt ) > 16 ) {
					$alert_excerpt = explode( ' ', $alert_excerpt );
					$alert_excerpt = array_slice( $alert_excerpt, 0, 15 );
					$alert_excerpt = implode( ' ', $alert_excerpt ) . '&hellip;';
				}
				$alert_excerpt = strip_tags( strip_shortcodes( $alert_excerpt ) );
?>
			<div class="local-alert">
				<h1 class="alert-title"><a href="<?php echo get_permalink( $post->ID ) ?>"><?php echo apply_filters( 'the_title', $post->post_title ) ?></a></h1>
				<div class="alert-content">
					<a href="<?php echo get_permalink( $post->ID ) ?>"><?php echo $alert_excerpt ?></a>
				</div>
				<div class="alert-date"><a href="<?php echo get_permalink( $post->ID ) ?>">[<?php _e( 'Posted: ' ); echo get_post_time( get_option( 'date_format' ), false, $post ); ?> at <?php echo get_post_time( get_option( 'time_format' ), false, $post ) ?>]</a></div>
			</div>
<?php
				/**
				 * If we made it this far through the loop, we want to return so that we don't output 
				 * 		more than one advisory.
				 */
				return;
			}
		}
		
		/**
		 * Register the meta box that determines when the advisory expires
		 */
		function add_expires_meta_box() {
			$object_type = $GLOBALS['blog_id'] == $this->ad_id ? 'post' : 'advisory';
			add_meta_box(
				/* Unique ID for the meta box */
				'advisory_expires_meta_box', 
				/* Title to display at top of meta box */
				__( 'Advisory Expiration' ), 
				/* Callback to output meta box content */
				array( $this, 'expires_meta_box' ), 
				/* Post type */
				$object_type, 
				/* Meta box position */
				'side'
			);
		}
		
		/**
		 * Output the contents of the advisory expiration meta box
		 */
		function expires_meta_box( $post ) {
			wp_enqueue_style( 'jquery-ui-timepicker' );
			wp_enqueue_script( 'umw-active-alerts-admin' );
			$expires = get_post_meta( $post->ID, '_advisory_expiration', true );
			$is_active = get_transient( 'advisory-' . $post->ID . '-active' );
			wp_nonce_field( 'advisory-expiration-meta', '_aexp_nonce' );
			if ( empty( $expires ) ) {
				$expires = array(
					'is_active' => true, 
					'expires_time' => ( current_time('timestamp') + ( 24 * 60 * 60 ) ), 
				);
			} else if ( false === $is_active ) {
				$expires['is_active'] = false;
			}
?>
	<p><input type="checkbox" name="_advisory_expiration[is_active]" id="_advisory_is_active" value="1"<?php checked( $expires['is_active'] ) ?>/> 
    	<label for="_advisory_is_active"><?php _e( 'Is this advisory currently active?' ) ?></label></p>
    <p><label for="_advisory_expires_time"><?php _e( 'If so, when should it expire?' ) ?></label>
    	<input type="text" class="datetimepicker" name="_advisory_expiration[expires_time]" id="_advisory_expires_time" value="<?php echo date( "Y-m-d H:i", $expires['expires_time'] ) ?>"/></p>
<?php
		}
		
		/**
		 * Set the expiration information for an advisory
		 * @param int the ID of the post being saved
		 * @param stdClass $post a WordPress post object
		 */
		function set_expires_time( $post_ID, $post=null ) {
			if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return $post_ID;
			if ( ! is_object( $post ) )
				$post = get_post( $post_ID );
			if( 'auto-draft' == $post->post_status || 'inherit' == $post->post_status )
				return $post_ID;
			
			if ( ! wp_verify_nonce( $_POST['_aexp_nonce'], 'advisory-expiration-meta' ) )
				return $post_ID;
			if ( ( $GLOBALS['blog_id'] != $this->ad_id && 'advisory' !== $post->post_type ) || ( $GLOBALS['blog_id'] == $this->ad_id && 'post' !== $post->post_type ) )
				return $post_ID;
			
			$is_active = in_array( $_POST['_advisory_expiration']['is_active'], array( 1, '1', true ) );
			$expires_time = @strtotime( $_POST['_advisory_expiration']['expires_time'] );
			
			if ( $expires_time < current_time('timestamp') )
				$is_active = false;
			
			$expires = array( 'is_active' => $is_active, 'expires_time' => $expires_time );
			if ( $is_active && $expires_time ) {
				$trans_time = ( $expires_time - current_time('timestamp') );
				if ( $trans_time > 0 )
					set_transient( 'advisory-' . $post_ID . '-active', $post_ID, $trans_time );
				else
					delete_transient( 'advisory-' . $post_ID . '-active', $post_ID );
			}
			update_post_meta( $post_ID, '_advisory_expiration', $expires );
			
			$this->set_object_terms( $post );
			
			return $post_ID;
		}
		
		/**
		 * Determine whether a post is an active advisory or not, and set the "alert-type" term appropriately.
		 * @param stdClass $post a WordPress post object
		 * @param bool $force whether or not to force the term update (if false, the list of existing terms will 
		 * 		be checked, and, if the target term is already applied to the object, the function will exit.
		 * @return void
		 */
		function set_object_terms( $post, $force = true ) {
			if ( 'advisory' !== $post->post_type )
				return;
			
			$is_active = get_transient( 'advisory-' . $post->ID . '-active' );
			$active = get_term_by( 'slug', 'active', 'alert-type' );
			$prev = get_term_by( 'slug', 'previous', 'alert-type' );
			
			$target = $is_active ? $active->term_id : $prev->term_id;
			
			if ( ! $force ) {
				$terms = wp_get_object_terms( $post->ID, 'alert-type' );
				if ( is_array( $terms ) ) {
					foreach ( $terms as $term ) {
						if ( $term->term_id === $target )
							return;
					}
				}
			}
			
			error_log( '[Alerts Debug]: Preparing to set the alert-type taxonomy to ' . $target );
			$result = wp_set_object_terms( $post->ID, array( intval( $target ) ), 'alert-type', false );
			ob_start();
			var_dump( $result );
			$results = ob_get_clean();
			error_log( '[Alerts Debug]: Results of setting alert-type taxonomy: ' . $results );
		}
		
		/**
		 * Register the extra columns for the advisory post list
		 * @param array $columns the array of columns
		 */
		function add_advisory_table_columns( $columns ) {
			$title_arr = array_slice( $columns, 0, 2, true );
			return $title_arr + array( 
				'active' => __( 'Is Active' ), 
				'expires' => __( 'Expiration date' ) 
			) + $columns;
		}
		
		/**
		 * Echo the HTML for the custom columns in the advisory edit table
		 */
		function output_custom_table_columns( $column, $post_ID ) {
			switch( $column ) {
				case 'active' :
					wp_enqueue_style( 'jquery-ui-timepicker' );
					wp_enqueue_script( 'umw-active-alerts-admin' );
					$is_active = get_transient( 'advisory-' . $post_ID . '-active' );
					echo $is_active ? __( '<strong>Yes</strong>' ) : __( 'No' );
					break;
				case 'expires' :
					$expires = get_post_meta( $post_ID, '_advisory_expiration', true );
					echo date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $expires['expires_time'] );
					break;
			}
		}
		
		/**
		 * Echo the HTML to show in the Quick Edit area for advisories
		 */
		function quick_edit_expires_box( $column_name, $post_type ) {
			if ( 'advisory' !== $post_type )
				return;
			
			if ( ! in_array( $column_name, array( 'active', 'expires' ) ) )
				return;
			
			global $post;
			
			if ( 'active' === $column_name ) {
?>
<fieldset class="inline-edit-col-right"><div class="inline-edit-col">
<?php
			}
?>
<div class="inline-edit-group">
	<label class="inline-edit-<?php echo esc_attr( $column_name ) ?> alignleft">
    	<span class="title"><?php echo 'active' == $column_name ? __( 'Active?' ) : __( 'Expires:' ) ?></span>
<?php
			switch( $column_name ) {
				case 'active' :
					$is_active = false === get_transient( 'advisory-' . $post->ID . '-active' ) ? false : true;
?>
<input type="checkbox" name="_advisory_expiration[is_active]" id="_advisory_is_active" value="1"<?php checked( $is_active ) ?>/>
<?php
				break;
				case 'expires' : 
					$expires = get_post_meta( $post->ID, '_advisory_expiration', true );
?>
<input type="text" class="datetimepicker" name="_advisory_expiration[expires_time]" id="_advisory_expires_time_<?php echo $post->ID ?>" value="<?php echo date( "Y-m-d H:i", $expires['expires_time'] ) ?>"/>
<?php
				break;
			}
?>
    </label>
</div>
<?php
			if ( 'expires' === $column_name ) {
?>
	<!--<input type="hidden" name="is_quickedit" value="1"/>-->
</div></fieldset>
<?php
			}
		}
		
		/**
		 * Echo the HTML to show in the Bulk Edit area for advisories
		 */
		function bulk_edit_expires_box( $column_name, $post_type ) {
			if ( 'advisory' !== $post_type )
				return;
		}
		
		/**
		 * Implement a shortcode to display an active alert in the content of a post/page
		 * @deprecated
		 */
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
		
		/**
		 * Initiate the active alerts widget
		 * @deprecated
		 */
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