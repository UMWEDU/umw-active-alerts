<?php
function cptui_register_my_cpts() {

	/**
	 * Post Type: Advisories.
	 */

	$labels = array(
		"name" => __( "Advisories", "twentyseventeen" ),
		"singular_name" => __( "Advisory", "twentyseventeen" ),
	);

	$args = array(
		"label" => __( "Advisories", "twentyseventeen" ),
		"labels" => $labels,
		"description" => "These are community-wide announcements and updates that need to be prominently featured, but are not urgent.",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => true,
		"rest_base" => "",
		"has_archive" => false,
		"show_in_menu" => true,
		"exclude_from_search" => false,
		"capability_type" => "page",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"rewrite" => array( "slug" => "advisory", "with_front" => false ),
		"query_var" => true,
		"menu_position" => 5,
		"menu_icon" => "dashicons-welcome-view-site",
		"supports" => array( "title", "editor", "excerpt", "author" ),
	);

	register_post_type( "advisory", $args );

	/**
	 * Post Type: Emergency Alerts.
	 */

	$labels = array(
		"name" => __( "Emergency Alerts", "twentyseventeen" ),
		"singular_name" => __( "Emergency Alert", "twentyseventeen" ),
	);

	$args = array(
		"label" => __( "Emergency Alerts", "twentyseventeen" ),
		"labels" => $labels,
		"description" => "These are community-wide, urgent messages that need to be broadcast across the entire UMW website",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => true,
		"rest_base" => "",
		"has_archive" => false,
		"show_in_menu" => true,
		"exclude_from_search" => false,
		"capability_type" => "page",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"rewrite" => array( "slug" => "alert", "with_front" => false ),
		"query_var" => true,
		"menu_position" => 5,
		"menu_icon" => "dashicons-welcome-comments",
		"supports" => array( "title", "editor", "excerpt", "author" ),
	);

	register_post_type( "alert", $args );

	/**
	 * Post Type: External Advisories.
	 */

	$labels = array(
		"name" => __( "External Advisories", "twentyseventeen" ),
		"singular_name" => __( "External Advisory", "twentyseventeen" ),
	);

	$args = array(
		"label" => __( "External Advisories", "twentyseventeen" ),
		"labels" => $labels,
		"description" => "These are advisories that have been syndicated *into* this site from other sites within the UMW system. These advisories should never be published/modified directly within the Advisories website; instead, they should be published/modified from the external source where they should originate.",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => true,
		"rest_base" => "",
		"has_archive" => false,
		"show_in_menu" => true,
		"exclude_from_search" => false,
		"capability_type" => "page",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"rewrite" => array( "slug" => "external_advisory", "with_front" => false ),
		"query_var" => true,
		"menu_position" => 10,
		"menu_icon" => "dashicons-admin-comments",
		"supports" => array( "title", "editor" ),
	);

	register_post_type( "external_advisory", $args );
}

add_action( 'init', 'cptui_register_my_cpts' );
