<?php

function omsk_register_privacy_options_page() {
	add_submenu_page(
		'openmost-site-kit',
		__('Privacy', 'openmost-site-kit'),
		__('Privacy', 'openmost-site-kit'),
		'manage_options',
		'omsk-privacy',
		'omsk_view_privacy',
		40
	);
}

add_action( 'admin_menu', 'omsk_register_privacy_options_page' );


function omsk_view_privacy() {
	echo '<div class="wrap"><div id="omsk-privacy-root"></div></div>';
}
