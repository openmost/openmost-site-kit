<?php


function get_matomo_host() {
	$options = get_option( 'osk-settings' );

	return $options['osk-matomo-host-field'] ?? '';
}

function get_matomo_idsite() {
	$options = get_option( 'osk-settings' );

	return $options['osk-matomo-idsite-field'] ?? '';
}

function get_matomo_idcontainer() {
	$options = get_option( 'osk-settings' );

	return $options['osk-matomo-idcontainer-field'] ?? '';
}

function get_matomo_token_auth() {
	$options = get_option( 'osk-settings' );

	return $options['osk-matomo-token-auth-field'] ?? '';
}

function fetch_matomo_api( $url ) {
	$host       = get_matomo_host();
	$idsite     = get_matomo_idsite();
	$token_auth = get_matomo_token_auth();
	$base_url   = "$host/index.php?module=API&format=JSON&idSite=$idsite&token_auth=$token_auth";

	$response = wp_remote_get( "$base_url$url" );
	$body     = wp_remote_retrieve_body( $response );

	return (array) json_decode( $body );
}