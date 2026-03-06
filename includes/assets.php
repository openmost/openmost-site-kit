<?php
/**
 * Assets Management
 *
 * @package Openmost_Site_Kit
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue admin scripts and styles.
 *
 * @since 1.0.0
 * @param string $hook Current admin page hook.
 * @return void
 */
function omsk_admin_enqueue_scripts( $hook ) {
	// Load on our plugin pages, main dashboard, and post edit screens.
	$load_on_pages = array(
		'index.php',
		'post.php',
		'post-new.php',
	);

	$should_load = in_array( $hook, $load_on_pages, true )
		|| false !== strpos( $hook, 'openmost-site-kit' )
		|| false !== strpos( $hook, 'omsk-' );

	if ( ! $should_load ) {
		return;
	}

	$asset_file_path = plugin_dir_path( __DIR__ ) . 'build/index.asset.php';

	if ( ! file_exists( $asset_file_path ) ) {
		return;
	}

	$asset_file = include $asset_file_path;

	wp_enqueue_style(
		'omsk-app',
		plugins_url( 'build/index.css', __DIR__ ),
		array( 'wp-components' ),
		$asset_file['version']
	);

	wp_enqueue_script(
		'omsk-app',
		plugins_url( 'build/index.js', __DIR__ ),
		$asset_file['dependencies'],
		$asset_file['version'],
		true
	);

	// Pass user capabilities and config to JavaScript.
	wp_localize_script(
		'omsk-app',
		'openmostSiteKit',
		array(
			'canManageOptions' => current_user_can( 'manage_options' ),
			'canEditPosts'     => current_user_can( 'edit_posts' ),
			'restUrl'          => esc_url_raw( rest_url( 'openmost-site-kit/v1/' ) ),
			'nonce'            => wp_create_nonce( 'wp_rest' ),
		)
	);

	wp_set_script_translations( 'omsk-app', 'openmost-site-kit' );
}
add_action( 'admin_enqueue_scripts', 'omsk_admin_enqueue_scripts' );