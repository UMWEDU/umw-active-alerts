<?php
/*
Plugin Name: UMW Active Alerts
Description: Inserts the active alert on the home page if there is an active alert.
Version: 1.0
Author: Curtiss Grymala
Author URI: http://ten-321.com/
License: GPL2
@TODO Implement widget for Local Advisories display on Advisories website (previously implemented with a View)
*/

namespace {
	spl_autoload_register( function ( $class_name ) {
		if ( ! stristr( $class_name, 'UMW\Advisories\\' ) ) {
			return;
		}

		$filename = plugin_dir_path( __FILE__ ) . '/lib/classes/' . strtolower( str_replace( array(
				'\\',
				'_'
			), array( '/', '-' ), $class_name ) ) . '.php';

		if ( ! file_exists( $filename ) ) {
			return;
		}

		include_once $filename;
	} );
}

namespace UMW\Advisories {
	if ( ! isset( $umw_active_alerts_obj ) || ! is_a( $umw_active_alerts_obj, '\UMW\Advisories\Plugin' ) ) {
		$GLOBALS['umw_active_alerts_obj'] = Plugin::instance();
	}
}