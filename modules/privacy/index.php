<?php

if ( is_admin() ) {
	require_once plugin_dir_path( __FILE__ ) . 'admin.php';
}

add_shortcode( 'osk_matomo_opt_out', 'osk_matomo_opt_out_shortcode' );
function osk_matomo_opt_out_shortcode( $params ) {

	$html       = '';
	$host       = osk_get_matomo_host();
	$language   = 'auto';
	$show_intro = '1';

	if ( isset( $params ) && isset( $params['language'] ) ) {
		$language = esc_attr( $params['language'] );
	}

	if ( isset( $params ) && isset( $params['show_intro'] ) ) {
		$show_intro = esc_attr( $params['show_intro'] );
	}

	if ( $host && $language && $show_intro ) {
		$html .= '<div id="matomo-opt-out"></div>';
		$html .= '<script src="' . $host . '/index.php?module=CoreAdminHome&action=optOutJS&divId=matomo-opt-out&language=' . $language . '&showIntro=' . $show_intro . '"></script>';
	}

	return $html;
}