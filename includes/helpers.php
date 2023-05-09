<?php


function omsk_get_matomo_host() {
	$options = get_option( 'omsk-settings' );

	return sanitize_text_field( $options['omsk-matomo-host-field'] ) ?? '';
}

function omsk_get_matomo_idsite() {
	$options = get_option( 'omsk-settings' );

	return sanitize_text_field( $options['omsk-matomo-idsite-field'] ) ?? '';
}

function omsk_get_matomo_idcontainer() {
	$options = get_option( 'omsk-settings' );

	return sanitize_text_field( $options['omsk-matomo-idcontainer-field'] ) ?? '';
}

function omsk_get_matomo_token_auth() {
	$options = get_option( 'omsk-settings' );

	return sanitize_text_field( $options['omsk-matomo-token-auth-field'] ) ?? '';
}

function omsk_get_matomo_period() {
	return isset( $_GET['period'] ) ? esc_html( $_GET['period'] ) : 'day';
}

function omsk_get_matomo_date() {
	return isset( $_GET['date'] ) ? esc_html( $_GET['date'] ) : 'last7';
}

function omsk_fetch_matomo_api( $url ) {
	$host       = omsk_get_matomo_host();
	$idsite     = omsk_get_matomo_idsite();
	$token_auth = omsk_get_matomo_token_auth();

	$base_url = "$host/index.php?module=API&format=JSON&idSite=$idsite&token_auth=$token_auth";

	$response = wp_remote_get( sanitize_url( "$base_url$url" ) );
	$body     = wp_remote_retrieve_body( $response );

	return (array) json_decode( $body );
}