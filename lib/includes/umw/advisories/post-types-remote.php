<?php
function cptui_register_my_cpts() {

	/**
	 * Post Type: Local Advisories.
	 */

	$labels = array(
		'name' => __( 'Advisories', 'umw-active-alerts' ),
		'singular_name' => __( 'Advisory', 'umw-active-alerts' ),
	);

	$args = array(
		'label' => __( 'Advisories', 'umw-active-alerts' ),
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

add_action( 'init', 'cptui_register_my_cpts' );
