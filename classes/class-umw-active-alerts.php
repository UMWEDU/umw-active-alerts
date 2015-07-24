<?php
/**
 * Define the UMW_Active_Alerts class
 * @package umw-active-alerts
 * @version 1.0
 */
class UMW_Active_Alerts {
	public $version = '1.0';
	public $is_root = false;
	public $root_url = null;
	public $is_alerts = false;
	public $alerts_url = null;
	public $db_version = '20150724110000';
	
	function __construct() {
		$this->umw_is_root();
		$this->umw_is_alerts_site();
		
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}
	
	/**
	 * Perform any init-level actions that need to happen
	 */
	function init() {
		if ( $this->is_alerts ) {
			$this->register_post_types_main();
			$this->add_feeds_main();
		} else {
			$this->register_post_types();
			$this->add_feeds();
		}
		/**
		 * If we've changed the feed structure since the rewrite rules were last 
		 * 		flushed, we need to flush them again to update it
		 */
		if ( get_option( 'umw-active-alerts-dbversion', false ) !== $this->db_version ) {
			global $wp_rewrite;
			$wp_rewrite->flush_rules( false );
			update_option( 'umw-active-alerts-dbversion', $this->db_version );
		}
	}
	
	/**
	 * Register any post types & taxonomies we need for the main 
	 * 		Alerts site
	 */
	function register_post_types_main() {
	}
	
	/**
	 * Register any custom feeds that need to come from the main 
	 * 		Alerts site
	 */
	function add_feeds_main() {
	}
	
	/**
	 * Register any post types & taxonomies we need for all 
	 * 		sites that are not the main Alerts site
	 */
	function register_post_types() {
	}
	
	/**
	 * Register any custom feeds that need to come from all 
	 * 		site that are not the main Alerts site
	 */
	function add_feeds() {
	}
	
	/**
	 * Determine whether or not this is the root site, and set the 
	 * 		URL of the root UMW site/
	 * @uses UMW_IS_ROOT
	 * @uses UMW_Active_Alerts::$is_root
	 * @uses UMW_Active_Alerts::$root_url
	 * @uses esc_url()
	 * @return void
	 */
	function umw_is_root() {
		if ( defined( 'UMW_IS_ROOT' ) && is_numeric( UMW_IS_ROOT ) ) {
			if ( $GLOBALS['blog_id'] == UMW_IS_ROOT ) {
				$this->is_root = true;
				$this->root_url = get_bloginfo( 'url' );
			} else {
				$this->root_url = get_blog_option( UMW_IS_ROOT, 'homeurl' );
			}
		}
		
		$this->root_url = esc_url( UMW_IS_ROOT );
	}
	
	/**
	 * Determine whether or not this is the main Alerts site, and set the 
	 * 		URL of the main ALerts site
	 * @uses UMW_ALERTS_SITE
	 * @uses UMW_Active_Alerts::$is_alerts
	 * @uses UMW_Active_Alerts::$alerts_url
	 * @uses esc_url()
	 * @return void
	 */
	function umw_is_alerts_site() {
		if ( defined( 'UMW_ALERTS_SITE' ) && is_numeric( UMW_ALERTS_SITE ) ) {
			if ( $GLOBALS['blog_id'] == UMW_ALERTS_SITE ) {
				$this->is_alerts = true;
				$this->alerts_url = get_bloginfo( 'url' );
			} else {
				$this->alerts_url = get_blog_option( UMW_ALERTS_SITE, 'homeurl' );
			}
		}
		
		$this->alerts_url = esc_url( UMW_ALERTS_SITE );
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