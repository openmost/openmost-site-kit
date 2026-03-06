<?php
/**
 * Server-Side PHP Tracking Module
 *
 * Implements Matomo tracking using the PHP Tracker SDK.
 * - Works even when JavaScript is disabled
 * - Cannot be blocked by ad blockers
 * - More accurate tracking for certain scenarios
 * - Better privacy as no client-side cookies needed
 *
 * @package Openmost_Site_Kit
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include the Matomo PHP Tracker.
require_once __DIR__ . '/MatomoTracker.php';

// Hook into template_redirect for server-side tracking.
add_action( 'template_redirect', 'omsk_server_side_track_pageview' );

/**
 * Track a page view using server-side PHP.
 *
 * This hook fires after WordPress has determined which template to load,
 * but before the template is actually loaded - ideal for tracking.
 *
 * @since 1.0.0
 * @return void
 */
function omsk_server_side_track_pageview() {
    // Don't track admin pages, AJAX requests, or REST API calls.
    if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        return;
    }

    // Don't track if this is a preview.
    if ( is_preview() ) {
        return;
    }

    $options = get_option( 'omsk-settings', array() );

    if ( empty( $options ) ) {
        return;
    }

    $host                   = isset( $options['omsk-matomo-host-field'] ) ? $options['omsk-matomo-host-field'] : '';
    $id_site                = isset( $options['omsk-matomo-idsite-field'] ) ? $options['omsk-matomo-idsite-field'] : '';
    $enable_server_tracking = ! empty( $options['omsk-matomo-enable-server-tracking-field'] );
    $excluded_roles         = isset( $options['omsk-matomo-excluded-roles-field'] ) ? (array) $options['omsk-matomo-excluded-roles-field'] : array();
    $token_auth             = isset( $options['omsk-matomo-token-auth-field'] ) ? $options['omsk-matomo-token-auth-field'] : '';
    $enable_userid_tracking = ! empty( $options['omsk-matomo-enable-userid-tracking-field'] );

    // Check if server-side tracking is enabled.
    if ( ! $enable_server_tracking ) {
        return;
    }

    // Validate required settings.
    if ( empty( $host ) || empty( $id_site ) ) {
        return;
    }

    // Check if current user should be excluded from tracking.
    if ( omsk_should_exclude_user( $excluded_roles ) ) {
        return;
    }

    // Don't track bots.
    if ( omsk_is_bot() ) {
        return;
    }

    try {
        // Initialize the Matomo Tracker.
        $tracker = new MatomoTracker( absint( $id_site ), $host );

        // Set token auth for accurate IP tracking and visitor recognition.
        if ( $token_auth ) {
            $tracker->setTokenAuth( $token_auth );
        }

        // Disable cookies for server-side tracking (more privacy-friendly).
        $tracker->disableCookieSupport();

        // Set the page URL.
        $page_url = omsk_get_current_url();
        $tracker->setUrl( $page_url );

        // Set the page title.
        $page_title = wp_get_document_title();

        // Set referrer if available.
        if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
            $tracker->setUrlReferrer( esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) );
        }

        // Set User-Agent.
        if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
            $tracker->setUserAgent( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) );
        }

        // Set Accept-Language.
        if ( ! empty( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
            $tracker->setBrowserLanguage( sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) );
        }

        // Set visitor IP (important for geolocation).
        $visitor_ip = omsk_get_visitor_ip();
        if ( $visitor_ip ) {
            $tracker->setIp( $visitor_ip );
        }

        // Set User ID if enabled and user is logged in.
        if ( $enable_userid_tracking && is_user_logged_in() ) {
            $user_id = omsk_get_hashed_user_id();
            if ( $user_id ) {
                $tracker->setUserId( $user_id );
            }
        }

        // Set custom variables for WordPress context.
        $tracker->setCustomTrackingParameter( 'dimension1', omsk_get_wp_environment() );

        // Track the page view (async - non-blocking).
        $tracker->doTrackPageView( $page_title );

    } catch ( Exception $e ) {
        // Log error but don't break the page.
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging.
            error_log( 'Matomo Server-Side Tracking Error: ' . $e->getMessage() );
        }
    }
}

/**
 * Get the current page URL.
 *
 * @since 1.0.0
 * @return string Current URL.
 */
function omsk_get_current_url() {
    $protocol = is_ssl() ? 'https://' : 'http://';
    $host     = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
    $uri      = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

    return $protocol . $host . $uri;
}

/**
 * Get the visitor's real IP address.
 *
 * Handles common proxy headers.
 *
 * @since 1.0.0
 * @return string|null Visitor IP address.
 */
function omsk_get_visitor_ip() {
    $ip_headers = array(
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR',
    );

    foreach ( $ip_headers as $header ) {
        if ( ! empty( $_SERVER[ $header ] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );

            // X-Forwarded-For can contain multiple IPs, use the first one.
            if ( false !== strpos( $ip, ',' ) ) {
                $ips = explode( ',', $ip );
                $ip  = trim( $ips[0] );
            }

            // Validate IP address.
            if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
                return $ip;
            }

            // Also accept private IPs for local development.
            if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                return $ip;
            }
        }
    }

    return null;
}

/**
 * Check if the request is from a known bot.
 *
 * @since 1.0.0
 * @return bool True if the request is from a bot.
 */
function omsk_is_bot() {
    if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
        return true;
    }

    $user_agent = strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) );

    // Common bot patterns.
    $bot_patterns = array(
        'bot',
        'crawl',
        'spider',
        'slurp',
        'search',
        'fetch',
        'archive',
        'mediapartners',
        'adsbot',
        'googlebot',
        'bingbot',
        'yandexbot',
        'duckduckbot',
        'baiduspider',
        'facebookexternalhit',
        'twitterbot',
        'linkedinbot',
        'whatsapp',
        'telegram',
        'semrush',
        'ahrefsbot',
        'mj12bot',
        'dotbot',
        'petalbot',
        'uptimerobot',
        'pingdom',
        'gtmetrix',
        'pagespeed',
        'lighthouse',
        'headlesschrome',
        'phantomjs',
        'prerender',
        'wget',
        'curl',
        'python',
        'java/',
        'ruby/',
        'perl/',
        'php/',
    );

    foreach ( $bot_patterns as $pattern ) {
        if ( false !== strpos( $user_agent, $pattern ) ) {
            return true;
        }
    }

    return false;
}
