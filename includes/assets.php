<?php

add_action( 'admin_enqueue_scripts', 'omsk_admin_enqueue_scripts' );
function omsk_admin_enqueue_scripts( $hook ) {
	// Load on our plugin pages, main dashboard, and post edit screens
	$load_on_pages = array(
		'index.php',      // Main WordPress dashboard
		'post.php',       // Post edit screen
		'post-new.php',   // New post screen
	);

	$should_load = in_array( $hook, $load_on_pages, true )
		|| strpos( $hook, 'openmost-site-kit' ) !== false
		|| strpos( $hook, 'omsk-' ) !== false;

	if ( ! $should_load ) {
		return;
	}

	$asset_file = include( plugin_dir_path( __DIR__ ) . 'build/index.asset.php' );

	wp_enqueue_style(
		'omsk-app',
		plugins_url( '/build/index.css', __DIR__ ),
		array( 'wp-components' ),
		$asset_file['version']
	);

	wp_enqueue_script(
		'omsk-app',
		plugins_url( '/build/index.js', __DIR__ ),
		$asset_file['dependencies'],
		$asset_file['version'],
		true
	);

	// Pass user capabilities and config to JavaScript
	wp_localize_script(
		'omsk-app',
		'openmostSiteKit',
		array(
			'canManageOptions' => current_user_can( 'manage_options' ),
			'canEditPosts'     => current_user_can( 'edit_posts' ),
		)
	);

	wp_set_script_translations( 'omsk-app', 'openmost-site-kit' );
}