<?php
namespace {
	if ( ! defined( 'ABSPATH' ) ) {
		die( 'You do not have permission to access this file directly.' );
	}
}

namespace UMW_Advisories {
	if ( ! class_exists( 'Debug' ) ) {
		class Debug {
			/**
			 * @var \UMW_Advisories\Debug $instance holds the single instance of this class
			 * @access private
			 */
			private static $instance;

			/**
			 * Creates the \UMW_Advisories\Debug object
			 *
			 * @access private
			 * @since  0.1
			 */
			private function __construct() {
			}

			/**
			 * Returns the instance of this class.
			 *
			 * @access  public
			 * @since   0.1
			 * @return  \UMW_Advisories\Debug
			 */
			public static function instance() {
				if ( ! isset( self::$instance ) ) {
					$className      = __CLASS__;
					self::$instance = new $className;
				}

				return self::$instance;
			}

			/**
			 * Output a debug message if error logging is enabled
			 * @param string $message the message to be logged
			 *
			 * @access public
			 * @since  1.0
			 * @return void
			 */
			public static function log( $message ) {
				if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG )
					return;

				error_log( '[UMW Advisories Debug] ' . $message );
			}
		}
	}
}