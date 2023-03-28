<?php
/*
Plugin Name: Matomo Site Kit
Plugin URI: https://openmost.io/matomo-site-kit
Description: A site kit plugin for Matomo
Author: Openmost
Version: 1.0
Author URI: http://openmost.io
*/

// Make sure we don't expose any info if called directly
if ( ! function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'MATOMO_SITE_KIT_VERSION', '1.0' );
define( 'MATOMO_SITE_KIT__MINIMUM_WP_VERSION', '5.0' );
define( 'MATOMO_SITE_KIT__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once MATOMO_SITE_KIT__PLUGIN_DIR . 'inc/archive.php';
require_once MATOMO_SITE_KIT__PLUGIN_DIR . 'inc/author.php';
require_once MATOMO_SITE_KIT__PLUGIN_DIR . 'inc/error.php';
require_once MATOMO_SITE_KIT__PLUGIN_DIR . 'inc/search.php';
require_once MATOMO_SITE_KIT__PLUGIN_DIR . 'inc/single-page.php';
require_once MATOMO_SITE_KIT__PLUGIN_DIR . 'inc/term.php';
require_once MATOMO_SITE_KIT__PLUGIN_DIR . 'inc/type.php';
require_once MATOMO_SITE_KIT__PLUGIN_DIR . 'inc/user.php';


add_action( 'wp_head', 'matomo_site_kit_init' );
function matomo_site_kit_init() {
	$dataLayer = array();

	if ( is_front_page() && is_home() ) {
		// Default homepage
		var_dump( 'DEFAULT HOME' );

	} elseif ( is_front_page() ) {
		// static homepage
		var_dump( 'HOMEPAGE' );
	} elseif ( is_home() ) {
		// blog page
		var_dump( 'BLOG' );
	}

	if ( is_single() ) {
		var_dump( 'SINGLE', );
		$dataLayer['page'] = msk_get_single_page_details();
	}

	if ( is_page() ) {
		var_dump( 'PAGE', );
		$dataLayer['page'] = msk_get_single_page_details();
	}

	if ( is_archive() && ! is_author() ) {
		var_dump( 'ARCHIVE' );
	}

	if ( is_author() ) {
		var_dump( 'AUTHOR' );
		$dataLayer['page'] = msk_get_author_page_details();
	}

	if ( is_search() ) {
		var_dump( 'SEARCH' );
		$dataLayer['page'] = msk_get_search_page_details();
	}

	if ( is_404() ) {
		var_dump( 'ERROR 404' );
		$dataLayer['page'] = msk_get_error_page_details();
	}

	if ( is_attachment() ) {
		var_dump( 'ATTACHMENT' );
	}

	if ( is_user_logged_in() ) {
		$dataLayer['user'] = msk_get_user_details();
	}

	if(get_search_query()){
		var_dump( 'SEARCH QUERY' );
		$dataLayer['search'] = msk_get_search_query_details();
	}

	$html = '<script id="matomo-site-kit-datalayer">let_mtm=window._mtm=window._mtm||[];_mtm.push(' . json_encode( $dataLayer ) . ');</script>';

	echo $html;
}
