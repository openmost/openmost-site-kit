<?php

function msk_get_archive_page_details() {

	global $wp;

	return array(
		'type'         => 'archive',
		'title'        => wp_get_document_title(),
		'url'          => home_url( add_query_arg( array(), $wp->request ) ),
		'path'         => add_query_arg( array(), $wp->request ),
		'archive_type' => msk_get_archive_type(),
		'taxonomy'     => msk_get_archive_taxonomy(),
		'post_type'    => msk_get_archive_post_type(),
		'date'         => msk_get_archive_date(),
	);
}


function msk_get_archive_type() {
	$object = get_queried_object();

	if ( $object instanceof WP_Term ) {
		return 'taxonomy';
	}

	if ( $object instanceof WP_Post_Type ) {
		return 'post_type';
	}

	if ( is_date() ) {
		return 'date';
	}

	return 'not supported';
}

function msk_get_archive_taxonomy() {
	$object = get_queried_object();

	if ( $object instanceof WP_Term ) {
		return $object->taxonomy;
	}

	return false;
}

function msk_get_archive_post_type() {
	$object = get_queried_object();

	if ( $object instanceof WP_Term ) {
		return $object->taxonomy;
	}

	if ( $object instanceof WP_Post_Type ) {
		return $object->name;
	}

	return false;
}


function msk_get_archive_date() {

	$date = array(
		'year'  => get_query_var( 'year' ) ?: '',
		'month' => get_query_var( 'monthnum' ) ?: '',
		'day'   => get_query_var( 'day' ) ?: '',
		'date'  => '',
	);


	return $date;
}