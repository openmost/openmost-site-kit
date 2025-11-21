<?php

if ( is_admin() ) {
	require_once plugin_dir_path( __FILE__ ) . 'admin.php';
}

/**
 * Register opt-out shortcodes
 * Usage: [matomo_opt_out] or [omsk_matomo_opt_out] (legacy)
 */
add_shortcode( 'matomo_opt_out', 'omsk_matomo_opt_out_shortcode' );
add_shortcode( 'omsk_matomo_opt_out', 'omsk_matomo_opt_out_shortcode' ); // Backward compatibility

function omsk_matomo_opt_out_shortcode( $params ) {
	$host = omsk_get_matomo_host();

	if ( ! $host ) {
		return '<p>' . __( 'Matomo is not configured.', 'openmost-site-kit' ) . '</p>';
	}

	// Parse shortcode attributes
	$atts = shortcode_atts( array(
		'language'   => 'auto',
		'show_intro' => '1',
		'width'      => '100%',
		'height'     => '200px',
	), $params );

	$language   = esc_attr( $atts['language'] );
	$show_intro = esc_attr( $atts['show_intro'] );

	// Generate unique ID for multiple opt-out forms on same page
	$unique_id = 'matomo-opt-out-' . uniqid();

	// Build opt-out script URL
	$script_url = add_query_arg(
		array(
			'module'     => 'CoreAdminHome',
			'action'     => 'optOutJS',
			'divId'      => $unique_id,
			'language'   => $language,
			'showIntro'  => $show_intro,
		),
		trailingslashit( $host ) . 'index.php'
	);

	$html = sprintf(
		'<div id="%s" style="width: %s; min-height: %s;"></div>',
		esc_attr( $unique_id ),
		esc_attr( $atts['width'] ),
		esc_attr( $atts['height'] )
	);
	$html .= sprintf(
		'<script src="%s"></script>',
		esc_url( $script_url )
	);

	return $html;
}