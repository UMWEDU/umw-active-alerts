<?php

namespace {
	if ( ! defined( 'ABSPATH' ) ) {
		die( 'You do not have permission to access this file directly.' );
	}
}

namespace UMW\Active_Alerts\Post_Types {
	if ( ! class_exists( 'Base' ) ) {
		abstract class Base {
			abstract protected function __construct();

			abstract protected function get_handle();

			abstract protected function get_labels();

			abstract protected function get_args();

			/**
			 * Registers the new post type
			 *
			 * @access public
			 * @return void
			 * @since  0.1
			 */
			public function register_post_type() {
				register_post_type( $this->get_handle(), $this->get_args() );
			}
		}
	}
}