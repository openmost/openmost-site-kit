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
	require_once plugin_dir_path(__FILE__) . 'views/index.php';
}