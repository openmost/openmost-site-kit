<?php

function omsk_add_post_type_charts_box() {

	$screens = array_values( get_post_types( array( 'public' => true ) ) );

	foreach ( $screens as $screen ) {
		add_meta_box(
			'omsk_post_type_charts',
			__( 'Matomo - Visits Summary', 'openmost-site-kit' ),
			'omsk_post_type_charts_box_content',
			$screen
		);
	}
}

add_action( 'add_meta_boxes', 'omsk_add_post_type_charts_box' );


function omsk_post_type_charts_box_content() {
	global $post;
	$post_id = $post->ID;

	// Check if Matomo is configured
	$host = omsk_get_matomo_host();
	$idSite = omsk_get_matomo_idsite();
	$tokenAuth = omsk_get_matomo_token_auth();

	if (!$host || !$idSite || !$tokenAuth) {
		echo '<div class="notice notice-warning inline"><p>';
		echo __('Matomo is not configured. Please configure your Matomo instance in the Site Kit settings.', 'openmost-site-kit');
		echo '</p></div>';
		return;
	}

	// Render React root with post ID as data attribute
	echo '<div id="omsk-post-analytics-root-' . esc_attr($post_id) . '" data-post-id="' . esc_attr($post_id) . '"></div>';
}