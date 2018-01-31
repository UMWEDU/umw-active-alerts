<?php
namespace {
    if ( ! defined( 'ABSPATH' ) ) {
        die( 'You do not have permission to access this file directly.' );
    }
}

namespace UMW_Advisories {
    if ( ! class_exists('Plugin') ) {
        class Plugin {
            /**
             * @var \UMW_Advisories\Plugin $instance holds the single instance of this class
             * @access private
             */
            private static $instance;

            /**
             * @var string $version holds the version number for the plugin
             * @access public
             */
            public static $version = '2018.1';

	        /**
	         * @var bool $is_root whether this is the root site of the UMW system or not
	         * @access private
	         */
	        private $is_root = false;

	        /**
	         * @var string $root_url the URL to the root UMW site
	         * @access private
	         */
	        private $root_url = null;

	        /**
	         * @var bool $is_alerts whether this is the main Advisories site or not
	         * @access private
	         */
	        private $is_alerts = false;

	        /**
	         * @var string $alerts_url the URL to the main Advisories site
	         * @access private
	         */
	        private $alerts_url = null;

            /**
             * Creates the \AdmLdg17\Plugin object
             *
             * @access private
             * @since  0.1
             */
            private function __construct() {
	            $this->is_root();
	            $this->is_advisories();
	            $this->setup_acf();
            }

            /**
             * Returns the instance of this class.
             *
             * @access  public
             * @since   0.1
             * @return  \UMW_Advisories\Plugin
             */
            public static function instance() {
                if ( ! isset( self::$instance ) ) {
                    $className      = __CLASS__;
                    self::$instance = new $className;
                }

                return self::$instance;
            }

            /**
             * Determines whether this is the root UMW site
             * Also determines the URL to the root UMW site
             *
             * @uses UMW_IS_ROOT
             * @uses \UMW_Advisories\Plugin::$is_root
             * @uses \UMW_Advisories\Plugin::$root_url
             *
             * @access private
             * @since  1.0
             * @return void
             */
            private function is_root() {
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
             * Determine whether this is the main Advisories site or not
             * Also determines the URL to the main Advisories site
             *
             * @uses UMW_ADVISORIES_SITE
             * @uses \UMW_Advisories\Plugin\$is_alerts
             * @uses \UMW_Advisories\Plugin\$alerts_url
             * @uses \UMW_Advisories\Plugin\setup_alerts_site()
             * @uses \UMW_Advisories\Plugin\add_syndication_actions()
             *
             * @access private
             * @since  1.0
             * @return void
             */
            private function is_advisories() {
            	if ( ! defined( 'UMW_ADVISORIES_SITE' ) )
            		return;

	            if ( is_numeric( UMW_ADVISORIES_SITE ) ) {
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
	         * Setup the necessary filters/includes for use with ACF
	         *
	         * @access private
	         * @since  2018.1
	         * @return void
	         */
	        private function setup_acf() {
	        	add_filter( 'acf/settings/path', function() { return plugin_dir_path( __FILE__ ) . '/includes/acf/'; } );
	        	add_filter( 'acf/settings/dir', function() { return plugin_dir_url( __FILE__ ) . '/includes/acf/'; } );
	        	if ( ! is_plugin_active( 'advanced-custom-fields-pro' ) && ( is_multisite() && ! is_plugin_active_for_network( 'advanced-custom-fields-pro' ) ) ) {
	        		add_filter( 'acf/settings/show_admin', '__return_false' );
		        }
		        include_once( plugin_dir_path( __FILE__ ) . '/includes/acf/acf.php' );
	        	include_once( plugin_dir_path( __FILE__ ) . '/includes/acf-fields.php' );
	        }

            /**
             * Setup any actions/filters that need to be registered on the
             * 		main Advisories site
             *
             * @access private
             * @since  1.0
             * @return void
             */
            private function setup_alerts_site() {
	            add_action( 'rest_api_init', array( $this, 'bypass_cas' ) );
            }

	        /**
	         * Setup any syndication actions that need to be handled
	         */
	        private function add_syndication_actions() {
		        /*add_action( 'save_post_advisory', array( $this, 'push_advisory' ), 10, 2 );
		        add_action( 'wp_trash_post', array( $this, 'trash_advisory' ) );
		        add_action( 'untrashed_post', array( $this, 'untrash_advisory' ) );
		        add_action( 'delete_post', array( $this, 'delete_advisory' ) );*/

		        $this->register_post_types();
	        }

	        /**
	         * Register the necessary post types and custom fields for this plugin
	         */
	        private function register_post_types() {
	        	if ( $this->is_alerts ) {
	        		require_once( plugin_dir_path( __FILE__ ) . '/includes/post-types-root.php' );
		        } else {
	        		require_once( plugin_dir_path( __FILE__ ) . '/includes/post-types-remote.php' );
		        }
	        }

            /**
             * If we are attempting to perform an authenticated REST API request,
             *      we need to bypass the CAS authentication and go straight to
             *      WordPress native authentication
             *
             * @uses WPCAS_BYPASS
             *
             * @access public
             * @since  1.0
             * @return void
             */
            public function bypass_cas() {
	            if ( ! defined( 'WPCAS_BYPASS' ) )
		            define( 'WPCAS_BYPASS', true );
            }

	        /**
	         * Push a new external advisory from the source site to the
	         * 		central Advisories site
	         *
	         * @access public
	         * @since  1.0
	         * @return bool
	         */
	        function push_advisory( $post_id, $p=null ) {
		        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			        return false;
		        if ( wp_is_post_revision( $post_id ) )
			        return false;

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

		        return true;
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

        }
    }
}
