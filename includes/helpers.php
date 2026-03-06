<?php
/**
 * Helper Functions
 *
 * @package Openmost_Site_Kit
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get cached plugin options. Avoids repeated get_option calls.
 *
 * @return array Plugin options.
 */
function omsk_get_options() {
    static $options = null;

    if ( null === $options ) {
        $options = get_option( 'omsk-settings', array() );
    }

    return $options;
}

/**
 * Get Matomo host URL from settings.
 *
 * @return string Matomo host URL or empty string.
 */
function omsk_get_matomo_host() {
    $options = omsk_get_options();
    $value   = isset( $options['omsk-matomo-host-field'] ) ? $options['omsk-matomo-host-field'] : '';

    return esc_url_raw( $value );
}

/**
 * Get Matomo site ID from settings.
 *
 * @return int Matomo site ID or 0.
 */
function omsk_get_matomo_idsite() {
    $options = omsk_get_options();
    $value   = isset( $options['omsk-matomo-idsite-field'] ) ? $options['omsk-matomo-idsite-field'] : '';

    return absint( $value );
}

/**
 * Get Matomo container ID from settings.
 *
 * @return string Matomo container ID or empty string.
 */
function omsk_get_matomo_idcontainer() {
    $options = omsk_get_options();
    $value   = isset( $options['omsk-matomo-idcontainer-field'] ) ? $options['omsk-matomo-idcontainer-field'] : '';

    return sanitize_text_field( $value );
}

/**
 * Get Matomo auth token from settings.
 *
 * @return string Matomo auth token or empty string.
 */
function omsk_get_matomo_token_auth() {
    $options = omsk_get_options();
    $value   = isset( $options['omsk-matomo-token-auth-field'] ) ? $options['omsk-matomo-token-auth-field'] : '';

    return sanitize_text_field( $value );
}


/**
 * Fetch data from Matomo API.
 *
 * @param string $param_string Query parameters string.
 * @return array|WP_Error API response or error.
 */
function omsk_fetch_matomo_api( $param_string ) {
    $host       = omsk_get_matomo_host();
    $idsite     = omsk_get_matomo_idsite();
    $token_auth = omsk_get_matomo_token_auth();

    if ( empty( $host ) || empty( $idsite ) ) {
        return new WP_Error( 'missing_config', __( 'Matomo host or site ID not configured.', 'openmost-site-kit' ) );
    }

    // Parse parameter string into array.
    $params = array();
    parse_str( ltrim( $param_string, '&' ), $params );

    // Sanitize all parameters.
    $sanitized_params = array_map( 'sanitize_text_field', $params );

    // Build POST body with all parameters.
    $body_params = array_merge(
        array(
            'module'     => 'API',
            'format'     => 'JSON',
            'idSite'     => $idsite,
            'token_auth' => $token_auth,
        ),
        $sanitized_params
    );

    $request_url = trailingslashit( $host ) . 'index.php';

    // Make POST request with token in body AND as Bearer token.
    $response = wp_remote_post(
        $request_url,
        array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'Authorization' => 'Bearer ' . $token_auth,
            ),
            'body'    => $body_params,
        )
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        return new WP_Error( 'json_error', __( 'Invalid JSON response from Matomo.', 'openmost-site-kit' ) );
    }

    return $data;
}

/**
 * Get Matomo plan type based on host URL.
 *
 * @return string Plan type ('cloud' or 'on_premise').
 */
function omsk_get_matomo_plan() {
    $host = omsk_get_matomo_host();

    if ( ! empty( $host ) && strpos( $host, '.matomo.cloud' ) !== false ) {
        return 'cloud';
    }

    return 'on_premise';
}

/**
 * Get Matomo CDN host URL.
 *
 * @return string CDN host URL.
 */
function omsk_get_matomo_cdn_host() {
    $host = omsk_get_matomo_host();
    $plan = omsk_get_matomo_plan();

    if ( 'cloud' === $plan && ! empty( $host ) ) {
        return str_replace( 'https://', 'https://cdn.matomo.cloud/', $host );
    }

    return $host;
}