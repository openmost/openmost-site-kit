<?php

function msk_get_terms_per_tax() {
	$result     = array();
	$taxonomies = get_object_taxonomies( array( 'post_type' => get_post_type() ) );

	foreach ( $taxonomies as $taxonomy ) {
		$terms = wp_get_post_terms( get_the_ID(), $taxonomy );
		if ( count( $terms ) ) {

			$result[ $taxonomy ] = msk_get_taxonomy_details( $taxonomy );

			foreach ( $terms as $term ) {
				$result[ $taxonomy ]['terms'][ $term->name ] = msk_get_term_details( $term );
			}

			// Get main term with Yoast SEO
			if ( class_exists( 'WPSEO_Primary_Term' ) ) {
				$yoast_primary_term                  = new WPSEO_Primary_Term( $taxonomy, get_the_id() );
				$term                                = get_term( $yoast_primary_term->get_primary_term() );
				$result[ $taxonomy ]['primary_term'] = $term ? msk_get_term_details( $term ) : false;
			}
		}
	}

	return $result;
}

function msk_get_parent_term( $parent_id, $taxonomy_name ) {
	if ( $parent_id === 0 ) {
		return 0;
	}

	$term = get_term( $parent_id, $taxonomy_name );

	return msk_get_term_details( $term );
}


function msk_get_taxonomy_details( $taxonomy ) {
	$tax = get_taxonomy( $taxonomy );

	return array(
		'name'        => $tax->name,
		'label'       => $tax->label,
		'description' => $tax->description,
		'object_type' => $tax->object_type,
		'terms'       => array()
	);
}

function msk_get_term_details( $term ) {
	return array(
		'term_id'          => $term->term_id,
		'slug'             => $term->slug,
		'name'             => $term->name,
		'term_group'       => $term->term_group,
		'term_taxonomy_id' => $term->term_taxonomy_id,
		'taxonomy'         => $term->taxonomy,
		'description'      => $term->description,
		'parent'           => msk_get_parent_term( $term->parent, $term->taxonomy ),
		'count'            => $term->count,
		'filter'           => $term->filter,
	);
}