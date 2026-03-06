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
    $enable_server_tracking  = ! empty( $options['omsk-matomo-enable-server-tracking-field'] );
    $excluded_roles          = isset( $options['omsk-matomo-excluded-roles-field'] ) ? (array) $options['omsk-matomo-excluded-roles-field'] : array();
    $token_auth              = isset( $options['omsk-matomo-token-auth-field'] ) ? $options['omsk-matomo-token-auth-field'] : '';
    $enable_userid_tracking  = ! empty( $options['omsk-matomo-enable-userid-tracking-field'] );
    $enable_ai_bot_tracking  = ! empty( $options['omsk-matomo-enable-ai-bot-tracking-field'] );
    $enable_server_search    = ! empty( $options['omsk-matomo-enable-server-search-tracking-field'] );

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

    // Detect if the request is from an AI assistant bot.
    $is_ai_bot = omsk_is_ai_bot();

    // Handle bot requests: track AI bots if enabled, discard other bots.
    if ( omsk_is_bot() ) {
        if ( $is_ai_bot && $enable_ai_bot_tracking ) {
            omsk_track_ai_bot_request( $host, $id_site, $token_auth );
        }
        return;
    }

    // Skip trackPageView on search pages when server-side search tracking is enabled.
    // doTrackSiteSearch replaces doTrackPageView (avoids double tracking).
    if ( is_search() && $enable_server_search ) {
        return;
    }

    try {
        // Initialize the Matomo Tracker.
        $tracker = new MatomoTracker( absint( $id_site ), $host );

        // Configure common visitor attributes (visitor ID, IP, UA, referrer, User ID).
        omsk_configure_tracker( $tracker, $token_auth, $enable_userid_tracking );

        // Set the page URL.
        $tracker->setUrl( omsk_get_current_url() );

        // Set custom variables for WordPress context.
        $tracker->setCustomTrackingParameter( 'dimension1', omsk_get_wp_environment() );

        // Track the page view (async - non-blocking).
        $tracker->doTrackPageView( wp_get_document_title() );

    } catch ( Exception $e ) {
        // Log error but don't break the page.
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging.
            error_log( 'Matomo Server-Side Tracking Error: ' . $e->getMessage() );
        }
    }
}

/**
 * Generate a deterministic visitor ID for the current request.
 *
 * Creates a consistent 16-character hex ID based on IP + User-Agent so that
 * all server-side tracking calls within the same request (pageview, search,
 * ecommerce) are attributed to the same visitor in Matomo.
 *
 * @since 2.2.0
 * @return string 16-character hex visitor ID.
 */
function omsk_get_visitor_id() {
    static $visitor_id = null;

    if ( null !== $visitor_id ) {
        return $visitor_id;
    }

    $ip         = omsk_get_visitor_ip() ?: 'unknown';
    $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
        ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
        : 'unknown';

    // Use a hash of IP + User-Agent for a deterministic visitor ID.
    $visitor_id = substr( md5( $ip . $user_agent ), 0, 16 );

    return $visitor_id;
}

/**
 * Configure a MatomoTracker instance with common visitor attributes.
 *
 * Sets visitor ID, IP, User-Agent, Accept-Language, referrer, and User ID
 * so that all server-side tracking requests share the same visitor identity.
 *
 * @since 2.2.0
 * @param MatomoTracker $tracker              The tracker instance to configure.
 * @param string        $token_auth           Auth token for IP/visitor recognition.
 * @param bool          $enable_userid_tracking Whether User ID tracking is enabled.
 * @return void
 */
function omsk_configure_tracker( $tracker, $token_auth = '', $enable_userid_tracking = false ) {
    if ( $token_auth ) {
        $tracker->setTokenAuth( $token_auth );
    }

    // Disable cookies for server-side tracking (more privacy-friendly).
    $tracker->disableCookieSupport();

    // Set a deterministic visitor ID so all hits belong to the same visit.
    $tracker->setVisitorId( omsk_get_visitor_id() );

    // Set visitor IP (important for geolocation).
    $visitor_ip = omsk_get_visitor_ip();
    if ( $visitor_ip ) {
        $tracker->setIp( $visitor_ip );
    }

    // Set User-Agent.
    if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
        $tracker->setUserAgent( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) );
    }

    // Set Accept-Language.
    if ( ! empty( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
        $tracker->setBrowserLanguage( sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) );
    }

    // Set referrer if available.
    if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
        $tracker->setUrlReferrer( esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) );
    }

    // Set User ID if enabled and user is logged in.
    if ( $enable_userid_tracking && is_user_logged_in() ) {
        $user_id = omsk_get_hashed_user_id();
        if ( $user_id ) {
            $tracker->setUserId( $user_id );
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

            // Validate IP address (accept private/reserved IPs for local dev environments).
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

/**
 * Check if the request is from a known AI assistant bot.
 *
 * These are user-triggered AI assistants that Matomo 5.7+ can track
 * separately via the recMode=1 bot tracking feature.
 *
 * @since 2.3.0
 * @return bool True if the request is from an AI assistant bot.
 */
function omsk_is_ai_bot() {
    if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
        return false;
    }

    $user_agent = strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) );

    // AI assistant bot patterns (user-triggered AI agents).
    $ai_bot_patterns = array(
        'chatgpt-user',
        'gptbot',
        'perplexitybot',
        'perplexity-user',
        'claudebot',
        'claude-web',
        'anthropic-ai',
        'cohere-ai',
        'google-extended',
        'gemini',
        'meta-externalagent',
        'bytespider',
        'amazonbot',
        'youbot',
        'applebot-extended',
        'oai-searchbot',
    );

    foreach ( $ai_bot_patterns as $pattern ) {
        if ( false !== strpos( $user_agent, $pattern ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Track an AI bot request using Matomo's bot tracking mode (recMode=1).
 *
 * Sends only the parameters supported by Matomo's bot tracking API:
 * url, ua, source, http_status, pf_srv, bw_bytes, cdt.
 *
 * @since 2.3.0
 * @param string $host      Matomo host URL.
 * @param string $id_site   Site ID.
 * @param string $token_auth Auth token.
 * @return void
 */
function omsk_track_ai_bot_request( $host, $id_site, $token_auth ) {
    try {
        $tracker = new MatomoTracker( absint( $id_site ), $host );

        if ( $token_auth ) {
            $tracker->setTokenAuth( $token_auth );
        }

        $tracker->disableCookieSupport();

        // Set bot tracking mode (recMode=1: bot-only).
        $tracker->setCustomTrackingParameter( 'recMode', '1' );

        // Set the page URL.
        $tracker->setUrl( omsk_get_current_url() );

        // Set User-Agent (required for Matomo to identify the AI bot).
        if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
            $tracker->setUserAgent( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) );
        }

        // Set source label for identification in Matomo reports.
        $tracker->setCustomTrackingParameter( 'source', 'WordPress' );

        // Set HTTP status code (200 for normal page loads).
        $tracker->setCustomTrackingParameter( 'http_status', (string) http_response_code() );

        // Track the request.
        $tracker->doTrackPageView( wp_get_document_title() );

    } catch ( Exception $e ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging.
            error_log( 'Matomo AI Bot Tracking Error: ' . $e->getMessage() );
        }
    }
}
