<?php

function omsk_data_layer_options_page() {
	add_submenu_page(
		'openmost-site-kit', // parent slug
		__( 'Data Layer Settings', 'openmost-site-kit' ), // page title
		__( 'Data Layer', 'openmost-site-kit' ), // menu title
		'manage_options', // capability
		'omsk-datalayer', // menu slug
		'omsk_view_datalayer', // callback function to display the options form
		30
	);
}

add_action( 'admin_menu', 'omsk_data_layer_options_page' );

function omsk_view_datalayer() {
	require_once plugin_dir_path( __FILE__ ) . 'views/index.php';
}

function omsk_register_data_layer_settings() {
	add_settings_section(
		'omsk-data-layer-settings-section', // section ID
		__( 'Data Layer Settings', 'openmost-site-kit' ), // section title
		'omsk_data_layer_settings_section_callback', // callback function to display the section description
		'omsk-data-layer-settings' // page slug
	);

	add_settings_field(
		'omsk-enable-home-page-field', // field ID
		__( 'Home page informations', 'openmost-site-kit' ), // field label
		'omsk_enable_home_page_field_callback', // callback function to display the field input
		'omsk-data-layer-settings', // page slug
		'omsk-data-layer-settings-section' // section ID
	);

	add_settings_field(
		'omsk-enable-blog-page-field', // field ID
		__( 'Blog page informations', 'openmost-site-kit' ), // field label
		'omsk_enable_blog_page_field_callback', // callback function to display the field input
		'omsk-data-layer-settings', // page slug
		'omsk-data-layer-settings-section' // section ID
	);

	add_settings_field(
		'omsk-enable-page-field', // field ID
		__( 'Page informations', 'openmost-site-kit' ), // field label
		'omsk_enable_page_field_callback', // callback function to display the field input
		'omsk-data-layer-settings', // page slug
		'omsk-data-layer-settings-section' // section ID
	);

	add_settings_field(
		'omsk-enable-single-page-field', // field ID
		__( 'Single page informations', 'openmost-site-kit' ), // field label
		'omsk_enable_single_page_field_callback', // callback function to display the field input
		'omsk-data-layer-settings', // page slug
		'omsk-data-layer-settings-section' // section ID
	);

	add_settings_field(
		'omsk-enable-attachment-page-field', // field ID
		__( 'Attachment page informations', 'openmost-site-kit' ), // field label
		'omsk_enable_attachment_page_field_callback', // callback function to display the field input
		'omsk-data-layer-settings', // page slug
		'omsk-data-layer-settings-section' // section ID
	);

	add_settings_field(
		'omsk-enable-archive-page-field', // field ID
		__( 'Archive page informations', 'openmost-site-kit' ), // field label
		'omsk_enable_archive_page_field_callback', // callback function to display the field input
		'omsk-data-layer-settings', // page slug
		'omsk-data-layer-settings-section' // section ID
	);

	add_settings_field(
		'omsk-enable-author-page-field', // field ID
		__( 'Author page informations', 'openmost-site-kit' ), // field label
		'omsk_enable_author_page_field_callback', // callback function to display the field input
		'omsk-data-layer-settings', // page slug
		'omsk-data-layer-settings-section' // section ID
	);

	add_settings_field(
		'omsk-enable-search-page-field', // field ID
		__( 'Search page informations', 'openmost-site-kit' ), // field label
		'omsk_enable_search_page_field_callback', // callback function to display the field input
		'omsk-data-layer-settings', // page slug
		'omsk-data-layer-settings-section' // section ID
	);

	add_settings_field(
		'omsk-enable-error-page-field', // field ID
		__( 'Error page informations', 'openmost-site-kit' ), // field label
		'omsk_enable_error_page_field_callback', // callback function to display the field input
		'omsk-data-layer-settings', // page slug
		'omsk-data-layer-settings-section' // section ID
	);


	add_settings_field(
		'omsk-enable-user-field', // field ID
		__( 'User informations', 'openmost-site-kit' ), // field label
		'omsk_enable_user_field_callback', // callback function to display the field input
		'omsk-data-layer-settings', // page slug
		'omsk-data-layer-settings-section' // section ID
	);

	add_settings_field(
		'omsk-enable-search-field', // field ID
		__( 'Search informations', 'openmost-site-kit' ), // field label
		'omsk_enable_search_field_callback', // callback function to display the field input
		'omsk-data-layer-settings', // page slug
		'omsk-data-layer-settings-section' // section ID
	);

	add_settings_field(
		'omsk-enable-pagination-field', // field ID
		__( 'Pagination informations', 'openmost-site-kit' ), // field label
		'omsk_enable_pagination_field_callback', // callback function to display the field input
		'omsk-data-layer-settings', // page slug
		'omsk-data-layer-settings-section' // section ID
	);

	register_setting(
		'omsk-data-layer-settings-group', // option group
		'omsk-data-layer-settings' // option name

	);
}

