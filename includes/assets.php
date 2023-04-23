<?php

add_action( 'admin_enqueue_scripts', 'omsk_admin_enqueue_scripts' );
function omsk_admin_enqueue_scripts( $hook ) {

	wp_enqueue_style( 'omsk-app', plugins_url( '/admin/css/app.css', __DIR__ ), array(), OPENMOSTSITEKIT_VERSION );
	//wp_enqueue_script('omsk-app', plugins_url( '/dist/js/app.js', __DIR__ ), array(), OPENMOSTSITEKIT_VERSION, true );
	wp_enqueue_script( 'omsk-echarts', plugins_url( '/admin/js/echarts.js', __DIR__ ), array(), OPENMOSTSITEKIT_VERSION, true );
}