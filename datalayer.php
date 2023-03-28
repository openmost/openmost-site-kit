<?php


require_once MATOMOSITEKIT_PLUGIN_DIR . 'inc/archive.php';
require_once MATOMOSITEKIT_PLUGIN_DIR . 'inc/author.php';
require_once MATOMOSITEKIT_PLUGIN_DIR . 'inc/error.php';
require_once MATOMOSITEKIT_PLUGIN_DIR . 'inc/pagination.php';
require_once MATOMOSITEKIT_PLUGIN_DIR . 'inc/search.php';
require_once MATOMOSITEKIT_PLUGIN_DIR . 'inc/single-page.php';
require_once MATOMOSITEKIT_PLUGIN_DIR . 'inc/term.php';
require_once MATOMOSITEKIT_PLUGIN_DIR . 'inc/type.php';
require_once MATOMOSITEKIT_PLUGIN_DIR . 'inc/user.php';

add_action( 'wp_head', 'matomo_site_kit_init' );
function matomo_site_kit_init() {

	$dataLayer = array();

	if ( is_front_page() && is_home() ) {
		// Default homepage
		$dataLayer['page'] = msk_get_single_page_details();

	} elseif ( is_front_page() ) {
		// static homepage
		$dataLayer['page'] = msk_get_single_page_details();

	} elseif ( is_home() ) {
		// blog page
		$dataLayer['page'] = msk_get_single_page_details();
	}

	if ( is_single() ) {
		$dataLayer['page'] = msk_get_single_page_details();
	}

	if ( is_page() ) {
		$dataLayer['page'] = msk_get_single_page_details();
	}

	if ( is_attachment() ) {
		$dataLayer['page'] = msk_get_single_page_details();
	}

	if ( is_archive() && ! is_author() ) {
		$dataLayer['page'] = msk_get_archive_page_details();
	}

	if ( is_author() ) {
		$dataLayer['page'] = msk_get_author_page_details();
	}

	if ( is_search() ) {
		$dataLayer['page'] = msk_get_search_page_details();
	}

	if ( is_404() ) {
		$dataLayer['page'] = msk_get_error_page_details();
	}

	if ( is_user_logged_in() ) {
		$dataLayer['user'] = msk_get_user_details();
	}

	if ( get_search_query() ) {
		$dataLayer['search'] = msk_get_search_details();
	}

	if ( paginate_links() ) {
		$dataLayer['pagination'] = msk_get_pagination_details();
	}

	$html = '<script id="matomo-site-kit-datalayer">let_mtm=window._mtm=window._mtm||[];_mtm.push(' . json_encode( $dataLayer ) . ');</script>';

	echo $html;
}