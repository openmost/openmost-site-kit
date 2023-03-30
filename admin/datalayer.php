<?php

function osk_data_layer_options_page() {
	add_submenu_page(
		'openmost-site-kit', // parent slug
		'Data Layer Settings', // page title
		'Data Layer', // menu title
		'manage_options', // capability
		'osk-data-layer-settings', // menu slug
		'osk_view_datalayer' // callback function to display the options form
	);
}

add_action( 'admin_menu', 'osk_data_layer_options_page' );

function osk_view_datalayer() {
	require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'views/datalayer.php';
}


function osk_register_data_layer_settings() {
	add_settings_section(
		'osk-data-layer-settings-section', // section ID
		'Data Layer Settings', // section title
		'osk_data_layer_settings_section_callback', // callback function to display the section description
		'osk-data-layer-settings' // page slug
	);

	add_settings_field(
		'osk-enable-page-field', // field ID
		'Enable Page Informations', // field label
		'osk_enable_page_field_callback', // callback function to display the field input
		'osk-data-layer-settings', // page slug
		'osk-data-layer-settings-section' // section ID
	);

	add_settings_field(
		'osk-enable-user-field', // field ID
		'Enable User Informations', // field label
		'osk_enable_user_field_callback', // callback function to display the field input
		'osk-data-layer-settings', // page slug
		'osk-data-layer-settings-section' // section ID
	);

	add_settings_field(
		'osk-enable-search-field', // field ID
		'Enable Search Informations', // field label
		'osk_enable_search_field_callback', // callback function to display the field input
		'osk-data-layer-settings', // page slug
		'osk-data-layer-settings-section' // section ID
	);

	add_settings_field(
		'osk-enable-pagination-field', // field ID
		'Enable Pagination Informations', // field label
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
	echo '<p>Configure your OSK data layer settings:</p>';
}

function osk_enable_page_field_callback() {
	$options = get_option( 'osk-data-layer-settings' );
	$value   = isset( $options['osk-enable-page-field'] ) ? $options['osk-enable-page-field'] : '';
	echo '<label><input type="checkbox" name="osk-data-layer-settings[osk-enable-page-field]" value="1" ' . checked( 1, $value, false ) . '> Enable <code>_mtm.page</code></label>';
}

function osk_enable_user_field_callback() {
	$options = get_option( 'osk-data-layer-settings' );
	$value   = isset( $options['osk-enable-user-field'] ) ? $options['osk-enable-user-field'] : '';
	echo '<label><input type="checkbox" name="osk-data-layer-settings[osk-enable-user-field]" value="1" ' . checked( 1, $value, false ) . '> Enable <code>_mtm.user</code></label>';
}


function osk_enable_search_field_callback() {
	$options = get_option( 'osk-data-layer-settings' );
	$value   = isset( $options['osk-enable-search-field'] ) ? $options['osk-enable-search-field'] : '';
	echo '<label><input type="checkbox" name="osk-data-layer-settings[osk-enable-search-field]" value="1" ' . checked( 1, $value, false ) . '> Enable <code>_mtm.search</code></label>';
}

function osk_enable_pagination_field_callback() {
	$options = get_option( 'osk-data-layer-settings' );
	$value   = isset( $options['osk-enable-pagination-field'] ) ? $options['osk-enable-pagination-field'] : '';
	echo '<label><input type="checkbox" name="osk-data-layer-settings[osk-enable-pagination-field]" value="1" ' . checked( 1, $value, false ) . '> Enable <code>_mtm.pagination</code></label>';
}

function osk_activate_plugin() {
	// Set default values for the OSK data layer settings
	$default_settings = array(
		'osk-enable-page-field'       => 0,
		'osk-enable-user-field'       => 0,
		'osk-enable-search-field'     => 0,
		'osk-enable-pagination-field' => 0
	);

	add_option( 'osk-data-layer-settings', $default_settings );
}

register_activation_hook( __FILE__, 'osk_activate_plugin' );

function osk_uninstall_plugin() {
	// Delete the OSK data layer settings
	delete_option( 'osk-data-layer-settings' );
}

register_uninstall_hook( __FILE__, 'osk_uninstall_plugin' );