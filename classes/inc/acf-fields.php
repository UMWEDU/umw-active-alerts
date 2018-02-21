<?php
if( function_exists('acf_add_local_field_group') ):

	acf_add_local_field_group(array(
		'key' => 'group_5a71d775061ac',
		'title' => 'Advisory Expiration',
		'fields' => array(
			array(
				'key' => 'field_5a71d791bceef',
				'label' => 'When should this advisory expire?',
				'name' => '_advisory_expires_time',
				'type' => 'date_time_picker',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'display_format' => 'F j, Y g:i a',
				'return_format' => 'Y-m-d H:i:s',
				'first_day' => 0,
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'advisory',
				),
			),
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'alert',
				),
			),
		),
		'menu_order' => 0,
		'position' => 'normal',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => array(
			0 => 'custom_fields',
			1 => 'discussion',
			2 => 'comments',
			3 => 'format',
			4 => 'page_attributes',
			5 => 'featured_image',
			6 => 'categories',
			7 => 'tags',
			8 => 'send-trackbacks',
		),
		'active' => 1,
		'description' => '',
	));

	acf_add_local_field_group(array(
		'key' => 'group_5a71d9ab71516',
		'title' => 'Advisory Information',
		'fields' => array(
			array(
				'key' => 'field_5a71da4225611',
				'label' => 'When should this advisory expire?',
				'name' => '_advisory_expires_time',
				'type' => 'date_time_picker',
				'instructions' => 'If you do not want this advisory to appear as an active advisory, set the expiration date in the past.',
				'required' => 1,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'display_format' => 'F j, Y g:i a',
				'return_format' => 'Y-m-d H:i:s',
				'first_day' => 0,
			),
			array(
				'key' => 'field_5a71da7525612',
				'label' => 'What is the original URL of this advisory?',
				'name' => '_advisory_permalink',
				'type' => 'url',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'default_value' => '',
				'placeholder' => '',
			),
			array(
				'key' => 'field_5a71da9125613',
				'label' => 'Who is the original author of this advisory?',
				'name' => '_advisory_author',
				'type' => 'text',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'default_value' => '',
				'placeholder' => '',
				'prepend' => '',
				'append' => '',
				'maxlength' => '',
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'external-advisory',
				),
			),
		),
		'menu_order' => 0,
		'position' => 'normal',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => array(
			0 => 'excerpt',
			1 => 'custom_fields',
			2 => 'discussion',
			3 => 'comments',
			4 => 'revisions',
			5 => 'author',
			6 => 'format',
			7 => 'page_attributes',
			8 => 'featured_image',
			9 => 'categories',
			10 => 'tags',
			11 => 'send-trackbacks',
		),
		'active' => 1,
		'description' => '',
	));

endif;