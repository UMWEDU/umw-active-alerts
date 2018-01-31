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

	        	add_filter( 'acf/load_value/type=date_time_picker', array( $this, 'default_expiry' ) );
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
		        $this->register_post_types();

		        if ( ! class_exists( 'Syndication' ) ) {
			        require_once( plugin_dir_path( __FILE__ ) . '/class-umw-advisories-syndication.php' );
			        Syndication::instance();
		        }
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
		        $this->setup_acf();
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
        }
    }
}
