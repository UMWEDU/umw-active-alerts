<?php
namespace {
	if ( ! defined( 'ABSPATH' ) ) {
		die( 'You do not have permission to access this file directly.' );
	}
}

namespace UMW_Advisories {
	if ( ! class_exists( 'Syndication' ) ) {
		class Syndication {
			/**
			 * @var \UMW_Advisories\Syndication $instance holds the single instance of this class
			 * @access private
			 */
			private static $instance;

			/**
			 * @var string $version holds the version number for the plugin
			 * @access public
			 */
			public $version = null;

			/**
			 * @var array $api_uris the URIs for the various REST endpoints used by this plugin
			 * @access private
			 */
			private $api_uris = array();

			/**
			 * @var array $headers an array of the headers that need to be sent to the REST API
			 * @access private
			 */
			private $headers = array();

			/**
			 * @var bool a variable to determine whether we've performed init actions or not
			 * @access private
			 */
			private $did_init = false;

			/**
			 * Construct our \UMW_Advisories\Syndication object
			 *
			 * @access private
			 * @since  2018.1
			 */
			private function __construct() {
				add_action( 'plugins_loaded', array( $this, 'do_init' ) );
			}

			/**
			 * Returns the instance of this class.
			 *
			 * @access  public
			 * @since   0.1
			 * @return  \UMW_Advisories\Syndication
			 */
			public static function instance() {
				if ( ! isset( self::$instance ) ) {
					$className      = __CLASS__;
					self::$instance = new $className;
				}

				return self::$instance;
			}

			/**
			 * Run any startup actions that need to happen
			 *
			 * @access public
			 * @since  1.0
			 * @return void
			 */
			public function do_init() {
				if ( true === $this->did_init )
					return;

				$this->did_init = true;

				$this->version = Plugin::$version;
				$this->_get_api_uris();
				$this->_set_api_headers();

				add_action( 'save_post_advisory', array( $this, 'push_advisory' ), 10, 2 );
				add_action( 'wp_trash_post', array( $this, 'trash_advisory' ) );
				add_action( 'untrashed_post', array( $this, 'untrash_advisory' ) );
				add_action( 'delete_post', array( $this, 'delete_advisory' ) );

				return;
			}

			/**
			 * Set the API URIs for syndication
			 *
			 * @access private
			 * @since  0.1
			 * @return void
			 */
			private function _get_api_uris() {
				$url = Plugin::instance()->get_alerts_url();
				$this->api_uris = apply_filters( 'umw-alerts-api-uris', array(
					'publish' => $url . '/wp-json/wp/v2/advisories',
					'update'  => $url . '/wp-json/wp/v2/advisories/%1$d',
					'delete'  => $url . '/wp-json/wp/v2/advisories/%1$d',
					'trash'   => $url . '/wp-json/wp/v2/advisories/%1$d',
					'meta'    => $url . '/wp-json/wp/v2/posts/%1d/meta',
				) );
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
			 * Attempt to identify the author for an advisory
			 * @param \WP_Post $p the post object being pushed
			 *
			 * @access private
			 * @since  1.0
			 * @return string
			 */
			private function _get_advisory_author( $p=null ) {
				if ( empty( $p ) )
					$p = (object) array( 'post_author' => null );

				if ( isset( $_REQUEST['author_override'] ) && is_numeric( $_REQUEST['author_override'] ) ) {
					$author = $_REQUEST['author_override'];
				} else if ( isset( $_REQUEST['post_author'] ) && is_numeric( $_REQUEST['post_author'] ) ) {
					$author = $_REQUEST['post_author'];
				} else {
					$author = $p->post_author;
				}

				$author = get_user_by( 'id', $author );
				$author = $author->display_name;

				return $author;
			}

			/**
			 * Retrieve the ID of the syndicated version of this advisory
			 * @param \WP_Post $p the post object being syndicated
			 *
			 * @access private
			 * @since  1.0
			 * @return int
			 */
			private function _get_syndicated_id( $p=null ) {
				if ( is_numeric( $p ) ) {
					$p = get_post( $p );
				}

				if ( ! is_a( $p, '\WP_Post' ) )
					return null;

				if ( isset( $_REQUEST['post_ID'] ) && is_numeric( $_REQUEST['post_ID'] ) ) {
					$syndicated_id = get_post_meta( $_REQUEST['post_ID'], '_syndicated-alert-id', true );
				} else {
					$syndicated_id = get_post_meta( $p->ID, '_syndicated-alert-id', true );
				}

				return $syndicated_id;
			}

			/**
			 * Assemble the post meta array for REST API publishing
			 * @param string $expires the expiry date/time for the advisory
			 * @param int $post_id the ID of the post being pushed
			 * @param string $author the author display name for the advisory
			 *
			 * @access private
			 * @since  1.0
			 * @return array
			 */
			private function _get_advisory_meta_data( $expires=null, $post_id=null, $author='' ) {
				return array(
					'_advisory_expires_time' => $expires,
					'_advisory_permalink' => esc_url( get_permalink( $post_id ) ),
					'_advisory_author' => $author,
				);
			}

			/**
			 * Assemble the body of a syndication request
			 * @param \WP_Post $p the post being syndicated
			 * @param array $meta the meta data being added to the advisory
			 *
			 * @access private
			 * @since  1.0
			 * @return array
			 */
			private function _get_syndication_body( $p=null, $meta=array() ) {
				return array(
					'title'   => $p->post_title,
					'content' => $p->post_content,
					'status'  => $p->post_status,
					'post_meta' => json_encode( $meta ),
				);
			}

			/**
			 * Push a new external advisory from the source site to the
			 * 		central Advisories site
			 * @param int $post_id the ID of the post being syndicated
			 * @param \WP_Post $p the post object being pushed
			 *
			 * @access public
			 * @since  1.0
			 * @return bool
			 */
			public function push_advisory( $post_id, $p=null ) {
				if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
					return false;
				if ( wp_is_post_revision( $post_id ) )
					return false;

				if ( empty( $p ) )
					$p = get_post( $post_id );

				$syndicated_id = $this->_get_syndicated_id( $p );
				$author = $this->_get_advisory_author( $p );
				$expires = get_field( '_advisory_expires_time', $p->ID, false );

				$meta = $this->_get_advisory_meta_data( $expires, $post_id, $author );

				$body = $this->_get_syndication_body( $p, $meta );

				if ( empty( $syndicated_id ) ) {
					$url = $this->api_uris['publish'];

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
							$url = $this->api_uris['publish'];
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
			 * @param array $body the array of body content being pushed
			 * @param string $url the syndication URL
			 * @param string $method the HTTP method being used to push
			 *
			 * @access private
			 * @since  0.1
			 * @return bool|object|array
			 */
			private function _push_advisory_new( $body, $url, $method='POST' ) {
				$args = array( 'headers' => $this->_get_api_headers(), 'body' => http_build_query( $body ) );
				$done = wp_safe_remote_post( $url, $args );
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[Alert API Debug]: Push URL: ' . $url );
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
			 * @param int $syndicated_id the ID of the post on the central advisories site
			 * @param array $body the array of body content that needs to be syndicated
			 * @param string $url the URL of the destination site
			 * @param string $method the HTTP method to use for this action
			 *
			 * @access private
			 * @since  0.1
			 * @return bool|object|array
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
			 * @param string $url the REST API URL
			 * @param array $meta the array of meta data being pushed
			 *
			 * @access private
			 * @since  0.1
			 * @return void
			 */
			private function _push_advisory_meta( $url, $meta ) {
				error_log( '[Alerts API Debug]: Meta API URL: ' . $url );
				error_log( '[Alerts API Debug]: Attempting to push the following meta data: ' . print_r( $meta, true ) );

				$original = wp_remote_get( $url, array( 'headers' => $this->_get_api_headers(), 'body' => '' ) );
				$original = @json_decode( wp_remote_retrieve_body( $original ) );

				error_log( '[ALerts API Debug]: The original meta data looks like: ' . print_r( $original, true ) );

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
			 * @param int $post_id the ID of the post being syndicated
			 * @param \WP_Post $p the post object being syndicated
			 *
			 * @access public
			 * @since  0.1
			 * @return void
			 */
			public function fix_api_formatting( $post_id=null, $p=null ) {
				if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
					return;
				if ( wp_is_post_revision( $post_id ) )
					return;

				if ( empty( $p ) )
					$p = get_post( $post_id );

				remove_action( 'save_post_external-advisory', array( $this, 'fix_api_formatting' ), 10 );

				wp_update_post( array( 'ID' => $p->ID, 'post_title' => stripslashes( $p->post_title ), 'post_content' => stripslashes( $p->post_content ) ) );

				add_action( 'save_post_external-advisory', array( $this, 'fix_api_formatting' ), 10, 2 );
			}

			/**
			 * Since there is currently no process to permanently delete a post with the API,
			 * 		let's do it a different way
			 * @param int $post_id the ID of the post being removed
			 *
			 * @access public
			 * @since  0.1
			 * @return void
			 */
			public function really_delete_syndicated_advisory( $post_id ) {
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
			 * @param int $post_id the ID of the post being trashed
			 * @param bool $force whether to skip the trash or not
			 *
			 * @access public
			 * @since  0.1
			 * @return bool
			 */
			public function trash_advisory( $post_id=null, $force=false ) {
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
					remove_action( 'save_post_advisory', array( $this, 'push_advisory' ), 10 );
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
			 * @param int $post_id the ID of the post being re-published
			 *
			 * @access public
			 * @since  0.1
			 * @return bool
			 */
			public function untrash_advisory( $post_id=null ) {
				if ( empty( $post_id ) ) {
					$post_id = isset( $_REQUEST['post'] ) && is_numeric( $_REQUEST['post'] ) ? $_REQUEST['post'] : null;
				}
				if ( empty( $post_id ) )
					return false;

				$p = get_post( $post_id );
				if ( 'advisory' != $p->post_type )
					return false;

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
						'key'   => '_advisory_expires_time',
						'value' => get_field( '_advisory_expires_time', $post_id, false ),
					),
					(object)array(
						'key'   => '_advisory_permalink',
						'value' => esc_url( get_permalink( $post_id ) ),
					),
					(object)array(
						'key'   => '_advisory_author',
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

				return true;
			}

			/**
			 * Permanently delete an advisory on the main Advisories site
			 * @param int $post_id the ID of the post being deleted
			 *
			 * @access public
			 * @since  0.1
			 * @return bool
			 */
			public function delete_advisory( $post_id=null ) {
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

				$expires = get_field( '_advisory_expires_time', $post['ID'], false );
				if ( ! empty( $expires ) && is_numeric( $expires ) ) {
					$meta['_advisory_expires_time'] = $expires;
				}
				$permalink = get_permalink( $post['ID'] );
				if ( esc_url( $permalink ) ) {
					$meta['_advisory_permalink'] = esc_url( $permalink );
				}

				$author = get_user_by( 'id', $post['post_author'] );
				$author = $author->display_name;

				if ( ! empty( $author ) ) {
					$meta['_advisory_author'] = $author;
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