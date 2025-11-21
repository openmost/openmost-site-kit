<?php

function omsk_register_settings_options_page() {
	add_submenu_page(
		'openmost-site-kit',
		__( 'Settings', 'openmost-site-kit' ),
		__( 'Settings', 'openmost-site-kit' ),
		'manage_options',
		'omsk-settings',
		'omsk_view_settings',
		50
	);
}

add_action( 'admin_menu', 'omsk_register_settings_options_page' );


function omsk_view_settings() {
	echo '<div class="wrap"><div id="omsk-settings-root"></div></div>';
}

// Legacy settings fields removed - now handled by React
// Settings are managed via REST API (see includes/rest-api.php)
// React component handles validation and display (see src/pages/Settings)
