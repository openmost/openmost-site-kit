<?php

function osk_add_post_type_charts_box() {

	$screens = array_values( get_post_types( array( 'public' => true ) ) );

	foreach ( $screens as $screen ) {
		add_meta_box(
			'osk_post_type_charts',
			__( 'Matomo - Visits Summary', 'osk' ),
			'osk_post_type_charts_box_content',
			$screen,
		);
	}
}

add_action( 'add_meta_boxes', 'osk_add_post_type_charts_box' );


function osk_post_type_charts_box_content() {
	require_once plugin_dir_path( __FILE__ ) . 'views/index.php';
}