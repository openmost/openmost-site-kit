<?php

function osk_data_layer_options_page() {
	add_submenu_page(
		'openmost-site-kit', // parent slug
		'Data Layer Settings', // page title
		'Data Layer', // menu title
		'manage_options', // capability
		'osk-datalayer', // menu slug
		'osk_view_datalayer', // callback function to display the options form
		30
	);
}

add_action( 'admin_menu', 'osk_data_layer_options_page' );

function osk_view_datalayer() {
	require_once plugin_dir_path( __FILE__ ) . 'views/index.php';
}

function osk_register_data_layer_settings() {
	add_settings_section(
		'osk-data-layer-settings-section', // section ID
		'Data Layer Settings', // section title
		'osk_data_layer_settings_section_callback', // callback function to display the section description
		'osk-data-layer-settings' // page slug
	);

	add_settings_field(
		'osk-enable-home-page-field', // field ID
		'Home page informations', // field label
		'osk_enable_home_page_field_callback', // callback function to display the field input
		'osk-data-layer-settings', // page slug
		'osk-data-layer-settings-section' // section ID
	);

	add_settings_field(
		'osk-enable-blog-page-field', // field ID
		'Blog page informations', // field label
		'osk_enable_blog_page_field_callback', // callback function to display the field input
		'osk-data-layer-settings', // page slug
		'osk-data-layer-settings-section' // section ID
	);

	add_settings_field(
		'osk-enable-page-field', // field ID
		'Page informations', // field label
		'osk_enable_page_field_callback', // callback function to display the field input
		'osk-data-layer-settings', // page slug
		'osk-data-layer-settings-section' // section ID
	);

	add_settings_field(
		'osk-enable-single-page-field', // field ID
		'Single page informations', // field label
		'osk_enable_single_page_field_callback', // callback function to display the field input
		'osk-data-layer-settings', // page slug
		'osk-data-layer-settings-section' // section ID
	);

	add_settings_field(
		'osk-enable-attachment-page-field', // field ID
		'Attachment page informations', // field label
		'osk_enable_attachment_page_field_callback', // callback function to display the field input
		'osk-data-layer-settings', // page slug
		'osk-data-layer-settings-section' // section ID
	);

	add_settings_field(
		'osk-enable-archive-page-field', // field ID
		'Archive page informations', // field label
		'osk_enable_archive_page_field_callback', // callback function to display the field input
		'osk-data-layer-settings', // page slug
		'osk-data-layer-settings-section' // section ID
	);

	add_settings_field(
		'osk-enable-author-page-field', // field ID
		'Author page informations', // field label
		'osk_enable_author_page_field_callback', // callback function to display the field input
		'osk-data-layer-settings', // page slug
		'osk-data-layer-settings-section' // section ID
	);

	add_settings_field(
		'osk-enable-search-page-field', // field ID
		'Search page informations', // field label
		'osk_enable_search_page_field_callback', // callback function to display the field input
		'osk-data-layer-settings', // page slug
		'osk-data-layer-settings-section' // section ID
	);

	add_settings_field(
		'osk-enable-error-page-field', // field ID
		'Error page informations', // field label
		'osk_enable_error_page_field_callback', // callback function to display the field input
		'osk-data-layer-settings', // page slug
		'osk-data-layer-settings-section' // section ID
	);


	add_settings_field(
		'osk-enable-user-field', // field ID
		'User informations', // field label
		'osk_enable_user_field_callback', // callback function to display the field input
		'osk-data-layer-settings', // page slug
		'osk-data-layer-settings-section' // section ID
	);

	add_settings_field(
		'osk-enable-search-field', // field ID
		'Search informations', // field label
		'osk_enable_search_field_callback', // callback function to display the field input
		'osk-data-layer-settings', // page slug
		'osk-data-layer-settings-section' // section ID
	);

	add_settings_field(
		'osk-enable-pagination-field', // field ID
		'Pagination informations', // field label
		'osk_enable_pagination_field_callback', // callback function to display the field input
		'osk-data-layer-settings', // page slug
		'osk-data-layer-settings-section' // section ID
	);

	register_setting(
		'osk-data-layer-settings-group', // option group
		'osk-data-layer-settings' // option name
	);
}

add_action( 'admin_init', 'osk_register_data_layer_settings' );

function osk_data_layer_settings_section_callback() {
	echo '<p>Choose what you want to add to the dataLayer:</p>';
}

function osk_enable_home_page_field_callback() {
	$options = get_option( 'osk-data-layer-settings' );
	$value   = isset( $options['osk-enable-home-page-field'] ) ? $options['osk-enable-home-page-field'] : '';
	echo '<label><input type="checkbox" name="osk-data-layer-settings[osk-enable-home-page-field]" value="1" ' . checked( 1, $value, false ) . '> Add to <code>_mtm.page</code></label>';
}

function osk_enable_blog_page_field_callback() {
	$options = get_option( 'osk-data-layer-settings' );
	$value   = isset( $options['osk-enable-blog-page-field'] ) ? $options['osk-enable-blog-page-field'] : '';
	echo '<label><input type="checkbox" name="osk-data-layer-settings[osk-enable-blog-page-field]" value="1" ' . checked( 1, $value, false ) . '> Add to <code>_mtm.page</code></label>';
}

