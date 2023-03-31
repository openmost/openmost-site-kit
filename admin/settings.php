<?php

function osk_register_settings_options_page() {
	add_submenu_page(
		'openmost-site-kit',
		'General Settings',
		'General Settings',
		'manage_options',
		'openmost-site-kit',
		'osk_view_settings',
		1
	);
}

add_action( 'admin_menu', 'osk_register_settings_options_page' );


function osk_view_settings() {
	require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'views/settings.php';
}

function osk_register_settings() {

	add_settings_section(
		'osk-settings-section', // section ID
		'General Settings', // section title
		'osk_settings_section_callback', // callback function to display the section description
		'osk-settings' // page slug
	);

	add_settings_field(
		'osk-matomo-host-field', // field ID
		'Host URL', // field label
		'osk_matomo_host_field_callback', // callback function to display the field input
		'osk-settings', // page slug
		'osk-settings-section' // section ID
	);

	add_settings_field(
		'osk-matomo-idsite-field', // field ID
		'ID Site', // field label
		'osk_matomo_idsite_field_callback', // callback function to display the field input
		'osk-settings', // page slug
		'osk-settings-section' // section ID
	);

	add_settings_field(
		'osk-matomo-idcontainer-field', // field ID
		'ID Container', // field label
		'osk_matomo_idcontainer_field_callback', // callback function to display the field input
		'osk-settings', // page slug
		'osk-settings-section' // section ID
	);

	add_settings_field(
		'osk-matomo-token-auth-field', // field ID
		'Token Auth', // field label
		'osk_matomo_token_auth_field_callback', // callback function to display the field input
		'osk-settings', // page slug
		'osk-settings-section' // section ID
	);

	add_settings_field(
		'osk-matomo-enable-classic-tracking-code-field', // field ID
		'Enable classic tracking code', // field label
		'osk_matomo_enable_classic_tracking_code_field_callback', // callback function to display the field input
		'osk-settings', // page slug
		'osk-settings-section' // section ID
	);

	add_settings_field(
		'osk-matomo-enable-mtm-tracking-code-field', // field ID
		'Enable Tag Manager tracking code', // field label
		'osk_matomo_enable_mtm_tracking_code_field_callback', // callback function to display the field input
		'osk-settings', // page slug
		'osk-settings-section' // section ID
	);

	register_setting(
		'osk-settings-group', // option group
		'osk-settings' // option name
	);
}

add_action( 'admin_init', 'osk_register_settings' );

function osk_settings_section_callback() {
	echo '<p>Configure your Matomo instance:</p>';
}

function osk_matomo_host_field_callback() {
	$options = get_option( 'osk-settings' );
	$value   = isset( $options['osk-matomo-host-field'] ) ? $options['osk-matomo-host-field'] : '';
	echo '<input type="url" name="osk-settings[osk-matomo-host-field]" value="' . esc_attr( $value ) . '" class="regular-text" required>';
}

function osk_matomo_idsite_field_callback() {
	$options = get_option( 'osk-settings' );
	$value   = isset( $options['osk-matomo-idsite-field'] ) ? $options['osk-matomo-idsite-field'] : '';
	echo '<input type="number" name="osk-settings[osk-matomo-idsite-field]" value="' . esc_attr( $value ) . '" class="regular-text" min="1" step="1" required>';
}

function osk_matomo_idcontainer_field_callback() {
	$options = get_option( 'osk-settings' );
	$value   = isset( $options['osk-matomo-idcontainer-field'] ) ? $options['osk-matomo-idcontainer-field'] : '';
	echo '<input type="text" name="osk-settings[osk-matomo-idcontainer-field]" value="' . esc_attr( $value ) . '" class="regular-text" min="1" step="1" required>';
}

function osk_matomo_token_auth_field_callback() {
	$options = get_option( 'osk-settings' );
	$value   = isset( $options['osk-matomo-token-auth-field'] ) ? $options['osk-matomo-token-auth-field'] : '';
	echo '<input type="text" name="osk-settings[osk-matomo-token-auth-field]" value="' . esc_attr( $value ) . '" class="regular-text">';
}

function osk_matomo_enable_classic_tracking_code_field_callback() {
	$options = get_option( 'osk-settings' );
	$value   = isset( $options['osk-matomo-enable-classic-tracking-code-field'] ) ? $options['osk-matomo-enable-classic-tracking-code-field'] : '';
	echo '<label><input type="checkbox" name="osk-settings[osk-matomo-enable-classic-tracking-code-field]" value="1" ' . checked( 1, $value, false ) . '> Enable classic tracking</label>';
}


function osk_matomo_enable_mtm_tracking_code_field_callback() {
	$options = get_option( 'osk-settings' );
	$value   = isset( $options['osk-matomo-enable-mtm-tracking-code-field'] ) ? $options['osk-matomo-enable-mtm-tracking-code-field'] : '';
	echo '<label><input type="checkbox" name="osk-settings[osk-matomo-enable-mtm-tracking-code-field]" value="1" ' . checked( 1, $value, false ) . '> Enable Tag Manager tracking</label>';
}


/**
 * Display errors
 *
 * @return void
 */
function osk_both_code_deployed_notice() {
	$options = get_option( 'osk-settings' );
	if ( isset( $options['osk-matomo-enable-classic-tracking-code-field'] ) && isset( $options['osk-matomo-enable-mtm-tracking-code-field'] ) ) {
		echo '<div class="notice notice-error"><p>Both Matomo and Matomo Tag Manager codes are deployed, you should use only one of them.</p></div>';
	}
}

add_action( 'admin_notices', 'osk_both_code_deployed_notice' );