<?php

require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/datalayer/inc/archive.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/datalayer/inc/author.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/datalayer/inc/error.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/datalayer/inc/pagination.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/datalayer/inc/search.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/datalayer/inc/single-page.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/datalayer/inc/term.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/datalayer/inc/type.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/datalayer/inc/user.php';

add_action( 'wp_head', 'matomo_site_kit_init', 15 );
function matomo_site_kit_init() {

	$options   = get_option( 'osk-data-layer-settings' );
	$dataLayer = array();

	if ( is_front_page() && is_home() ) {
		// Default homepage
		$dataLayer['page'] = osk_get_single_page_details();

	} elseif ( is_front_page() && isset( $options['osk-enable-home-page-field'] ) ) {
		// static homepage
		$dataLayer['page'] = osk_get_single_page_details();

	} elseif ( is_home()&& isset( $options['osk-enable-blog-page-field'] ) ) {
		// blog page
		$dataLayer['page'] = osk_get_single_page_details();
	}

	if ( is_page() && isset( $options['osk-enable-page-field'] ) ) {
		$dataLayer['page'] = osk_get_single_page_details();
	}

	if ( is_single() && isset( $options['osk-enable-single-page-field'] ) ) {
		$dataLayer['page'] = osk_get_single_page_details();
	}

	if ( is_attachment() && isset( $options['osk-enable-attachment-page-field'] ) ) {
		$dataLayer['page'] = osk_get_single_page_details();
	}

	if ( ( is_archive() && ! is_author() ) && isset( $options['osk-enable-archive-page-field'] ) ) {
		$dataLayer['page'] = osk_get_archive_page_details();
	}

	if ( is_author() && isset( $options['osk-enable-author-page-field'] ) ) {
		$dataLayer['page'] = osk_get_author_page_details();
	}

	if ( is_search() && isset( $options['osk-enable-search-page-field'] ) ) {
		$dataLayer['page'] = osk_get_search_page_details();
	}

	if ( is_404() && isset( $options['osk-enable-error-page-field'] ) ) {
		$dataLayer['page'] = osk_get_error_page_details();
	}

	if ( is_user_logged_in() && isset( $options['osk-enable-user-field'] ) ) {
		$dataLayer['user'] = osk_get_user_details();
	}

	if ( get_search_query() && isset( $options['osk-enable-search-field'] ) ) {
		$dataLayer['search'] = osk_get_search_details();
	}

	if ( paginate_links() && isset( $options['osk-enable-pagination-field'] ) ) {
		$dataLayer['pagination'] = osk_get_pagination_details();
	}

	if ( ! empty( $dataLayer ) ) {
		$html = '<script id="matomo-site-kit-datalayer">window._mtm=window._mtm||[];_mtm.push(' . json_encode( $dataLayer ) . ');console.log(_mtm)</script>';
		echo $html;
	}
}