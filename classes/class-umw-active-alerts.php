<?php
/**
 * Define the UMW_Active_Alerts class
 * @package umw-active-alerts
 * @version 1.0
 */
if ( ! class_exists( 'UMW_Active_Alerts' ) ) {
	class UMW_Active_Alerts {
		public $version = '1.0.2';
		public $is_root = false;
		public $root_url = null;
		public $is_alerts = false;
		public $alerts_url = null;
		public $db_version = '20150724110000';
		private $oauth = array();
		private $headers = array();
		
		function __construct() {
			$this->umw_is_root();
			$this->umw_is_advisories();
			
			if ( isset( $_SERVER['PHP_AUTH_USER'] ) && ! defined( 'WPCAS_BYPASS' ) )
				define( 'WPCAS_BYPASS', true );
			
			add_action( 'genesis_header', array( $this, 'do_current_advisories' ), 9 );
			$this->api_uris = apply_filters( 'umw-alerts-api-uris', array(
				'publish' => $this->alerts_url . '/wp-json/wp/v2/advisories', 
				'update'  => $this->alerts_url . '/wp-json/wp/v2/advisories/%1$d', 
				'delete'  => $this->alerts_url . '/wp-json/wp/v2/advisories/%1$d', 
				'trash'   => $this->alerts_url . '/wp-json/wp/v2/advisories/%1$d', 
				'meta'    => $this->alerts_url . '/wp-json/wp/v2/posts/%1d/meta', 
			) );
			
			$this->_set_api_headers();
			
			add_action( 'plugins_loaded', array( $this, 'ajax_setup' ) );
		}
		
		function ajax_setup() {
			if ( ! is_admin() ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			}
			add_action( 'wp_ajax_check_global_advisories', array( $this, 'check_global_advisories' ) );
			add_action( 'wp_ajax_nopriv_check_global_advisories', array( $this, 'check_global_advisories' ) );
			add_action( 'wp_ajax_check_local_advisories', array( $this, 'check_local_advisories' ) );
			add_action( 'wp_ajax_nopriv_check_global_advisories', array( $this, 'check_local_advisories' ) );
		}
		
		function enqueue_scripts() {
			wp_register_script( 'umw-active-alerts', plugins_url( '/scripts/umw-active-alerts.js', dirname( __FILE__ ) ), array( 'jquery' ), $this->version, true );
			$this->do_global_advisories_script();
			wp_enqueue_script( 'umw-active-alerts' );
			if ( post_type_exists( 'advisory' ) )
				add_action( 'wp_print_footer_scripts', array( $this, 'do_local_advisories_script' ) );
		}
		
		function enqueue_styles() {
			wp_enqueue_style( 'umw-active-alerts', plugins_url( '/styles/umw-active-alerts.css', dirname( __FILE__ ) ), array(), $this->version, 'all' );
		}
		
		/**
		 * Check to see if this is the root site of the system
		 */
		function umw_is_root() {
			if ( defined( 'UMW_IS_ROOT' ) ) {
				if ( is_numeric( UMW_IS_ROOT ) && $GLOBALS['blog_id'] == UMW_IS_ROOT ) {
					$this->is_root = true;
					$this->root_url = get_bloginfo( 'url' );
				} else if ( is_numeric( UMW_IS_ROOT ) ) {
					$this->root_url = get_blog_option( UMW_IS_ROOT, 'home_url', null );
				} else {
					$this->root_url = esc_url( UMW_IS_ROOT );
				}
			}
		}
		
		/**
		 * Determine whether or not this is the main advisories site.
		 * If it is, set the appropriate var to true
		 * If it isn't, set the appropriate var to true, and set the URL
		 * 		of the main advisories site
		 */
		function umw_is_advisories() {
			if ( defined( 'UMW_ADVISORIES_SITE' ) && is_numeric( UMW_ADVISORIES_SITE ) ) {
				if ( UMW_ADVISORIES_SITE == $GLOBALS['blog_id'] ) {
					$this->is_alerts = true;
					$this->alerts_url = esc_url( get_bloginfo( 'url' ) );
					$this->setup_alerts_site();
				} else {
					$this->is_alerts = false;
					$this->alerts_url = esc_url( get_blog_option( UMW_ADVISORIES_SITE, 'home' ) );
					$this->add_syndication_actions();
				}
			} else {
				$this->is_alerts = false;
				$this->alerts_url = esc_url( UMW_ADVISORIES_SITE );
				$this->add_syndication_actions();
			}
		}
		
		/**
		 * Setup any actions/filters that need to be registered on the 
		 * 		main Advisories site
		 */
		function setup_alerts_site() {
			add_action( 'init', array( $this, '_add_extra_api_post_type_arguments' ), 12 );
			add_filter( 'rest_api_allowed_post_types', array( $this, 'whitelist_external_advisories' ) );
			add_filter( 'json_prepare_post', array( $this, 'add_advisory_metadata' ) );
			add_filter( 'rest_public_meta_keys', array( $this, 'whitelist_advisory_metadata' ) );
			add_filter( 'rest_api_allowed_public_metadata', array( $this, 'whitelist_advisory_metadata' ) );
			// We need to fix some oddities in the way the API behaves
			add_action( 'save_post_external-advisory', array( $this, 'fix_api_formatting' ), 10, 2 );
			add_action( 'wp_trash_post', array( $this, 'really_delete_syndicated_advisory' ) );
			add_action( 'init', array( $this, 'register_advisory_feed' ) );
			add_action( 'rest_api_init', array( $this, 'bypass_cas' ) );
		}
		
		function bypass_cas() {
			if ( ! defined( 'WPCAS_BYPASS' ) )
				define( 'WPCAS_BYPASS', true );
		}
		
		/**
		 * Adds any syndication actions that are necessary
		 */
		function add_syndication_actions() {
			add_action( 'save_post_advisory', array( $this, 'push_advisory' ), 10, 2 );
			add_action( 'wp_trash_post', array( $this, 'trash_advisory' ) );
			add_action( 'untrashed_post', array( $this, 'untrash_advisory' ) );
			add_action( 'delete_post', array( $this, 'delete_advisory' ) );
			
			$this->register_post_types();
		}
		
		/**
		 * Register the Advisory post type
		 */
		function register_post_types() {
			require_once( plugin_dir_path( __FILE__ ) . '/embedded-alerts/types.php' );
		}
		
		/**
		 * Set up the API request headers to be sent with any remote request
		 */
		private function _set_api_headers() {
			$this->headers = array(
				/*'context' => 'display', 
				'pretty'  => true, */
				'Authorization' => 'Basic ' . base64_encode( UMW_ALERTS_USER_NAME . ':' . UMW_ALERTS_USER_PWD ), 
				/*'Content-Type' => 'application/json'*/
			);
		}
		
		private function _get_api_headers() {
			return $this->headers;
		}
		
		/**
		 * Push a new external advisory from the source site to the 
		 * 		central Advisories site
		 */
		function push_advisory( $post_id, $p=null ) {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return;
			if ( wp_is_post_revision( $post_id ) )
				return;
			
			if ( empty( $p ) )
				$p = get_post( $post_id );
			
			if ( isset( $_REQUEST['post_ID'] ) && is_numeric( $_REQUEST['post_ID'] ) ) {
				$syndicated_id = get_post_meta( $_REQUEST['post_ID'], '_syndicated-alert-id', true );
			} else {
				$syndicated_id = get_post_meta( $p->ID, '_syndicated-alert-id', true );
			}
			
			if ( isset( $_REQUEST['author_override'] ) && is_numeric( $_REQUEST['author_override'] ) ) {
				$author = $_REQUEST['author_override'];
			} else if ( isset( $_REQUEST['post_author'] ) && is_numeric( $_REQUEST['post_author'] ) ) {
				$author = $_REQUEST['post_author'];
			} else {
				$author = $p->post_author;
			}
			$author = get_user_by( 'id', $author );
			$author = $author->display_name;
			
			$datefields = isset( $_REQUEST['wpcf']['_advisory_expires_time'] ) && is_array( $_REQUEST['wpcf']['_advisory_expires_time'] ) ? $_REQUEST['wpcf']['_advisory_expires_time'] : array();
			if ( ! function_exists( 'wpcf_fields_date_value_save_filter' ) && defined( 'WPCF_EMBEDDED_INC_ABSPATH' ) ) {
				@include_once( WPCF_EMBEDDED_INC_ABSPATH . '/fields/date/functions.php' );
			}
			if ( ! empty( $datefields ) && function_exists( 'wpcf_fields_date_value_save_filter' ) ) {
				$expires = wpcf_fields_date_value_save_filter( $datefields, null, null );
			} else {
				$expires = null;
			}
			
			$meta = array(
				(object)array(
					'key'   => 'wpcf-_advisory_expires_time', 
					'value' => $expires,
				), 
				(object)array(
					'key'   => 'wpcf-_advisory_permalink', 
					'value' => esc_url( get_permalink( $post_id ) ), 
				), 
				(object)array(
					'key'   => 'wpcf-_advisory_author', 
					'value' => $author, 
				), 
			);
			
			$body = array(
				'title'   => $p->post_title, 
				'content' => $p->post_content, 
				'status'  => $p->post_status, 
				'post_meta' => json_encode( $meta ), 
			);
				
			if ( empty( $syndicated_id ) ) {
				$url = sprintf( $this->api_uris['publish'], $this->jetpack_api_domain( $this->alerts_url ) );
				
				$result = $this->_push_advisory_new( $body, $url );
			} else {
				$url = sprintf( $this->api_uris['update'], intval( $syndicated_id ) );
				
				$result = $this->_push_advisory_edit( $syndicated_id, $body, $url );
			}
			
			if ( ! is_object( $result ) && ! is_array( $result ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[Alert API Debug]: Attempted to get the result ID, but result did not appear to be an object or an array' );
					error_log( '[Alert API Debug]: ' . print_r( $result, true ) );
					/*print( '<pre><code>' );
					var_dump( $result );
					print( '</code></pre>' );
					return wp_die( 'The result was not an array or an object' );*/
				}
				return false;
			}
			if ( is_object( $result ) && ! property_exists( $result, 'id' ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[Alert API Debug]: Attempted to get the result ID, but that property did not exist within the result object' );
					/*print( '<pre><code>' );
					var_dump( $result );
					print( '</code></pre>' );
					return wp_die( 'The result was an object but the id property did not exist' );*/
				}
				return false;
			} else if ( is_array( $result ) && ! array_key_exists( 'id', $result ) ) {
				$r = array_shift( $result );
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[Alert API Debug]: Successfully pushed the advisory with a result ID of ' . $r->id );
				}
				if ( is_object( $r ) ) {
					if ( property_exists( $r, 'code' ) && 'rest_post_invalid_id' == $r->code ) {
						$url = sprintf( $this->api_uris['publish'], $this->jetpack_api_domain( $this->alerts_url ) );
						$result = $this->_push_advisory_new( $body, $url );
						
						if ( ( is_array( $result ) && ! array_key_exists( 'id', $result ) ) || ( is_object( $result ) && ! property_exists( $result, 'id' ) ) ) {
							return false;
						}
					}
				}
			}
			
			if ( is_array( $result ) )
				$result_id = $result['id'];
			else
				$result_id = $result->id;
			
			$url = sprintf( $this->api_uris['meta'], $result_id, $result_id );
			
			$this->_push_advisory_meta( $url, $meta );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[Alert API Debug]: Just pushed the meta data for the post with a result ID of ' . $result_id );
			}
			
			update_post_meta( $post_id, '_syndicated-alert-id', $result_id );
		}
		
		/**
		 * Create a new external advisory based on the data from the advisory being created
		 */
		private function _push_advisory_new( $body, $url, $method='POST' ) {
			$args = array( 'headers' => $this->_get_api_headers(), 'body' => http_build_query( $body ) );
			$done = wp_safe_remote_post( $url, $args );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[Alert API Debug]: ' . print_r( $done, true ) );
				/*print( '<pre><code>' );
				var_dump( $done );
				print( '</code></pre>' );
				wp_die( 'Hold it right there' );*/
			}
			$result = @json_decode( wp_remote_retrieve_body( $done ) );
			
			return $result;
		}
		
		/**
		 * Update an existing external advisory based on the new data from the advisory being edited
		 */
		private function _push_advisory_edit( $syndicated_id, $body, $url, $method='PUT' ) {
			$body['ID'] = $syndicated_id;
			$args = array( 'method' => 'PUT', 'headers' => $this->_get_api_headers(), 'body' => http_build_query( $body ) );
			$done = wp_remote_request( $url, $args );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[Alert API Debug]: ' . print_r( $done, true ) );
				/*print( '<pre><code>' );
				var_dump( $done );
				print( '</code></pre>' );
				wp_die( 'Hold it right there' );*/
			}
			$result = @json_decode( wp_remote_retrieve_body( $done ) );
			
			return $result;
		}
		
		/**
		 * Add or update any post meta information needed for the syndicated advisory
		 */
		private function _push_advisory_meta( $url, $meta ) {
			$original = wp_remote_get( $url, array( 'headers' => $this->_get_api_headers(), 'body' => '' ) );
			$original = @json_decode( wp_remote_retrieve_body( $original ) );
			
			foreach ( $meta as $m ) {
				$u = $url;
				
				foreach ( $original as $o ) {
					if ( $o->key != $m->key )
						continue;
					
					if ( $o->key == $m->key ) {
						$u .= '/' . $o->id;
						$m->id = $o->id;
					}
				}
				
				$u = add_query_arg( array( 'key' => urlencode( $m->key ), 'value' => urlencode( $m->value ) ), $u );
				if ( property_exists( $m, 'id' ) ) {
					$u = add_query_arg( 'id', intval( $m->id ), $u );
					$method = 'PUT';
				} else {
					$method = 'POST';
				}
				
				$args = array( 'headers' => $this->_get_api_headers(), 'body' => '' );
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[API Alert Debug]: Attempted to modify meta for an advisory. The meta key is: ' . $m->key . ', the meta value is: ' . $m->value . ' and the URL for the request is: ' . $u );
				}
				if ( 'PUT' == $method ) {
					$args['method'] = 'PUT';
					$tmp = wp_remote_request( $u, $args );
				} else {
					$tmp = wp_safe_remote_post( $u, $args );
				}
			}
			
			return;
		}
		
		/**
		 * If a syndicated post is pulled into the Advisories site with the API, 
		 * 		we may need to fix some of the formatting
		 */
		function fix_api_formatting( $post_id=null, $p=null ) {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return;
			if ( wp_is_post_revision( $post_id ) )
				return;
			
			if ( empty( $p ) )
				$p = get_post( $post_id );
			
			remove_action( 'save_post_external-advisory', array( $this, 'fix_api_formatting' ), 10, 2 );
			
			wp_update_post( array( 'ID' => $p->ID, 'post_title' => stripslashes( $p->post_title ), 'post_content' => stripslashes( $p->post_content ) ) );
			
			add_action( 'save_post_external-advisory', array( $this, 'fix_api_formatting' ), 10, 2 );
		}
		
		/**
		 * Since there is currently no process to permanently delete a post with the API, 
		 * 		let's do it a different way
		 */
		function really_delete_syndicated_advisory( $post_id ) {
			if ( wp_is_post_revision( $post_id ) )
				$post_id = wp_is_post_revision( $post_id );
			
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[Alerts API Debug]: We should be permanently deleting the external advisory with an ID of ' . $post_id );
			}
			$p = get_post( $post_id );
			if ( 'external-advisory' != $p->post_type )
				return;
			
			remove_action( 'wp_trash_post', array( $this, 'really_delete_syndicated_advisory' ) );
			wp_delete_post( $post_id, true );
			add_action( 'wp_trash_post', array( $this, 'really_delete_syndicated_advisory' ) );
		}
		
		/**
		 * Authorize the forthcoming API request
		 */
		function jetpack_auth() {
			$auth = wp_safe_remote_post( esc_url( $this->api_uris['auth'] ), array( 'body' => http_build_query( $this->oauth ) ) );
			if ( is_wp_error( $auth ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				wp_die( 'Errored out' );
			}
			$secret = json_decode( wp_remote_retrieve_body( $auth ) );
			$access_key = $secret->access_token;
			
			return $access_key;
		}
		
		/**
		 * Trash an external advisory on the main Advisories site
		 */
		function trash_advisory( $post_id=null, $force=false ) {
			$force = true;
			
			if ( empty( $post_id ) ) {
				$post_id = isset( $_REQUEST['post'] ) && is_numeric( $_REQUEST['post'] ) ? $_REQUEST['post'] : null;
			}
			if ( empty( $post_id ) )
				return false;
			
			$syndicated_id = get_post_meta( $post_id, '_syndicated-alert-id', true );
			if( empty( $syndicated_id ) ) {
				error_log( '[Alert API Debug]: We could not find a syndicated ID for this advisory' );
				return false;
			}
			
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[Alert API Debug]: We are attempting to trash the syndicated post with an ID of ' . $syndicated_id );
			}
			$url = sprintf( $this->api_uris['trash'], intval( $syndicated_id ) );
			
			$body = array();
			$body['ID'] = $syndicated_id;
			if ( true === $force ) {
				add_query_arg( 'force', 'true', $url );
				$body['force'] = true;
				remove_action( 'save_post_advisory', array( $this, 'push_advisory' ), 10, 2 );
				delete_post_meta( $post_id, '_syndicated-alert-id', $syndicated_id );
				add_action( 'save_post_advisory', array( $this, 'push_advisory' ), 10, 2 );
			}
				
			$args = array( 'method' => 'DELETE', 'headers' => $this->_get_api_headers(), 'body' => http_build_query( $body ) );
			
			$done = wp_remote_request( $url, $args );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[Alert API Debug]: Trashed post with an ID of ' . $syndicated_id . ' by using URL ' . $url );
				error_log( '[Alert API Debug]: Trash result: ' . print_r( $done, true ) );
			}
			$result = @json_decode( wp_remote_retrieve_body( $done ) );
			
			return $result;
		}
		
		/**
		 * Re-syndicate a post after it has been untrashed
		 */
		function untrash_advisory( $post_id=null ) {
			if ( empty( $post_id ) ) {
				$post_id = isset( $_REQUEST['post'] ) && is_numeric( $_REQUEST['post'] ) ? $_REQUEST['post'] : null;
			}
			if ( empty( $post_id ) )
				return false;
			
			$p = get_post( $post_id );
			if ( 'advisory' != $p->post_type )
				return;
			
			if ( isset( $_REQUEST['author_override'] ) && is_numeric( $_REQUEST['author_override'] ) ) {
				$author = $_REQUEST['author_override'];
			} else if ( isset( $_REQUEST['post_author'] ) && is_numeric( $_REQUEST['post_author'] ) ) {
				$author = $_REQUEST['post_author'];
			} else {
				$author = $p->post_author;
			}
			$author = get_user_by( 'id', $author );
			$author = $author->display_name;
			
			$meta = array(
				(object)array(
					'key'   => 'wpcf-_advisory_expires_time', 
					'value' => get_post_meta( $post_id, 'wpcf-_advisory_expires_time', true ),
				), 
				(object)array(
					'key'   => 'wpcf-_advisory_permalink', 
					'value' => esc_url( get_permalink( $post_id ) ), 
				), 
				(object)array(
					'key'   => 'wpcf-_advisory_author', 
					'value' => $author, 
				), 
			);
			
			$body = array(
				'title'   => $p->post_title, 
				'content' => $p->post_content, 
				'status'  => $p->post_status, 
				'post_meta' => json_encode( $meta ), 
			);
				
			$syndicated_id = get_post_meta( $post_id, '_syndicated-alert-id', true );
			if( empty( $syndicated_id ) )
				return false;
			
			$url = sprintf( $this->api_uris['update'], intval( $syndicated_id ) );
			
			$result = $this->_push_advisory_new( $body, $url );
			
			if ( ! is_object( $result ) && ! is_array( $result ) ) {
				return false;
			}
			if ( is_object( $result ) && ! property_exists( $result, 'id' ) ) {
				return false;
			} else if ( is_array( $result ) && ! array_key_exists( 'id', $result ) ) {
				return false;
			}
			
			if ( is_array( $result ) )
				$result_id = $result['id'];
			else
				$result_id = $result->id;
			
			$url = sprintf( $this->api_uris['meta'], $result_id, $result_id );
			
			$this->_push_advisory_meta( $url, $meta );
			
			update_post_meta( $post_id, '_syndicated-alert-id', $result_id );
		}
		
		/**
		 * Permanently delete an advisory on the main Advisories site
		 */
		function delete_advisory( $post_id=null ) {
			if ( empty( $post_id ) ) {
				$post_id = isset( $_REQUEST['post'] ) && is_numeric( $_REQUEST['post'] ) ? $_REQUEST['post'] : null;
			}
			if ( empty( $post_id ) )
				return false;
			
			return $this->trash_advisory( $post_id, true );
		}
		
		function _add_extra_api_post_type_arguments() {
			global $wp_post_types;
			if ( ! array_key_exists( 'external-advisory', $wp_post_types ) )
				return;
			
			$wp_post_types['external-advisory']->show_in_rest = true;
			$wp_post_types['external-advisory']->rest_base = 'advisories';
			$wp_post_types['external-advisory']->rest_controller_class = 'WP_REST_Posts_Controller';
		}
		
		/**
		 * Make sure the JetPack JSON REST API allows us to modify
		 * 		External Advisories
		 */
		function whitelist_external_advisories( $types=array() ) {
			$types[] = 'external-advisory';
			return $types;
		}
		
		function add_advisory_metadata( $data, $post, $context ) {
			if ( $context !== 'view' || is_wp_error( $data ) ) {
				return $data;
			}
			
			$meta = array();
			
			$expires = get_post_meta( $post['ID'], 'wpcf-_advisory_expires_time', true );
			if ( ! empty( $expires ) && is_numeric( $expires ) ) {
				$meta['wpcf-_advisory_expires'] = $expires;
			}
			$permalink = get_permalink( $post['ID'] );
			if ( esc_url( $permalink ) ) {
				$meta['wpcf-_advisory_permalink'] = esc_url( $permalink );
			}
			
			$author = get_user_by( 'id', $post['post_author'] );
			$author = $author->display_name;
			
			if ( ! empty( $author ) ) {
				$meta['wpcf-_advisory_author'] = $author;
			}
			
			if ( array_key_exists( 'metadata', $data ) && is_array( $data['metadata'] ) ) {
				$data['metadata'] = array_merge( $data['metadata'], $meta );
			} else {
				$data['metadata'] = $meta;
			}
			
			if ( array_key_exists( 'post_meta', $data ) && is_array( $data['post_meta'] ) ) {
				$data['post_meta'] = array_merge( $data['post_meta'], $meta );
			} else {
				$data['post_meta'] = $meta;
			}
			
			$data['custom_meta'] = $meta;
			
			return $data;
		}
		
		function whitelist_advisory_metadata( $keys=array() ) {
			$keys[] = 'wpcf-_advisory_expires_time';
			$keys[] = 'wpcf-_advisory_permalink';
			$keys[] = 'wpcf-_advisory_author';
			
			return $keys;
		}
		
		function do_current_advisories() {
			return;
			
			$tmp = @render_view( array( 'title' => 'Current Advisories' ) );
			if ( ! empty( $tmp ) )
				echo $tmp;
		}
		
		function jetpack_api_domain( $url ) {
			return urlencode( str_ireplace( array( 'https://', 'http://' ), array( '', '' ), untrailingslashit( $url ) ) );
		}
		
		/**
		 * Register a JSON feed for the global advisories
		 */
		function register_advisory_feed() {
			add_feed( 'global-advisories.json', array( $this, 'do_advisory_feed' ) );
		}
		
		/**
		 * Output a JSON feed with the current, active global advisories
		 */
		function do_advisory_feed() {
			$ad = get_posts( array(
				'post_type'   => 'advisory', 
				'post_status' => 'publish', 
				'orderby'     => 'date', 
				'order'       => 'desc', 
				'posts_per_page' => 1, 
				'meta_query'  => array(
					array( 
						'key'   => 'wpcf-_advisory_expires_time', 
						'value' => current_time( 'timestamp' ), 
						'compare' => '>', 
						'type'  => 'NUMERIC'
					), 
				), 
			) );
			
			$em = get_posts( array( 
				'post_type'   => 'alert', 
				'post_status' => 'publish', 
				'orderby'     => 'date', 
				'order'       => 'desc', 
				'posts_per_page' => 1, 
				'meta_query'  => array(
					array( 
						'key'   => 'wpcf-_advisory_expires_time', 
						'value' => current_time( 'timestamp' ), 
						'compare' => '>', 
						'type'  => 'NUMERIC'
					), 
				), 
			) );
			
			$alerts = array( 'time' => current_time( 'timestamp' ) );
			if ( ! empty( $ad ) ) {
				$p = array_shift( $ad );
				$author = get_user_by( 'id', $p->post_author );
				$author = $author->display_name;
				$url = get_post_meta( $p->ID, 'wpcf-_advisory_permalink', true );
				if ( empty( $url ) || ! esc_url( $url ) )
					$url = get_permalink( $p->ID );
				$alerts['advisory'] = array( 
					'title'   => apply_filters( 'the_title', $p->post_title ), 
					'content' => apply_filters( 'the_content', $p->post_content ), 
					'excerpt' => apply_filters( 'the_excerpt', $p->post_excerpt ), 
					'author'  => $author, 
					'date'    => get_the_date( '', $p->ID ), 
					'url'     => esc_url( $url ), 
				);
			}
			if ( ! empty( $em ) ) {
				$p = array_shift( $em );
				$author = get_user_by( 'id', $p->post_author );
				$author = $author->display_name;
				$url = get_post_meta( $p->ID, 'wpcf-_advisory_permalink', true );
				if ( empty( $url ) || ! esc_url( $url ) )
					$url = get_permalink( $p->ID );
				$alerts['alert'] = array(
					'title'   => apply_filters( 'the_title', $p->post_title ), 
					'content' => apply_filters( 'the_content', $p->post_content ), 
					'excerpt' => apply_filters( 'the_excerpt', $p->post_excerpt ), 
					'author'  => $author, 
					'date'    => get_the_date( '', $p->ID ), 
					'url'     => esc_url( $url ), 
				);
			}
			
			echo json_encode( $alerts );
			die();
		}
		
		/**
		 * Add global advisories to the page if there are active ones
		 */
		function check_global_advisories() {
			if ( ! check_ajax_referer( 'umw-active-alerts-ajax', 'umwalerts_nonce' ) && ! current_user_can( 'delete_users' ) )
				die( 'No nonce' );
			
			$feed = esc_url( trailingslashit( $this->alerts_url ) . 'feed/global-advisories.json' );
			
			$request = wp_remote_get( $feed );
			if ( ! is_wp_error( $request ) )
				$response = @json_decode( wp_remote_retrieve_body( $request ) );
			
			/*if ( ! isset( $_REQUEST['is_root'] ) || 1 != intval( $_REQUEST['is_root'] ) ) {
				if ( is_array( $response ) )
					unset( $response['advisory'] );
				else if ( is_object( $response ) )
					unset( $response->advisory );
			}*/
			
			echo json_encode( $response );
			
			wp_die();
		}
		
		/**
		 * Check for an active local advisory
		 */
		function check_local_advisories() {
			if ( ! check_ajax_referer( 'umw-active-alerts-ajax', 'umwalerts_nonce' ) && ! current_user_can( 'delete_users' ) )
				die( 'No nonce' );
			
			$ad = get_posts( array( 
				'post_type'   => 'advisory', 
				'post_status' => 'publish', 
				'orderby'     => 'date', 
				'order'       => 'desc', 
				'posts_per_page' => 1, 
				'meta_query'  => array(
					array( 
						'key'   => 'wpcf-_advisory_expires_time', 
						'value' => current_time( 'timestamp' ), 
						'compare' => '>', 
						'type'  => 'NUMERIC'
					), 
				), 
			) );
			$alerts = array();
			if ( ! empty( $ad ) ) {
				$p = array_shift( $ad );
				$author = get_user_by( 'id', $p->post_author );
				$author = $author->display_name;
				$url = get_post_meta( $p->ID, 'wpcf-_advisory_permalink', true );
				if ( empty( $url ) || ! esc_url( $url ) )
					$url = get_permalink( $p->ID );
				$alerts['local'] = array(
					'title'   => apply_filters( 'the_title', $p->post_title ), 
					'content' => apply_filters( 'the_content', $p->post_content ), 
					'excerpt' => apply_filters( 'the_excerpt', $p->post_excerpt ), 
					'author'  => $author, 
					'date'    => get_the_date( '', $p->ID ), 
					'url'     => esc_url( $url ), 
					'class'   => $this->is_alerts ? 'campus-advisory' : 'local-advisory', 
				);
			}
			
			echo json_encode( $alerts );
			wp_die();
		}
		
		/**
		 * Output the JavaScript that handles the global advisories
		 */
		function do_global_advisories_script() {
			$data = array(
				'ajaxurl' => str_replace( array( 'http://', 'https://' ), array( '//', '//' ), admin_url( 'admin-ajax.php' ) ), 
				'is_root' => $this->is_root ? 1 : 0, 
				'is_alerts' => $this->is_alerts ? 1 : 0, 
				'nonce'   => wp_create_nonce( 'umw-active-alerts-ajax' ), 
				'localized' => true, 
				'dolocals' => post_type_exists( 'advisory' ), 
			);
			
			wp_localize_script( 'umw-active-alerts', 'UMWAlertsData', $data );
		}
		
		/**
		 * Checks for the existence of an active local advisory
		 */
		function do_local_advisories_script() {
			return;
		}
		
		/**
		 * Instantiate the UMW_Active_Alerts object and assign it 
		 * 		to the $umw_active_alerts_obj global var
		 */
		static function instance() {
			global $umw_active_alerts_obj;
			if ( ! isset( $umw_active_alerts_obj ) )
				$umw_active_alerts_obj = new UMW_Active_Alerts;
		}
	}
}