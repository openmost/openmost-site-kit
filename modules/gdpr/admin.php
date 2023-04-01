<?php

function osk_register_gdpr_options_page() {
	add_submenu_page(
		'openmost-site-kit',
		'GDPR',
		'GDPR',
		'manage_options',
		'openmost-site-kit-gdpr',
		'osk_view_gdpr',
	);
}

add_action( 'admin_menu', 'osk_register_gdpr_options_page' );


function osk_view_gdpr() {
	require_once plugin_dir_path(__FILE__) . 'views/index.php';
}