function osk_enable_page_field_callback() {
	$options = get_option( 'osk-data-layer-settings' );
	$value   = isset( $options['osk-enable-page-field'] ) ? $options['osk-enable-page-field'] : '';
	echo '<label><input type="checkbox" name="osk-data-layer-settings[osk-enable-page-field]" value="1" ' . checked( 1, $value, false ) . '> Add to <code>_mtm.page</code></label>';
}

function osk_enable_single_page_field_callback() {
	$options = get_option( 'osk-data-layer-settings' );
	$value   = isset( $options['osk-enable-single-page-field'] ) ? $options['osk-enable-single-page-field'] : '';
	echo '<label><input type="checkbox" name="osk-data-layer-settings[osk-enable-single-page-field]" value="1" ' . checked( 1, $value, false ) . '> Add to <code>_mtm.page</code></label>';
}

function osk_enable_attachment_page_field_callback() {
	$options = get_option( 'osk-data-layer-settings' );
	$value   = isset( $options['osk-enable-attachment-page-field'] ) ? $options['osk-enable-attachment-page-field'] : '';
	echo '<label><input type="checkbox" name="osk-data-layer-settings[osk-enable-attachment-page-field]" value="1" ' . checked( 1, $value, false ) . '> Add to <code>_mtm.page</code></label>';
}

function osk_enable_archive_page_field_callback() {
	$options = get_option( 'osk-data-layer-settings' );
	$value   = isset( $options['osk-enable-archive-page-field'] ) ? $options['osk-enable-archive-page-field'] : '';
	echo '<label><input type="checkbox" name="osk-data-layer-settings[osk-enable-archive-page-field]" value="1" ' . checked( 1, $value, false ) . '> Add to <code>_mtm.page</code></label>';
}

function osk_enable_author_page_field_callback() {
	$options = get_option( 'osk-data-layer-settings' );
	$value   = isset( $options['osk-enable-author-page-field'] ) ? $options['osk-enable-author-page-field'] : '';
	echo '<label><input type="checkbox" name="osk-data-layer-settings[osk-enable-author-page-field]" value="1" ' . checked( 1, $value, false ) . '> Add to <code>_mtm.page</code></label>';
}

function osk_enable_search_page_field_callback() {
	$options = get_option( 'osk-data-layer-settings' );
	$value   = isset( $options['osk-enable-search-page-field'] ) ? $options['osk-enable-search-page-field'] : '';
	echo '<label><input type="checkbox" name="osk-data-layer-settings[osk-enable-search-page-field]" value="1" ' . checked( 1, $value, false ) . '> Add to <code>_mtm.page</code></label>';
}

function osk_enable_error_page_field_callback() {
	$options = get_option( 'osk-data-layer-settings' );
	$value   = isset( $options['osk-enable-error-page-field'] ) ? $options['osk-enable-error-page-field'] : '';
	echo '<label><input type="checkbox" name="osk-data-layer-settings[osk-enable-error-page-field]" value="1" ' . checked( 1, $value, false ) . '> Add to <code>_mtm.page</code></label>';
}


function osk_enable_user_field_callback() {
	$options = get_option( 'osk-data-layer-settings' );
	$value   = isset( $options['osk-enable-user-field'] ) ? $options['osk-enable-user-field'] : '';
	echo '<label><input type="checkbox" name="osk-data-layer-settings[osk-enable-user-field]" value="1" ' . checked( 1, $value, false ) . '> Add to <code>_mtm.user</code></label>';
}


function osk_enable_search_field_callback() {
	$options = get_option( 'osk-data-layer-settings' );
	$value   = isset( $options['osk-enable-search-field'] ) ? $options['osk-enable-search-field'] : '';
	echo '<label><input type="checkbox" name="osk-data-layer-settings[osk-enable-search-field]" value="1" ' . checked( 1, $value, false ) . '> Add to <code>_mtm.search</code></label>';
}

function osk_enable_pagination_field_callback() {
	$options = get_option( 'osk-data-layer-settings' );
	$value   = isset( $options['osk-enable-pagination-field'] ) ? $options['osk-enable-pagination-field'] : '';
	echo '<label><input type="checkbox" name="osk-data-layer-settings[osk-enable-pagination-field]" value="1" ' . checked( 1, $value, false ) . '> Add to <code>_mtm.pagination</code></label>';
}

function osk_activate_plugin() {
	// Set default values for the OSK data layer settings
	$default_settings = array(
		'osk-enable-page-field'                => 1,
		'osk-enable-single-page-field'         => 1,
		'osk-enable-attachment-page-field'     => 1,
		'osk-enable-archive-page-field'        => 1,
		'osk-enable-author-archive-page-field' => 1,
		'osk-enable-search-page-field'         => 1,
		'osk-enable-error-field'               => 1,
		'osk-enable-user-field'                => 1,
		'osk-enable-search-field'              => 1,
		'osk-enable-pagination-field'          => 1
	);

	add_option( 'osk-data-layer-settings', $default_settings );
}

register_activation_hook( __FILE__, 'osk_activate_plugin' );

function osk_uninstall_plugin() {
	// Delete the OSK data layer settings
	delete_option( 'osk-data-layer-settings' );
}

register_uninstall_hook( __FILE__, 'osk_uninstall_plugin' );