<?php
/*
Plugin Name: UMW Active Alerts
Description: Inserts the active alert on the home page if there is an active alert.
Version: 1.0
Author: Curtiss Grymala
Author URI: http://ten-321.com/
License: GPL2
*/

if ( ! class_exists( '\UMW_Advisories\Plugin' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . '/classes/class-umw-advisories-plugin.php' );
}

if ( ! isset( $umw_active_alerts_obj ) || ! is_a( $umw_active_alerts_obj, '\UMW_Advisories\Plugin' ) )
	$GLOBALS['umw_active_alerts_obj'] = \UMW_Advisories\Plugin::instance();