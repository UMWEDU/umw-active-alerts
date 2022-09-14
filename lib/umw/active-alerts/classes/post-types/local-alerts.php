<?php

namespace {
	if ( ! defined( 'ABSPATH' ) ) {
		die( 'You do not have permission to access this file directly.' );
	}
}

namespace UMW\Active_Alerts\Post_Types {
	if ( ! class_exists( 'Local_Alerts' ) ) {
		class Local_Alerts extends Base {
			/**
			 * @var Local_Alerts $instance holds the single instance of this class
			 * @access private
			 */
			private static Local_Alerts $instance;

			/**
			 * Construct our object and register it
			 */
			public function __construct() {
				$this->register_post_type();
			}

			/**
			 * Returns the instance of this class.
			 *
			 * @access  public
			 * @return  Local_Alerts
			 * @since   0.1
			 */
			public static function instance(): Local_Alerts {
				if ( ! isset( self::$instance ) ) {
					$className      = __CLASS__;
					self::$instance = new $className;
				}

				return self::$instance;
			}

			/**
			 * Retrieve the string used as the slug for this post type
			 *
			 * @access protected
			 * @return string
			 * @since  0.1
			 */
			protected function get_handle(): string {
				return 'advisory';
			}

			/**
			 * Retrieve the list of labels for this post type
			 *
			 * @access protected
			 * @return array
			 * @since  0.1
			 */
			protected function get_labels(): array {
				return array(
					'name'          => __( 'Advisories', 'umw-active-alerts' ),
					'singular_name' => __( 'Advisory', 'umw-active-alerts' ),
				);
			}

			/**
			 * Retrieve the full list of post type arguments
			 *
			 * @access protected
			 * @return array
			 * @since  0.1
			 */
			protected function get_args(): array {
				return array(
					'label'               => __( 'Advisories', 'umw-active-alerts' ),
					'labels'              => $this->get_labels(),
					'description'         => '',
					'public'              => true,
					'publicly_queryable'  => true,
					'show_ui'             => true,
					'show_in_rest'        => true,
					'rest_base'           => '',
					'has_archive'         => false,
					'show_in_menu'        => true,
					'exclude_from_search' => false,
					'capability_type'     => 'post',
					'map_meta_cap'        => true,
					'hierarchical'        => false,
					'rewrite'             => array( 'slug' => 'advisory', 'with_front' => false ),
					'query_var'           => true,
					'menu_position'       => 20,
					'menu_icon'           => 'dashicons-admin-comments',
					'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'custom-fields' ),
				);
			}
		}
	}
}