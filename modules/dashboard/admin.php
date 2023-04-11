<?php

function osk_dashboard_page() {
	add_submenu_page(
		'openmost-site-kit', // parent slug
		__('Dashboard', 'openmost-site-kit'), // page title
		__('Dashboard', 'openmost-site-kit'), // menu title
		'manage_options', // capability
		'openmost-site-kit', // menu slug
		'osk_view_dashboard', // callback function to display the options form
		1
	);
}

add_action( 'admin_menu', 'osk_dashboard_page' );

function osk_view_dashboard() {
	require_once plugin_dir_path( __FILE__ ) . 'views/index.php';
}

function osk_dashboard_notice() {
	if ( !osk_get_matomo_host() || !osk_get_matomo_idsite() || !osk_get_matomo_token_auth() ) {
		echo '<div class="notice notice-error"><p>' . ( "You should define a valid host, idsite and token_auth" ) . '</p></div>';
	}
}

add_action( 'admin_notices', 'osk_dashboard_notice' );