add_action( 'admin_init', 'omsk_register_data_layer_settings' );

function omsk_data_layer_settings_section_callback() {
	echo '<p>' . __( "Choose what you want to add to the dataLayer :", "openmost-site-kit" ) . '</p>';
}

function omsk_enable_home_page_field_callback() {
	$options = get_option( 'omsk-data-layer-settings' );
	$value   = isset( $options['omsk-enable-home-page-field'] ) ? $options['omsk-enable-home-page-field'] : '';
	echo '<label><input type="checkbox" name="omsk-data-layer-settings[omsk-enable-home-page-field]" value="1" ' . checked( 1, $value, false ) . '>' . __( "Add to", "openmost-site-kit" ) . '<code>_mtm.page</code></label>';
}

function omsk_enable_blog_page_field_callback() {
	$options = get_option( 'omsk-data-layer-settings' );
	$value   = isset( $options['omsk-enable-blog-page-field'] ) ? $options['omsk-enable-blog-page-field'] : '';
	echo '<label><input type="checkbox" name="omsk-data-layer-settings[omsk-enable-blog-page-field]" value="1" ' . checked( 1, $value, false ) . '>' . __( "Add to", "openmost-site-kit" ) . '<code>_mtm.page</code></label>';
}

function omsk_enable_page_field_callback() {
	$options = get_option( 'omsk-data-layer-settings' );
	$value   = isset( $options['omsk-enable-page-field'] ) ? $options['omsk-enable-page-field'] : '';
	echo '<label><input type="checkbox" name="omsk-data-layer-settings[omsk-enable-page-field]" value="1" ' . checked( 1, $value, false ) . '>' . __( "Add to", "openmost-site-kit" ) . '<code>_mtm.page</code></label>';
}

function omsk_enable_single_page_field_callback() {
	$options = get_option( 'omsk-data-layer-settings' );
	$value   = isset( $options['omsk-enable-single-page-field'] ) ? $options['omsk-enable-single-page-field'] : '';
	echo '<label><input type="checkbox" name="omsk-data-layer-settings[omsk-enable-single-page-field]" value="1" ' . checked( 1, $value, false ) . '>' . __( "Add to", "openmost-site-kit" ) . '<code>_mtm.page</code></label>';
}

function omsk_enable_attachment_page_field_callback() {
	$options = get_option( 'omsk-data-layer-settings' );
	$value   = isset( $options['omsk-enable-attachment-page-field'] ) ? $options['omsk-enable-attachment-page-field'] : '';
	echo '<label><input type="checkbox" name="omsk-data-layer-settings[omsk-enable-attachment-page-field]" value="1" ' . checked( 1, $value, false ) . '>' . __( "Add to", "openmost-site-kit" ) . '<code>_mtm.page</code></label>';
}

function omsk_enable_archive_page_field_callback() {
	$options = get_option( 'omsk-data-layer-settings' );
	$value   = isset( $options['omsk-enable-archive-page-field'] ) ? $options['omsk-enable-archive-page-field'] : '';
	echo '<label><input type="checkbox" name="omsk-data-layer-settings[omsk-enable-archive-page-field]" value="1" ' . checked( 1, $value, false ) . '>' . __( "Add to", "openmost-site-kit" ) . '<code>_mtm.page</code></label>';
}

