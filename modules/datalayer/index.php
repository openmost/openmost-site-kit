<?php

if ( is_admin() ) {
	require_once plugin_dir_path( __FILE__ ) . 'admin.php';
}

// Features
require_once plugin_dir_path( __FILE__ ) . 'inc/archive.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/author.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/error.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/matomo.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/pagination.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/search.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/single-page.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/term.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/type.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/user.php';

add_action( 'wp_head', 'matomo_site_kit_init', 15 );
function matomo_site_kit_init() {

	$options   = get_option( 'omsk-data-layer-settings' );
	$dataLayer = array();

	if ( is_front_page() && is_home() ) {
		// Default homepage
		$dataLayer['page'] = omsk_get_single_page_details();

	} elseif ( is_front_page() && isset( $options['omsk-enable-home-page-field'] ) ) {
		// static homepage
		$dataLayer['page'] = omsk_get_single_page_details();

	} elseif ( is_home() && isset( $options['omsk-enable-blog-page-field'] ) ) {
		// blog page
		$dataLayer['page'] = omsk_get_single_page_details();
	}

	if ( is_page() && isset( $options['omsk-enable-page-field'] ) ) {
		$dataLayer['page'] = omsk_get_single_page_details();
	}

	if ( is_single() && isset( $options['omsk-enable-single-page-field'] ) ) {
		$dataLayer['page'] = omsk_get_single_page_details();
	}

	if ( is_attachment() && isset( $options['omsk-enable-attachment-page-field'] ) ) {
		$dataLayer['page'] = omsk_get_single_page_details();
	}

	if ( ( is_archive() && ! is_author() ) && isset( $options['omsk-enable-archive-page-field'] ) ) {
		$dataLayer['page'] = omsk_get_archive_page_details();
	}

	if ( is_author() && isset( $options['omsk-enable-author-page-field'] ) ) {
		$dataLayer['page'] = omsk_get_author_page_details();
	}

	if ( is_search() && isset( $options['omsk-enable-search-page-field'] ) ) {
		$dataLayer['page'] = omsk_get_search_page_details();
	}

	if ( is_404() && isset( $options['omsk-enable-error-page-field'] ) ) {
		$dataLayer['page'] = omsk_get_error_page_details();
	}

	if ( is_user_logged_in() && isset( $options['omsk-enable-user-field'] ) ) {
		$dataLayer['user'] = omsk_get_user_details();
	}

	if ( get_search_query() && isset( $options['omsk-enable-search-field'] ) ) {
		$dataLayer['search'] = omsk_get_search_details();
	}

	if ( paginate_links() && isset( $options['omsk-enable-pagination-field'] ) ) {
		$dataLayer['pagination'] = omsk_get_pagination_details();
	}

	$dataLayer['matomo'] = omsk_get_matomo_details();

	if ( ! empty( $dataLayer ) ) {
		$html = '<script id="matomo-site-kit-datalayer">window._mtm=window._mtm||[];_mtm.push(' . json_encode( $dataLayer ) . ')</script>';
		echo wp_kses( $html, array( 'script' => array( 'id' => array() ) ) );
	}

	$dataLayerSync = '<script id="matomo-site-kit-datalayer-sync">window.dataLayer=window.dataLayer||[];let syncDataLayer=function(array, callback){array.push=function(e){Array.prototype.push.call(array,e);callback(array);};};syncDataLayer(window.dataLayer, function(e){window._mtm.push(dataLayer.at(-1))});</script>';
	echo wp_kses( $dataLayerSync, array( 'script' => array( 'id' => array() ) ) );
}