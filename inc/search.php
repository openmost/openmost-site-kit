<?php


function msk_get_search_query_details() {

	global $wp_query;

	return array(
		// Matomo default
		'search'       => get_search_query(),
		'search_cat'   => '',
		'search_count' => $wp_query->found_posts,

		// Wordpress default
		'query'        => get_search_query(),
		'found_posts'  => $wp_query->found_posts,
		'post_count'   => $wp_query->post_count,
	);
}

function msk_get_search_page_details() {

	global $wp_query;

	return array(
		'type'           => 'search',
		'posts_per_page' => $wp_query->query_vars['posts_per_page'],
		'post_count'     => $wp_query->post_count,
		'paged'          => is_paged(),
		'page_number'    => get_query_var( 'paged' ) ?? 1,
		'max_num_pages'  => $wp_query->max_num_pages,
	);
}