function omsk_enable_author_page_field_callback() {
	$options = get_option( 'omsk-data-layer-settings' );
	$value   = isset( $options['omsk-enable-author-page-field'] ) ? $options['omsk-enable-author-page-field'] : '';
	echo '<label><input type="checkbox" name="omsk-data-layer-settings[omsk-enable-author-page-field]" value="1" ' . checked( 1, $value, false ) . '>' . __( "Add to", "openmost-site-kit" ) . '<code>_mtm.page</code></label>';
}

function omsk_enable_search_page_field_callback() {
	$options = get_option( 'omsk-data-layer-settings' );
	$value   = isset( $options['omsk-enable-search-page-field'] ) ? $options['omsk-enable-search-page-field'] : '';
	echo '<label><input type="checkbox" name="omsk-data-layer-settings[omsk-enable-search-page-field]" value="1" ' . checked( 1, $value, false ) . '>' . __( "Add to", "openmost-site-kit" ) . '<code>_mtm.page</code></label>';
}

function omsk_enable_error_page_field_callback() {
	$options = get_option( 'omsk-data-layer-settings' );
	$value   = isset( $options['omsk-enable-error-page-field'] ) ? $options['omsk-enable-error-page-field'] : '';
	echo '<label><input type="checkbox" name="omsk-data-layer-settings[omsk-enable-error-page-field]" value="1" ' . checked( 1, $value, false ) . '>' . __( "Add to", "openmost-site-kit" ) . '<code>_mtm.page</code></label>';
}


function omsk_enable_user_field_callback() {
	$options = get_option( 'omsk-data-layer-settings' );
	$value   = isset( $options['omsk-enable-user-field'] ) ? $options['omsk-enable-user-field'] : '';
	echo '<label><input type="checkbox" name="omsk-data-layer-settings[omsk-enable-user-field]" value="1" ' . checked( 1, $value, false ) . '>' . __( "Add to", "openmost-site-kit" ) . '<code>_mtm.user</code></label>';
}


function omsk_enable_search_field_callback() {
	$options = get_option( 'omsk-data-layer-settings' );
	$value   = isset( $options['omsk-enable-search-field'] ) ? $options['omsk-enable-search-field'] : '';
	echo '<label><input type="checkbox" name="omsk-data-layer-settings[omsk-enable-search-field]" value="1" ' . checked( 1, $value, false ) . '>' . __( "Add to", "openmost-site-kit" ) . '<code>_mtm.search</code></label>';
}

function omsk_enable_pagination_field_callback() {
	$options = get_option( 'omsk-data-layer-settings' );
	$value   = isset( $options['omsk-enable-pagination-field'] ) ? $options['omsk-enable-pagination-field'] : '';
	echo '<label><input type="checkbox" name="omsk-data-layer-settings[omsk-enable-pagination-field]" value="1" ' . checked( 1, $value, false ) . '>' . __( "Add to", "openmost-site-kit" ) . '<code>_mtm.pagination</code></label>';
}

function omsk_activate_plugin() {
	// Set default values for the omsk data layer settings
	$default_settings = array(
		'omsk-enable-page-field'                => 1,
		'omsk-enable-single-page-field'         => 1,
		'omsk-enable-attachment-page-field'     => 1,
		'omsk-enable-archive-page-field'        => 1,
		'omsk-enable-author-archive-page-field' => 1,
		'omsk-enable-search-page-field'         => 1,
		'omsk-enable-error-field'               => 1,
		'omsk-enable-user-field'                => 1,
		'omsk-enable-search-field'              => 1,
		'omsk-enable-pagination-field'          => 1
	);

	add_option( 'omsk-data-layer-settings', $default_settings );
}

register_activation_hook( __FILE__, 'omsk_activate_plugin' );

function omsk_uninstall_plugin() {
	// Delete the omsk data layer settings
	delete_option( 'omsk-data-layer-settings' );
}

register_uninstall_hook( __FILE__, 'omsk_uninstall_plugin' );
