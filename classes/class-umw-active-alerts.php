<?php
/**
 * Define the UMW_Active_Alerts class
 * @package umw-active-alerts
 * @version 1.0
 */
class UMW_Active_Alerts {
	public $version = '1.0';
	
	function __construct() {
	}
	
	static function instance() {
		global $umw_active_alerts_obj;
		if ( ! isset( $umw_active_alerts_obj ) )
			$umw_active_alerts_obj = new UMW_Active_Alerts;
	}
}