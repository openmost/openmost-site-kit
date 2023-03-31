<?php

function osk_dashboard_page() {
	add_submenu_page(
		'openmost-site-kit', // parent slug
		'Dashboard', // page title
		'Dashboard', // menu title
		'manage_options', // capability
		'osk-matomo-dashboard', // menu slug
		'osk_view_dashboard' // callback function to display the options form
	);
}

add_action( 'admin_menu', 'osk_dashboard_page' );

function osk_view_dashboard() {
	require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'views/dashboard.php';
}


function osk_fetch_matomo_api() {
	$response = wp_remote_get( 'https://demo.ronan-hello.fr/index.php?module=API&format=JSON&idSite=1&period=day&date=2023-03-01,2023-03-30&method=API.get&filter_limit=100&format_metrics=1&expanded=1&token_auth=1d6096e4253139884a4853785e3d2288' );
	if ( is_array( $response ) && ! is_wp_error( $response ) ) {
		return $response['body'];
	}

	return false;
}