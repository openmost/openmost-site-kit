<?php


function osk_get_matomo_host() {
	$options = get_option( 'osk-settings' );

	return $options['osk-matomo-host-field'] ?? '';
}

function osk_get_matomo_idsite() {
	$options = get_option( 'osk-settings' );

	return $options['osk-matomo-idsite-field'] ?? '';
}

function osk_get_matomo_idcontainer() {
	$options = get_option( 'osk-settings' );

	return $options['osk-matomo-idcontainer-field'] ?? '';
}

function osk_get_matomo_token_auth() {
	$options = get_option( 'osk-settings' );

	return $options['osk-matomo-token-auth-field'] ?? '';
}

function osk_get_matomo_period() {
	return isset( $_GET['period'] ) ? $_GET['period'] : 'day';
}

function osk_fetch_matomo_api( $url ) {
	$host       = osk_get_matomo_host();
	$idsite     = osk_get_matomo_idsite();
	$token_auth = osk_get_matomo_token_auth();
	$period     = osk_get_matomo_period();
	$base_url   = "$host/index.php?module=API&format=JSON&idSite=$idsite&token_auth=$token_auth&period=$period";

	$response = wp_remote_get( "$base_url$url" );
	$body     = wp_remote_retrieve_body( $response );

	return (array) json_decode( $body );
}