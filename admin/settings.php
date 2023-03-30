<?php

function osk_register_settings_options_page() {
	add_menu_page(
		'General Settings',
		'Site Kit',
		'manage_options',
		'openmost-site-kit',
		'osk_view_settings',
		'data:image/svg+xml;base64,' . base64_encode( '<svg width="20" height="12" viewBox="0 0 20 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M13.4725 4.75549L13.4751 4.75549C13.9732 4.75549 14.4508 4.55764 14.803 4.20549C15.1552 3.85334 15.353 3.37573 15.353 2.87774L15.3457 2.87774C15.3457 2.50617 15.2354 2.14295 15.0289 1.83405C14.8223 1.52516 14.5288 1.28447 14.1853 1.14249C13.8419 1.0005 13.4641 0.963589 13.0996 1.03643C12.7352 1.10928 12.4006 1.2886 12.1382 1.55169C11.8757 1.81478 11.6973 2.14981 11.6253 2.51436C11.5534 2.87891 11.5913 3.2566 11.7342 3.59962C11.877 3.94264 12.1185 4.23558 12.4279 4.44134C12.7374 4.6471 13.1009 4.75644 13.4725 4.75549ZM13.4751 5.75549C12.9056 5.75694 12.3484 5.58939 11.8742 5.27406C11.4 4.95873 11.03 4.5098 10.811 3.98409C10.5921 3.45838 10.534 2.87953 10.6443 2.32082C10.7545 1.76211 11.028 1.24866 11.4302 0.845466C11.8324 0.442273 12.3452 0.167467 12.9036 0.0558341C13.4621 -0.0557988 14.0411 0.000761434 14.5674 0.218355C15.0937 0.435948 15.5436 0.804791 15.8602 1.27819C16.1767 1.75159 16.3457 2.30826 16.3457 2.87774H16.353C16.353 3.64097 16.0498 4.37293 15.5101 4.91262C14.9704 5.4523 14.2384 5.75549 13.4751 5.75549Z" fill="black"/><path fill-rule="evenodd" clip-rule="evenodd" d="M4.75585 8.40656C4.75585 8.03519 4.64572 7.67215 4.43938 7.36336C4.23304 7.05457 3.93975 6.81388 3.5966 6.67175C3.25344 6.52962 2.87584 6.49243 2.51155 6.56489C2.14726 6.63735 1.81264 6.8162 1.55001 7.07881C1.28738 7.34142 1.10854 7.676 1.03608 8.04024C0.963627 8.40447 1.00081 8.78202 1.14294 9.12512C1.28507 9.46823 1.52576 9.7615 1.83458 9.96783C2.14341 10.1742 2.50649 10.2843 2.87793 10.2843C3.376 10.2843 3.85367 10.0865 4.20584 9.73431C4.55801 9.38216 4.75585 8.90455 4.75585 8.40656ZM5.27083 6.80777C5.58706 7.28101 5.75585 7.83739 5.75585 8.40656C5.75585 9.16979 5.45264 9.90175 4.91293 10.4414C4.37321 10.9811 3.6412 11.2843 2.87793 11.2843C2.30873 11.2843 1.75231 11.1155 1.27904 10.7993C0.805764 10.4831 0.436894 10.0337 0.21907 9.50783C0.00124694 8.98199 -0.0557456 8.40337 0.0552998 7.84514C0.166345 7.28691 0.440441 6.77414 0.842926 6.37168C1.24541 5.96922 1.75821 5.69514 2.31647 5.58411C2.87473 5.47307 3.45339 5.53006 3.97926 5.74787C4.50513 5.96568 4.9546 6.33452 5.27083 6.80777Z" fill="black"/><path d="M13.475 5.75532C12.9979 5.75569 12.5283 5.63728 12.1085 5.41077C11.6886 5.18426 11.3318 4.85678 11.0702 4.45789L8.87517 1.37041C8.61852 0.951713 8.25874 0.60586 7.83024 0.365926C7.40173 0.125992 6.91883 0 6.42771 0C5.9366 0 5.4537 0.125992 5.02519 0.365926C4.59668 0.60586 4.2369 0.951713 3.98026 1.37041L0.492601 6.79423C0.762868 6.39385 1.12966 6.068 1.55911 5.84678C1.98856 5.62556 2.46683 5.51609 2.94975 5.52848C3.43268 5.54088 3.9047 5.67473 4.32224 5.91769C4.73977 6.16065 5.08937 6.50489 5.33874 6.91861L7.62156 10.111C7.88809 10.4753 8.23681 10.7717 8.63939 10.976C9.04198 11.1803 9.48707 11.2868 9.93854 11.2868C10.39 11.2868 10.8351 11.1803 11.2377 10.976C11.6403 10.7717 11.989 10.4753 12.2555 10.111L12.2775 10.0744L12.4433 9.8305L15.8578 4.50423C15.593 4.89145 15.2373 5.20793 14.8219 5.42602C14.4066 5.64411 13.9441 5.75717 13.475 5.75532Z" fill="black"/><path d="M15.9685 1.45352C16.215 1.88642 16.3456 2.37706 16.3456 2.87758H16.3529C16.3529 3.64081 16.0497 4.37278 15.51 4.91246C14.9703 5.45214 14.2382 5.75532 13.475 5.75532C12.9055 5.75676 12.3483 5.58923 11.8741 5.2739C11.6717 5.13935 11.4883 4.98046 11.3276 4.80166L14.6944 9.96954L14.7359 10.0354L14.7554 10.0622C15.1835 10.6809 15.837 11.1073 16.5757 11.2502C17.3145 11.393 18.0798 11.2408 18.7077 10.8263C19.3356 10.4117 19.7762 9.76778 19.935 9.03236C20.0939 8.29695 19.9584 7.52856 19.5576 6.89181L19.5479 6.89913L19.4967 6.81865C19.4872 6.8083 19.479 6.79683 19.4723 6.78451L15.9685 1.45352Z" fill="black"/></svg>' ),
		2
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