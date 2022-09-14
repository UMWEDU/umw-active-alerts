<?php
namespace {
	if ( ! defined( 'ABSPATH' ) ) {
		die( 'You do not have permission to access this file directly.' );
	}
}

namespace UMW\Active_Alerts {
	if ( ! class_exists( 'Plugin' ) ) {
		class Plugin {
			/**
			 * @var Plugin $instance holds the single instance of this class
			 * @access private
			 */
			private static Plugin $instance;
			/**
			 * @var string $version holds the version number for the plugin
			 * @access public
			 */
			public static string $version = 'rewrite-2022.08.17.30';
			/**
			 * @var string $namespace holds the current namespace for this class
			 * @access private
			 */
			private static string $namespace = __NAMESPACE__;

			/**
			 * Creates the Plugin object
			 *
			 * @access private
			 * @since  0.1
			 */
			private function __construct() {
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
				add_filter( 'heartbeat_received', array( $this, 'receive_heartbeat' ) );
			}

			/**
			 * Returns the instance of this class.
			 *
			 * @access  public
			 * @return  Plugin
			 * @since   0.1
			 */
			public static function instance(): Plugin {
				if ( ! isset( self::$instance ) ) {
					$className      = __CLASS__;
					self::$instance = new $className;
				}

				return self::$instance;
			}

			/**
			 * Register and enqueue necessary scripts and stylesheets
			 *
			 * @access public
			 * @return void
			 * @since  0.1
			 */
			public function enqueue_scripts() {
				$show_advisory = false;
				$show_emergency = true;
				$advisory_url = '';
				$emergency_url = '';
				$local_url = get_rest_url( get_current_blog_id(), 'wp/v2/advisory' );

				if ( defined( 'UMW_IS_ROOT' ) ) {
					if ( is_numeric( UMW_IS_ROOT ) ) {
						if ( intval( $GLOBALS['blog_id'] ) === intval( UMW_IS_ROOT ) && is_front_page() ) {
							$show_advisory = true;
						}
					}
				}

				if ( defined( 'UMW_ADVISORIES_SITE' ) ) {
					if ( is_numeric( UMW_ADVISORIES_SITE ) ) {
						if ( intval( $GLOBALS['blog_id'] ) === intval( UMW_ADVISORIES_SITE ) ) {
							$show_advisory = true;
							$local_url = get_rest_url( UMW_ADVISORIES_SITE, '/wp/v2/external-advisory' );
						}
						$advisory_url = get_rest_url( UMW_ADVISORIES_SITE, '/wp/v2/advisory' );
						$emergency_url = get_rest_url( UMW_ADVISORIES_SITE, '/wp/v2/alert' );
					} else {
						$advisory_url = esc_url( trailingslashit( UMW_ADVISORIES_SITE ) . 'wp-json/wp/v2/advisory' );
						$emergency_url = esc_url( trailingslashit( UMW_ADVISORIES_SITE ) . 'wp-json/wp/v2/alert' );
					}
				} else {
					$advisory_url = esc_url( 'https://www.umw.edu/advisories/' . 'wp-json/wp/v2/advisory' );
					$emergency_url = esc_url( 'https://www.umw.edu/advisories/' . 'wp-json/wp/v2/alert' );
				}

				wp_register_style( 'umw-active-alerts', self::plugins_url( '/dist/css/umw-active-alerts.css' ), array(), self::$version, 'all' );
				wp_register_script( 'umw-active-alerts', self::plugins_url( '/dist/js/umw-active-alerts.js' ), array(), self::$version, false );
				wp_localize_script( 'umw-active-alerts', 'umw_active_alerts_vars', array(
					'show_advisory' => $show_advisory,
					'show_emergency' => $show_emergency,
					'advisory_url' => $advisory_url,
					'emergency_url' => $emergency_url,
					'local_url' => $local_url,
				) );

				return;
			}

			/**
			 * Receive Heartbeat data and respond.
			 *
			 * Processes data received via a Heartbeat request, and returns additional data to pass back to the front end.
			 *
			 * @param array $response Heartbeat response data to pass back to front end.
			 * @param array $data     Data received from the front end (unslashed).
			 *
			 * @return array
			 */
			public function receive_heartbeat( array $response, array $data ): array {
				if ( empty( $data['umwalerts'] ) ) {
					return $response;
				}

				$curtime = time();
				$intervals = array(
					'zero' => strtotime( date( 'Y-m-d g:i:00', $curtime ) ),
					'thirty' => strtotime( date( 'Y-m-d g:i:30', $curtime ) ),
				);

				$vstring = $intervals['zero'];
				if ( $intervals['thirty'] <= $curtime ) {
					$vstring = $intervals['thirty'];
				}

				$args = array(
					'posts_per_page' => 1,
					'orderby' => 'meta_value_num',
					'meta_key' => '_advisory_expires_time',
					'_embed' => 1,
					'v' => $vstring,
				);

				$response['umwalerts'] = array();

				if ( $data['umwalerts']['show_emergency'] ) {
					$result    = wp_remote_get( esc_url( add_query_arg( $args, $data['umwalerts']['emergency_url'] ) ) );
					$emergency = wp_remote_retrieve_body( $result );
					if ( $emergency = json_decode( $emergency ) ) {
						$response['umwalerts']['emergency'] = $emergency;
					}
				}

				if ( $data['umwalerts']['show_advisory'] ) {
					$result   = wp_remote_get( esc_url( add_query_arg( $args, $data['umwalerts']['advisory_url'] ) ) );
					$advisory = wp_remote_retrieve_body( $result );
					if ( $advisory = json_decode( $advisory ) ) {
						$response['umwalerts']['advisory'] = $advisory;
					}
				}

				$result = wp_remote_get( esc_url( add_query_arg( $args, $data['umwalerts']['local_url'] ) ) );
				$advisory = wp_remote_retrieve_body( $result );
				if ( $advisory = json_decode( $advisory ) ) {
					$response['umwalerts']['local'] = $advisory;
				}

				return $response;
			}
		}
	}
}