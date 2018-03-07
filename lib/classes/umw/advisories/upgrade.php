<?php
namespace {
	if ( ! defined( 'ABSPATH' ) ) {
		die( 'You do not have permission to access this file directly.' );
	}
}

namespace UMW\Advisories {
	if ( ! class_exists( 'Upgrade' ) ) {
		class Upgrade {
			/**
			 * @var \UMW\Advisories\Upgrade $instance holds the single instance of this class
			 * @access private
			 */
			private static $instance;

			/**
			 * @var bool a variable to determine whether we've performed init actions or not
			 * @access private
			 */
			private $did_init = false;

			/**
			 * Creates the \UMW\Advisories\Plugin object
			 *
			 * @access private
			 * @since  0.1
			 */
			private function __construct() {
				if ( is_network_admin() || ! is_admin() ) {
					return;
				}

				if ( ! current_user_can( 'delete_users' ) )
					return;

				$this->do_init();
			}

			/**
			 * Returns the instance of this class.
			 *
			 * @access  public
			 * @since   0.1
			 * @return  \UMW\Advisories\Upgrade
			 */
			public static function instance() {
				if ( ! isset( self::$instance ) ) {
					$className      = __CLASS__;
					self::$instance = new $className;
				}

				return self::$instance;
			}

			/**
			 * Run any startup actions
			 *
			 * @access public
			 * @since  1.0
			 * @return void
			 */
			public function do_init() {
				if ( true === $this->did_init )
					return;

				$this->did_init = true;

				$done = get_option( 'umw_advisories_version', false );
				if ( $done == Plugin::$version )
					return;

				$this->remove_old_version();
			}

			/**
			 * Attempt to remove all traces of the old Toolset way of managing this plugin
			 *
			 * @access private
			 * @since  2018.1
			 * @return bool
			 */
			private function remove_old_version() {
				$types = get_option( 'wpcf-custom-types', array() );

				if ( ! is_array( $types ) ) {
					Debug::log( '[Alerts Debug]: Did not find any post types that need to be removed' );
					return false;
				}

				if ( is_array( $types ) && array_key_exists( 'advisory', $types ) )
					$this->remove_types_types( $types );

				$this->remove_types_field_groups();

				return update_option( 'umw_advisories_version', Plugin::$version );
			}

			/**
			 * Remove any post types registered by the Types plugin that used to
			 *      be used by this plugin
			 *
			 * @access private
			 * @since  2018.1
			 * @return bool
			 */
			private function remove_types_types( $types=array() ) {
				if ( array_key_exists( 'advisory', $types ) )
					unset( $types['advisory'] );
				if ( array_key_exists( 'external-advisory', $types ) )
					unset( $types['external-advisory'] );
				if ( array_key_exists( 'alert', $types ) )
					unset( $types['alert'] );

				Debug::log( '[Alerts Debug]: Preparing to update the list of post types to ' . print_r( $types, true ) );
				update_option( 'wpcf-custom-types', $types );

				return true;
			}

			/**
			 * Identify and remove any custom post field groups that were implemented using
			 *      Types in an old version of this plugin
			 *
			 * @access private
			 * @since  2018.1
			 * @return bool
			 */
			private function remove_types_field_groups() {
				$groups = get_posts( array( 'post_type' => 'wp-types-group', 'numberposts' => -1 ) );
				Debug::log( '[Alerts Debug]: List of field groups looks like: ' . print_r( $groups, true ) );
				$fields_to_remove = array();

				foreach ( $groups as $group ) {
					$types = get_post_meta( $group->ID, '_wp_types_group_post_types', true );
					if ( ! is_array( $types ) )
						$types = explode( ',', $types );

					$types = array_filter( $types );
					$new_types = array();

					foreach ( $types as $type ) {
						if ( ! empty( $type ) && ! in_array( $type, array( 'advisory', 'external-advisory', 'alert' ) ) ) {
							$new_types[] = $type;
						}
					}

					if ( empty( $new_types ) ) {
						$tmp = get_post_meta( $group->ID, '_wp_types_group_fields', true );
						if ( ! is_array( $tmp ) )
							$tmp = explode( ',', $tmp );

						$tmp = array_filter( $tmp );

						$fields_to_remove = $fields_to_remove + $tmp;
						Debug::log( '[Alerts Debug]: Preparing to delete the field group with an ID of ' . $group->ID );
						/*if ( defined( 'WPCF_INC_ABSPATH' ) ) {
							if ( ! function_exists( 'wpcf_admin_fields_delete_group' ) ) {
								require_once WPCF_INC_ABSPATH . '/fields.php';
							}
							wpcf_admin_fields_delete_group( intval( $group->ID ) );
						} else {*/
							wp_delete_post( $group->ID, true );
						/*}*/
					} else {
						Debug::log( '[Alerts Debug]: The field group with an ID of ' . $group->ID . ' still appears to be used on some types, so it will not be removed: ' . print_r( $new_types, true ) );
						update_post_meta( $group->ID, '_wp_types_group_post_types', implode( ',', $new_types ) );
					}
				}

				Debug::log( '[Alerts Debug]: Preparing to attempt to remove the following fields: ' . print_r( $fields_to_remove, true ) );
				$this->remove_types_fields( $fields_to_remove );

				return true;
			}

			/**
			 * Identify and remove any custom post fields that were implemented using
			 *      Types in an old version of this plugin
			 * @param $fields array the array of fields to be checked/removed
			 *
			 * @access private
			 * @since  2018.1
			 * @return bool
			 */
			private function remove_types_fields( $fields=array() ) {
				if ( empty( $fields ) )
					return false;

				$groups = get_posts( array( 'post_type' => 'wp-types-group', 'numberposts' => -1 ) );

				$fields_to_keep = array();

				foreach ( $groups as $group ) {
					$tmp = get_post_meta( $group->ID, '_wp_types_group_fields', true );
					if ( ! is_array( $tmp ) )
						$tmp = explode( ',', $tmp );

					$tmp = array_filter( $tmp );

					$fields_to_keep = $fields_to_keep + $tmp;
				}

				$fields_to_remove = array_diff( $fields, $fields_to_keep );

				Debug::log( '[Alerts Debug]: The list of fields to remove looks like: ' . print_r( $fields_to_remove, true ) );
				Debug::log( '[Alerts Debug]: The list of fields to keep looks like: '. print_r( $fields_to_keep, true ) );

				$all_fields = get_option( 'wpcf-fields', array() );

				Debug::log( '[Alerts Debug]: The list of all fields we retrieved from the DB looks like: ' . print_r( $all_fields, true ) );

				foreach ( $fields_to_remove as $field ) {
					if ( ! array_key_exists( $field, $all_fields ) )
						continue;

					unset( $all_fields[$field] );
				}

				Debug::log( '[Alerts Debug]: Preparing to update the list of Types Fields to ' . print_r( $all_fields, true ) );
				update_option( 'wpcf-fields', $all_fields );

				return true;
			}
		}
	}
}