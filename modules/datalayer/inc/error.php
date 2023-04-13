<?php

function osk_get_error_page_details() {

	global $wp;

	return array(
		'type'             => 'error',
		'title'            => wp_get_document_title(),
		'url'              => home_url( add_query_arg( array(), $wp->request ) ),
		'path'             => add_query_arg( array(), $wp->request ),
		'locale'           => get_locale(),
		'error_type'       => '404',
		'http_status_code' => 404,
	);
}