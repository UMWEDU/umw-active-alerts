<?php
namespace {
	if ( ! defined( 'ABSPATH' ) ) {
		die( 'You do not have permission to access this file directly.' );
	}
}

namespace UMW_Advisories {
	if ( ! class_exists( 'Ajax' ) ) {
		class Ajax {
			/**
			 * @var \UMW_Advisories\Ajax $instance holds the single instance of this class
			 * @access private
			 */
			private static $instance;

			/**
			 * @var bool $started whether or not we've already handled the startup functions
			 * @access private
			 */
			private $started;

			/**
			 * @var bool $is_root whether this is the root site of the UMW system or not
			 * @access private
			 */
			private $is_root = false;

			/**
			 * @var bool $is_alerts whether this is the main Advisories site or not
			 * @access private
			 */
			private $is_alerts = false;

			/**
			 * Creates the \UMW_Advisories\Ajax object
			 *
			 * @access private
			 * @since  0.1
			 */
			private function __construct( $args=array() ) {
				if ( is_admin() )
					return;

				if ( is_array( $args ) && ! empty( $args ) ) {
					$this->_set_vars( $args );
				}
				add_action( 'wp_enqueue_scripts', array( $this, 'startup' ) );
			}

			/**
			 * Returns the instance of this class.
			 *
			 * @access  public
			 * @since   0.1
			 * @return  \UMW_Advisories\Ajax
			 */
			public static function instance( $args=array() ) {
				if ( ! isset( self::$instance ) ) {
					$className      = __CLASS__;
					self::$instance = new $className( $args );
				}

				return self::$instance;
			}

			/**
			 * Set the vars that we need
			 * @param array $args the vars that need to be set
			 *
			 * @access private
			 * @since  1.0
			 * @return void
			 */
			private function _set_vars( $args=array() ) {
				if ( array_key_exists( 'is_alerts', $args ) ) {
					$this->is_alerts = $args['is_alerts'];
				}
				if ( array_key_exists( 'is_root', $args ) ) {
					$this->is_root = $args['is_root'];
				}
			}

			/**
			 * Set up any actions that need to happen for retrieval/display of advisories
			 *
			 * @access public
			 * @since  1.0
			 * @return void
			 */
			public function startup() {
				if ( $this->started )
					return;

				$this->started = true;

				add_action( 'wp_print_footer_scripts', array( $this, 'footer_scripts' ) );
			}

			/**
			 * Retrieve the appropriate values that need to be localized for the JavaScript
			 *
			 * @access private
			 * @since  1.0
			 * @return string
			 */
			private function _get_script_vars() {
				$vars = array(
					'alerts_url' => sprintf( '%s/wp-json/wp/v2/advisory', str_replace( array( 'http:', 'https:' ), '', Plugin::instance()->get_alerts_url() ) ),
					'local_url' => str_replace( array( 'http:', 'https:' ), '', get_rest_url( $GLOBALS['blog_id'], '/wp/v2/advisory' ) ),
					'emergency_url' => sprintf( '%s/wp-json/wp/v2/alert', str_replace( array( 'http:', 'https:' ), '', Plugin::instance()->get_alerts_url() ) ),
					'is_root' => $this->is_root,
					'is_alerts' => $this->is_alerts,
					'css_url' => str_replace( array( 'http:', 'https:' ), '', add_query_arg( 'v', Plugin::$version, plugin_dir_url( dirname( __FILE__ ) ) . '/styles/umw-active-alerts.css' ) ),
				);

				return json_encode( $vars );
			}

			/**
			 * Output the JavaScript that needs to go in the footer
			 *
			 * @access public
			 * @since  1.0
			 * @return void
			 */
			public function footer_scripts() {
				echo '<script type="text/javascript">';
				printf( 'var advisoriesObject = advisoriesObject || %s;', $this->_get_script_vars() );
				ob_start();
				require_once( plugin_dir_path( __FILE__ ) . 'inc/ajax-scripts.js' );
				echo ob_get_clean();
				echo '</script>';
			}
		}
	}
}