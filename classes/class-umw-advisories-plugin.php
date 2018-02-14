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
	         * @var bool a variable to determine whether we've performed init actions or not
	         * @access private
	         */
	        private $did_init = false;

            /**
             * Creates the \UMW_Advisories\Plugin object
             *
             * @access private
             * @since  0.1
             */
            private function __construct() {
            	if ( is_network_admin() )
            		return;

            	add_action( 'muplugins_loaded', array( $this, 'do_init' ) );
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

		        $this->is_root();
		        $this->is_advisories();
		        add_action( 'init', array( $this, 'maybe_do_upgrade' ) );
		        add_filter( 'is_protected_meta', array( $this, 'unprotect_meta' ), 10, 2 );
	        }

	        /**
	         * Checks to see whether plugin upgrade functions need to be run
	         *
	         * @access public
	         * @since  1.0
	         * @return void
	         */
	        public function maybe_do_upgrade() {
	        	if ( ! current_user_can( 'delete_users' ) )
	        		return;

	        	if ( ! isset( $_REQUEST['test_alerts_upgrade'] ) )
	        		return;

		        $up_to_date = get_option( 'umw_advisories_version', false );
		        if ( $up_to_date == Plugin::$version )
		        	return;

		        require_once( plugin_dir_path( __FILE__ ) . 'class-umw-advisories-upgrade.php' );
		        Upgrade::instance();
	        }

	        /**
	         * Retrieve and return the Alerts URL
	         *
	         * @access public
	         * @since  1.0
	         * @return string
	         */
	        public function get_alerts_url() {
	        	return esc_url( $this->alerts_url );
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
            	if ( ! defined ( 'UMW_IS_ROOT' ) )
            		return;

            	if ( ! is_numeric( UMW_IS_ROOT ) ) {
            		$this->root_url = esc_url( UMW_IS_ROOT );
            		return;
	            }

	            if ( UMW_IS_ROOT == $GLOBALS['blog_id'] ) {
            		$this->is_root = true;
            		$this->root_url = get_bloginfo( 'url' );
	            } else {
            		$this->root_url = get_blog_option( UMW_IS_ROOT, 'home_url', null );
	            }

	            return;
            }

            /**
             * Determine whether this is the main Advisories site or not
             * Also determines the URL to the main Advisories site
             *
             * @uses UMW_ADVISORIES_SITE
             * @uses \UMW_Advisories\Plugin::$is_alerts
             * @uses \UMW_Advisories\Plugin::$alerts_url
             * @uses \UMW_Advisories\Plugin::setup_alerts_site()
             * @uses \UMW_Advisories\Plugin::add_syndication_actions()
             *
             * @access private
             * @since  1.0
             * @return void
             */
            private function is_advisories() {
            	if ( ! defined( 'UMW_ADVISORIES_SITE' ) )
            		return;

            	$this->is_alerts = false;

            	if ( ! is_numeric( UMW_ADVISORIES_SITE ) ) {
		            $this->alerts_url = esc_url( UMW_ADVISORIES_SITE );
		            $this->add_syndication_actions();
		            return;
	            }

	            if ( UMW_ADVISORIES_SITE == $GLOBALS['blog_id'] ) {
		            $this->is_alerts = true;
		            $this->alerts_url = esc_url( get_bloginfo( 'url' ) );
		            $this->setup_alerts_site();
	            } else {
		            $this->alerts_url = esc_url( get_blog_option( UMW_ADVISORIES_SITE, 'home' ) );
		            $this->add_syndication_actions();
	            }

	            return;
            }

	        /**
	         * Setup the necessary filters/includes for use with ACF
	         *
	         * @access public
	         * @since  2018.1
	         * @return void
	         */
	        public function setup_acf() {
	        	if ( ! function_exists( 'is_plugin_active' ) ) {
			        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		        }

	        	if ( ! is_plugin_active( 'advanced-custom-fields-pro' ) && ( is_multisite() && ! is_plugin_active_for_network( 'advanced-custom-fields-pro' ) ) ) {
			        add_filter( 'acf/settings/path', array( $this, 'acf_path' ) );
			        add_filter( 'acf/settings/dir', array( $this, 'acf_url' ) );
	        		add_filter( 'acf/settings/show_admin', '__return_false' );
			        include_once( plugin_dir_path( __FILE__ ) . '/inc/acf/acf.php' );
		        }

	        	include_once( plugin_dir_path( __FILE__ ) . '/inc/acf-fields.php' );

	        	add_filter( 'acf/load_value/type=date_time_picker', array( $this, 'default_expiry' ), 10, 3 );
	        }

	        /**
	         * Return the path to ACF within this plugin
	         * @param string $path the existing path
	         *
	         * @access public
	         * @since  1.0
	         * @return string
	         */
	        public function acf_path( $path ) {
		        return plugin_dir_path( __FILE__ ) . 'inc/acf/';
	        }

	        /**
	         * Return the URL to ACF within this plugin
	         * @param string $url the existing URL
	         *
	         * @access public
	         * @since  1.0
	         * @return string
	         */
	        public function acf_url( $url ) {
		        return plugin_dir_url( __FILE__ ) . 'inc/acf/';
	        }

	        /**
	         * Set up a default expiration date/time for the advisory
	         * @param mixed $val the value of the current field
	         * @param int $post_id the ID of the post being edited
	         * @param array $field the information about the specific field
	         *
	         * @access public
	         * @since  1.0
	         * @return string
	         */
	        public function default_expiry( $val=null, $post_id=0, $field=array() ) {
	        	if ( ! empty( $val ) )
	        		return $val;

	        	return strtotime( '+24 hours', current_time( 'U' ) );
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
            	/*add_action( 'rest_api_init', array( $this, 'register_meta_fields' ) );*/
	            add_action( 'rest_api_init', array( $this, 'bypass_cas' ) );
	            add_action( 'rest_insert_external-advisory', array( $this, 'update_syndicated_meta' ), 10, 3 );
	            $this->register_post_types();
            }

	        /**
	         * Setup any syndication actions that need to be handled
	         */
	        private function add_syndication_actions() {
		        $this->register_post_types();
		        add_action( 'rest_api_init', array( $this, 'register_meta_fields' ) );

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
	        		add_action( 'init', array( $this, 'register_main_advisory_post_types' ) );
		        } else {
	        		add_action( 'init', array( $this, 'register_local_advisory_post_type' ) );
		        }

		        add_action( 'plugins_loaded', array( $this, 'setup_acf' ) );
	        }

	        /**
	         * Register the local advisory post type
	         *
	         * @access public
	         * @since  1.0
	         * @return void
	         */
	        public function register_local_advisory_post_type() {
		        $labels = array(
			        'name' => __( 'Advisories', 'twentyseventeen' ),
			        'singular_name' => __( 'Advisory', 'twentyseventeen' ),
		        );

		        $args = array(
			        'label' => __( 'Advisories', 'twentyseventeen' ),
			        'labels' => $labels,
			        'description' => '',
			        'public' => true,
			        'publicly_queryable' => true,
			        'show_ui' => true,
			        'show_in_rest' => true,
			        'rest_base' => '',
			        'has_archive' => false,
			        'show_in_menu' => true,
			        'exclude_from_search' => false,
			        'capability_type' => 'post',
			        'map_meta_cap' => true,
			        'hierarchical' => false,
			        'rewrite' => array( 'slug' => 'advisory', 'with_front' => false ),
			        'query_var' => true,
			        'menu_position' => 20,
			        'menu_icon' => 'dashicons-admin-comments',
			        'supports' => array( 'title', 'editor', 'excerpt', 'author' ),
		        );

		        register_post_type( 'advisory', $args );
	        }

	        /**
	         * Register the emergency alerts and normal advisories post types
	         *
	         * @access public
	         * @since  1.0
	         * @return void
	         */
	        public function register_main_advisory_post_types() {
		        /**
		         * Post Type: Advisories.
		         */

		        $labels = array(
			        'name' => __( 'Advisories', 'twentyseventeen' ),
			        'singular_name' => __( 'Advisory', 'twentyseventeen' ),
		        );

		        $args = array(
			        'label' => __( 'Advisories', 'twentyseventeen' ),
			        'labels' => $labels,
			        'description' => 'These are community-wide announcements and updates that need to be prominently featured, but are not urgent.',
			        'public' => true,
			        'publicly_queryable' => true,
			        'show_ui' => true,
			        'show_in_rest' => true,
			        'rest_base' => '',
			        'has_archive' => false,
			        'show_in_menu' => true,
			        'exclude_from_search' => false,
			        'capability_type' => 'page',
			        'map_meta_cap' => true,
			        'hierarchical' => false,
			        'rewrite' => array( 'slug' => 'advisory', 'with_front' => false ),
			        'query_var' => true,
			        'menu_position' => 5,
			        'menu_icon' => 'dashicons-welcome-view-site',
			        'supports' => array( 'title', 'editor', 'excerpt', 'author' ),
		        );

		        register_post_type( 'advisory', $args );

		        /**
		         * Post Type: Emergency Alerts.
		         */

		        $labels = array(
			        'name' => __( 'Emergency Alerts', 'twentyseventeen' ),
			        'singular_name' => __( 'Emergency Alert', 'twentyseventeen' ),
		        );

		        $args = array(
			        'label' => __( 'Emergency Alerts', 'twentyseventeen' ),
			        'labels' => $labels,
			        'description' => 'These are community-wide, urgent messages that need to be broadcast across the entire UMW website',
			        'public' => true,
			        'publicly_queryable' => true,
			        'show_ui' => true,
			        'show_in_rest' => true,
			        'rest_base' => '',
			        'has_archive' => false,
			        'show_in_menu' => true,
			        'exclude_from_search' => false,
			        'capability_type' => 'page',
			        'map_meta_cap' => true,
			        'hierarchical' => false,
			        'rewrite' => array( 'slug' => 'alert', 'with_front' => false ),
			        'query_var' => true,
			        'menu_position' => 5,
			        'menu_icon' => 'dashicons-welcome-comments',
			        'supports' => array( 'title', 'editor', 'excerpt', 'author' ),
		        );

		        register_post_type( 'alert', $args );

		        /**
		         * Post Type: External Advisories.
		         */

		        $labels = array(
			        'name' => __( 'External Advisories', 'twentyseventeen' ),
			        'singular_name' => __( 'External Advisory', 'twentyseventeen' ),
		        );

		        $args = array(
			        'label' => __( 'External Advisories', 'twentyseventeen' ),
			        'labels' => $labels,
			        'description' => 'These are advisories that have been syndicated *into* this site from other sites within the UMW system. These advisories should never be published/modified directly within the Advisories website; instead, they should be published/modified from the external source where they should originate.',
			        'public' => true,
			        'publicly_queryable' => true,
			        'show_ui' => true,
			        'show_in_rest' => true,
			        'rest_base' => 'advisories',
			        'has_archive' => false,
			        'show_in_menu' => true,
			        'exclude_from_search' => false,
			        'capability_type' => 'post',
			        'map_meta_cap' => true,
			        'hierarchical' => false,
			        'rewrite' => array( 'slug' => 'external-advisory', 'with_front' => false ),
			        'query_var' => true,
			        'menu_position' => 10,
			        'menu_icon' => 'dashicons-admin-comments',
			        'supports' => array( 'title', 'editor', 'custom-fields' ),
		        );

		        register_post_type( 'external-advisory', $args );
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

	        /**
	         * Register any meta fields that need to be exposed to the REST API
	         *
	         * @access public
	         * @since  1.0
	         * @return void
	         */
	        public function register_meta_fields() {
            	$tmp = register_meta( 'post', '_advisory_expires_time', array( 'type' => 'string', 'description' => __( 'The time the advisory should expire' ), 'single' => true, 'show_in_rest' => true ) );
            	error_log( '[Alerts API debug]: Registered the expires time meta field with a result of ' . print_r( $tmp, true ) );

            	if ( ! $this->is_alerts )
            		return;

            	$tmp = register_meta( 'post', '_advisory_permalink', array( 'type' => 'string', 'description' => __( 'The original location of this advisory' ), 'single' => true, 'sanitize_callback' => 'esc_url', 'show_in_rest' => true ) );
		        error_log( '[Alerts API debug]: Registered the permalink meta field with a result of ' . print_r( $tmp, true ) );
            	$tmp = register_meta( 'post', '_advisory_author', array( 'type' => 'string', 'description' => __( 'The name of the advisory author' ), 'single' => true, 'show_in_rest' => true ) );
		        error_log( '[Alerts API debug]: Registered the author meta field with a result of ' . print_r( $tmp, true ) );

		        return;
	        }

	        /**
	         * Make sure that the appropriate custom fields are not blocked from the REST API
	         * @param bool $protected whether the field is supposed to be blocked
	         * @param string $key the meta field key to check
	         *
	         * @access public
	         * @since  1.0
	         * @return bool
	         */
	        public function unprotect_meta( $protected, $key ) {
            	if ( ! in_array( $key, array( '_advisory_expires_time', '_advisory_permalink', '_advisory_author' ) ) )
            		return $protected;

            	return false;
	        }

	        /**
	         * Attempt to update/add post meta when an advisory is syndicated
	         * @param \WP_Post         $post     Inserted or updated post object.
	         * @param \WP_REST_Request $request  Request object.
	         * @param bool             $creating True when creating a post, false when updating.
	         *
	         * @return void
	         */
	        public function update_syndicated_meta( $post, $request, $creating=true ) {
	        	$params = $request->get_params();
	        	error_log( '[Alerts API Debug]: REST request params look like: ' . print_r( $params, true ) );
	        	$meta = $request->get_param( 'post_meta' );
	        	if ( empty( $meta ) ) {
			        $meta = $request->get_param( 'meta' );
		        }

		        if ( ! is_array( $meta ) && ! is_object( $meta ) ) {
	        		$meta = @json_decode( $meta );
		        }

		        if ( ! is_array( $meta ) && ! is_object( $meta ) ) {
	        		error_log( '[Alerts API Debug]: Could not get the meta information to be an array. It looks like: ' . print_r( $meta, true ) );
	        		return;
		        }

	        	error_log( '[Alerts API Debug]: Meta array looks like: ' . print_r( $meta, true ) );

	        	$keys = array( '_advisory_expires_time', '_advisory_permalink', '_advisory_author' );

	        	if ( is_object( $meta ) ) {
	        		foreach ( $keys as $key ) {
	        			if ( ! property_exists( $meta, $key ) )
	        				continue;

	        			if ( $creating ) {
					        add_post_meta( $post->ID, $key, $meta->{$key}, true );
				        } else {
					        update_post_meta( $post->ID, $key, $meta->{$key} );
				        }
			        }

			        return;
		        }

	        	foreach ( $meta as $k=>$v ) {
	        		if ( ! in_array( $k, $keys ) )
	        			continue;

	        		if ( $creating ) {
	        			error_log( '[Alerts API Debug]: Attempting to add ' . $v . ' as the value of ' . $k . ' for the post with an ID of ' . $post->ID );
	        			add_post_meta( $post->ID, $k, $v, true );
			        } else {
				        error_log( '[Alerts API Debug]: Attempting to update ' . $v . ' as the value of ' . $k . ' for the post with an ID of ' . $post->ID );
	        			update_post_meta( $post->ID, $k, $v );
			        }
		        }

		        return;
	        }
        }
    }
}
