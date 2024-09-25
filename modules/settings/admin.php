<?php

function omsk_register_settings_options_page() {
	add_submenu_page(
		'openmost-site-kit',
		__( 'General Settings', 'openmost-site-kit' ),
		__( 'General Settings', 'openmost-site-kit' ),
		'manage_options',
		'omsk-settings',
		'omsk_view_settings',
		50
	);
}

add_action( 'admin_menu', 'omsk_register_settings_options_page' );


function omsk_view_settings() {
	require_once plugin_dir_path( __FILE__ ) . 'views/index.php';
}

function omsk_register_settings() {

	add_settings_section(
		'omsk-settings-section', // section ID
		__( 'General Settings' ), // section title
		'omsk_settings_section_callback', // callback function to display the section description
		'omsk-settings' // page slug
	);

	add_settings_field(
		'omsk-matomo-host-field', // field ID
		__( 'Host URL', 'openmost-site-kit' ), // field label
		'omsk_matomo_host_field_callback', // callback function to display the field input
		'omsk-settings', // page slug
		'omsk-settings-section' // section ID
	);

	add_settings_field(
		'omsk-matomo-idsite-field', // field ID
		__( 'ID Site', 'openmost-site-kit' ), // field label
		'omsk_matomo_idsite_field_callback', // callback function to display the field input
		'omsk-settings', // page slug
		'omsk-settings-section' // section ID
	);

	add_settings_field(
		'omsk-matomo-idcontainer-field', // field ID
		__( 'ID Container', 'openmost-site-kit' ), // field label
		'omsk_matomo_idcontainer_field_callback', // callback function to display the field input
		'omsk-settings', // page slug
		'omsk-settings-section' // section ID
	);

	add_settings_field(
		'omsk-matomo-token-auth-field', // field ID
		__( 'Token Auth', 'openmost-site-kit' ), // field label
		'omsk_matomo_token_auth_field_callback', // callback function to display the field input
		'omsk-settings', // page slug
		'omsk-settings-section' // section ID
	);

	add_settings_field(
		'omsk-matomo-enable-classic-tracking-code-field', // field ID
		__( 'Enable classic tracking code', 'openmost-site-kit' ), // field label
		'omsk_matomo_enable_classic_tracking_code_field_callback', // callback function to display the field input
		'omsk-settings', // page slug
		'omsk-settings-section' // section ID
	);

	add_settings_field(
		'omsk-matomo-enable-mtm-tracking-code-field', // field ID
		__( 'Enable Tag Manager tracking code', 'openmost-site-kit' ), // field label
		'omsk_matomo_enable_mtm_tracking_code_field_callback', // callback function to display the field input
		'omsk-settings', // page slug
		'omsk-settings-section' // section ID
	);

	register_setting(
		'omsk-settings-group', // option group
		'omsk-settings' // option name
	);
}

add_action( 'admin_init', 'omsk_register_settings' );

function omsk_settings_section_callback() {
	echo '<p>' . _e( 'Configure your Matomo instance:', 'openmost-site-kit' ) . '</p>';
}

function omsk_matomo_host_field_callback() {
	$value = omsk_get_matomo_host();
	echo '<input type="url" name="omsk-settings[omsk-matomo-host-field]" value="' . esc_attr( $value ) . '" class="regular-text" required>';
}

function omsk_matomo_idsite_field_callback() {
	$value = omsk_get_matomo_idsite();
	echo '<input type="number" name="omsk-settings[omsk-matomo-idsite-field]" value="' . esc_attr( $value ) . '" class="regular-text" min="1" step="1" required>';
}

function omsk_matomo_idcontainer_field_callback() {
	$value = omsk_get_matomo_idcontainer();
	echo '<input type="text" name="omsk-settings[omsk-matomo-idcontainer-field]" value="' . esc_attr( $value ) . '" class="regular-text" min="1" step="1" required>';
}

function omsk_matomo_token_auth_field_callback() {
	$value = omsk_get_matomo_token_auth();
	echo '<input type="text" name="omsk-settings[omsk-matomo-token-auth-field]" value="' . esc_attr( $value ) . '" class="regular-text">';
}

function omsk_matomo_enable_classic_tracking_code_field_callback() {
	$options = get_option( 'omsk-settings' );
	$value   = isset( $options['omsk-matomo-enable-classic-tracking-code-field'] ) ? sanitize_text_field( $options['omsk-matomo-enable-classic-tracking-code-field'] ) : '';
	echo '<label><input type="checkbox" name="omsk-settings[omsk-matomo-enable-classic-tracking-code-field]" value="1" ' . checked( 1, $value, false ) . '>' . __( "Enable classic tracking (not recommended)", "omsk" ) . '</label>';
}

function omsk_matomo_enable_mtm_tracking_code_field_callback() {
	$options = get_option( 'omsk-settings' );
	$value   = isset( $options['omsk-matomo-enable-mtm-tracking-code-field'] ) ? sanitize_text_field( $options['omsk-matomo-enable-mtm-tracking-code-field'] ) : '';
	echo '<label><input type="checkbox" name="omsk-settings[omsk-matomo-enable-mtm-tracking-code-field]" value="1" ' . checked( 1, $value, false ) . '>' . __( "Enable Tag Manager tracking (recommended)", "omsk" ) . '</label>';
}


/**
 * Display errors
 *
 * @return void
 */
function omsk_both_code_deployed_notice() {
	$options = get_option( 'omsk-settings' );
	if ( isset( $options['omsk-matomo-enable-classic-tracking-code-field'] ) && isset( $options['omsk-matomo-enable-mtm-tracking-code-field'] ) ) {
		echo '<div class="notice notice-error"><p>' . __( "Both Matomo and Matomo Tag Manager codes are deployed, you should use only one of them.", "omsk" ) . '</p></div>';
	}
}

add_action( 'admin_notices', 'omsk_both_code_deployed_notice' );
