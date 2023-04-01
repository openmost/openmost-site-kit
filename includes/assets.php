<?php

add_action( 'admin_init', 'osk_admin_enqueue_scripts' );
function osk_admin_enqueue_scripts() {
	wp_enqueue_style( 'osk-app', plugins_url( '/admin/css/app.css', __DIR__ ), array(), OPENMOSTSITEKIT_VERSION );
	//wp_enqueue_script('osk-app', plugins_url( '/dist/js/app.js', __DIR__ ), array(), OPENMOSTSITEKIT_VERSION, true );
	wp_enqueue_script( 'osk-echarts', plugins_url( '/admin/js/echarts.js', __DIR__ ), array(), OPENMOSTSITEKIT_VERSION, true );
}