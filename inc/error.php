<?php

function msk_get_error_page_details() {

	$current_url = home_url( $_SERVER['REQUEST_URI'] );

	return array(
		'title'            => wp_get_document_title(),
		'url'              => $current_url,
		'type'             => 'error',
		'error_type'       => '404',
		'http_status_code' => 404,
	);
}