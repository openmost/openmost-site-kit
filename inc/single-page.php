<?php

function osk_get_single_page_details() {

	global $wp;
	$object = get_queried_object();

	return array(
		'type'              => get_post_type_object( get_post_type() )->name,
		'id'                => get_the_ID(),
		'url'               => home_url( add_query_arg( array(), $wp->request ) ),
		'path'              => add_query_arg( array(), $wp->request ) ?: '/',
		'title'             => wp_get_document_title(),
		'post_name'         => $object->post_name,
		'post_title'        => $object->post_title,
		'post_excerpt'      => $object->post_excerpt,
		'post_status'       => $object->post_status,
		'post_date'         => $object->post_date,
		'post_date_gmt'     => $object->post_date_gmt,
		'post_modified'     => $object->post_modified,
		'post_modified_gmt' => $object->post_modified_gmt,
		'post_type'         => osk_get_post_type_details( get_post_type() ),
		'guid'              => $object->guid,
		'post_mime_type'    => $object->post_mime_type ?: false,

		'comment_status' => $object->comment_status,
		'comment_count'  => $object->comment_count,

		'is_home'       => is_home(),
		'is_front_page' => is_front_page(),

		'author'        => osk_get_author_details(),
		'taxonomies'    => osk_get_terms_per_tax(),
		'page_template' => esc_html( get_page_template_slug() ),
	);
}