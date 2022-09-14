<?php
namespace {
	if ( ! defined( 'ABSPATH' ) ) {
		die( 'You do not have permission to access this file directly.' );
	}
}

namespace UMW\Active_Alerts {
	if ( ! class_exists( 'Helpers' ) ) {
		class Helpers {
			/**
			 * Custom logging function that can be short-circuited
			 *
			 * @access public
			 * @return void
			 * @since  0.1
			 */
			public static function log( $message ) {
				if ( ! defined( 'WP_DEBUG' ) || false === WP_DEBUG ) {
					return;
				}

				error_log( '[Active Alerts Plugin Debug]: ' . $message );
			}

			/**
			 * Retrieve a URL relative to the root of this plugin
			 *
			 * @param string $path the path to append to the root plugin path
			 *
			 * @access public
			 * @return string the full URL to the provided path
			 * @since  0.1
			 */
			public static function plugins_url( $path ): string {
				return plugins_url( $path, dirname( __FILE__, 4 ) );
			}

			/**
			 * Retrieve and return the root path of this plugin
			 *
			 * @access public
			 * @return string the absolute path to the root of this plugin
			 * @since  0.1
			 */
			public static function plugin_dir_path(): string {
				return plugin_dir_path( dirname( __FILE__, 4 ) );
			}

			/**
			 * Retrieve and return the root URL of this plugin
			 *
			 * @access public
			 * @return string the absolute URL
			 * @since  0.1
			 */
			public static function plugin_dir_url(): string {
				return plugin_dir_url( dirname( __FILE__, 4 ) );
			}
		}
	}
}