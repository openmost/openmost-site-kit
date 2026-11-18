<?php
/**
 * AI chatbot tracking core.
 *
 * Shared by the plugin module, the wp-config.php drop-in (pages served by
 * advanced-cache.php) and the LiteSpeed ESI endpoint. The last two run before
 * WordPress is loaded, so nothing here may depend on WordPress functions.
 *
 * phpcs:disable WordPress.Security.ValidatedSanitizedInput, WordPress.WP.AlternativeFunctions
 *
 * @package Openmost_Site_Kit
 * @since 2.4.0
 */

/**
 * User-Agent substrings of AI chatbots, matched case-insensitively.
 *
 * Same list as the official Matomo PHP tracker plus Google-NotebookLM from
 * the Matomo AI chatbot FAQ.
 *
 * @since 2.4.0
 * @return string[]
 */
function omsk_get_ai_bot_user_agents() {
    $user_agents = array(
        'GPTBot',
        'ChatGPT-User',
        'MistralAI-User',
        'Gemini-Deep-Research',
        'Claude-User',
        'Perplexity-User',
        'GoogleAgent',
        'Google-NotebookLM',
        'Devin',
        'NovaAct',
        'Google-Extended',
        'Google-CloudVertexBot',
    );

    if ( function_exists( 'apply_filters' ) ) {
        /**
         * Filters the AI chatbot User-Agent substrings.
         *
         * Not applied on cached pages tracked by the drop-in or ESI, plugins are not loaded there.
         *
         * @since 2.4.0
         * @param string[] $user_agents User-Agent substrings.
         */
        $user_agents = (array) apply_filters( 'omsk_ai_bot_user_agents', $user_agents );
    }

    return $user_agents;
}

/**
 * Current request User-Agent, stripped of control characters.
 *
 * @since 2.4.0
 * @return string
 */
function omsk_get_request_user_agent() {
    if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
        return '';
    }

    return trim( (string) preg_replace( '/[\x00-\x1F\x7F]/', '', (string) $_SERVER['HTTP_USER_AGENT'] ) );
}

/**
 * @since 2.4.0
 * @param string $user_agent User-Agent to test.
 * @return bool
 */
function omsk_is_ai_bot_user_agent( $user_agent ) {
    if ( '' === (string) $user_agent ) {
        return false;
    }

    foreach ( omsk_get_ai_bot_user_agents() as $substring ) {
        if ( '' !== (string) $substring && false !== stripos( $user_agent, (string) $substring ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Set by the classic tracker only for AI agents that run JavaScript: they are
 * already recorded through recMode=2, the server-side request would double them.
 *
 * @since 2.4.0
 * @return bool
 */
function omsk_ai_bot_has_js_cookie() {
    return isset( $_COOKIE['matomo_has_js'] ) && '1' === $_COOKIE['matomo_has_js'];
}

/**
 * Resolve the tracking configuration from the plugin options.
 *
 * @since 2.4.0
 * @param mixed $options Value of the omsk-settings option.
 * @return array{host: string, id_site: int, esi: bool}|null Null when AI chatbot tracking must not run.
 */
function omsk_get_ai_bot_tracking_config( $options ) {
    if ( ! is_array( $options ) || empty( $options ) ) {
        return null;
    }

    if ( isset( $options['omsk-matomo-enable-ai-bot-tracking-field'] ) && ! $options['omsk-matomo-enable-ai-bot-tracking-field'] ) {
        return null;
    }

    $host    = isset( $options['omsk-matomo-host-field'] ) ? trim( (string) $options['omsk-matomo-host-field'] ) : '';
    $id_site = isset( $options['omsk-matomo-idsite-field'] ) ? abs( (int) $options['omsk-matomo-idsite-field'] ) : 0;

    if ( '' === $host || 0 === $id_site ) {
        return null;
    }

    $tracking_enabled = ! empty( $options['omsk-matomo-enable-classic-tracking-code-field'] )
        || ( ! empty( $options['omsk-matomo-enable-mtm-tracking-code-field'] ) && ! empty( $options['omsk-matomo-idcontainer-field'] ) )
        || ! empty( $options['omsk-matomo-enable-server-tracking-field'] );

    if ( ! $tracking_enabled ) {
        return null;
    }

    return array(
        'host'    => $host,
        'id_site' => $id_site,
        'esi'     => ! empty( $options['omsk-matomo-ai-bot-esi-field'] ) && omsk_ai_bot_esi_supported(),
    );
}

/**
 * Whether the web server can evaluate <esi:include>. Apache and nginx print it
 * as is, so a saved ESI setting would silently stop the tracking there.
 *
 * Define OMSK_AI_BOT_ESI in wp-config.php to force it, for example behind an
 * ESI-capable CDN (Fastly, Akamai) or to turn it off on LiteSpeed.
 *
 * @since 2.4.0
 * @return bool
 */
function omsk_ai_bot_esi_supported() {
    if ( defined( 'OMSK_AI_BOT_ESI' ) ) {
        return (bool) OMSK_AI_BOT_ESI;
    }

    return isset( $_SERVER['SERVER_SOFTWARE'] ) && false !== stripos( (string) $_SERVER['SERVER_SOFTWARE'], 'litespeed' );
}

/**
 * Web server name and version, for the Settings screen.
 *
 * @since 2.4.0
 * @return string
 */
function omsk_ai_bot_server_software() {
    return isset( $_SERVER['SERVER_SOFTWARE'] ) ? trim( (string) preg_replace( '/[^\w\s.\/()\-]/', '', (string) $_SERVER['SERVER_SOFTWARE'] ) ) : '';
}

/**
 * Current request URL built from the server variables.
 *
 * @since 2.4.0
 * @return string
 */
function omsk_ai_bot_current_url() {
    $https = ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== strtolower( (string) $_SERVER['HTTPS'] ) )
        || ( isset( $_SERVER['SERVER_PORT'] ) && '443' === (string) $_SERVER['SERVER_PORT'] )
        || ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === strtolower( (string) $_SERVER['HTTP_X_FORWARDED_PROTO'] ) );

    $host = isset( $_SERVER['HTTP_HOST'] ) ? preg_replace( '/[^A-Za-z0-9.\-:\[\]]/', '', (string) $_SERVER['HTTP_HOST'] ) : '';
    $uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';

    return ( $https ? 'https://' : 'http://' ) . $host . $uri;
}

/**
 * Milliseconds elapsed since the request started, or null when unknown.
 *
 * @since 2.4.0
 * @return int|null
 */
function omsk_ai_bot_elapsed_ms() {
    if ( ! isset( $_SERVER['REQUEST_TIME_FLOAT'] ) ) {
        return null;
    }

    return max( 0, (int) ( ( microtime( true ) - (float) $_SERVER['REQUEST_TIME_FLOAT'] ) * 1000 ) );
}

/**
 * Send the AI chatbot request to Matomo (recMode=1, bot-only), once per PHP request.
 *
 * No token_auth is sent: it is only needed to override the IP or to send a
 * timestamp older than 24 hours, and chatbot requests create no visit.
 *
 * @since 2.4.0
 * @param array    $config      Result of omsk_get_ai_bot_tracking_config().
 * @param string   $user_agent  Chatbot User-Agent.
 * @param string   $url         Page URL.
 * @param int      $http_status HTTP status of the page.
 * @param int|null $elapsed_ms  Server time in milliseconds, null when unknown.
 * @param string   $title       Page title, empty when unknown.
 * @return bool Whether a request was sent.
 */
function omsk_send_ai_bot_request( array $config, $user_agent, $url, $http_status = 200, $elapsed_ms = null, $title = '' ) {
    if ( ! empty( $GLOBALS['omsk_ai_bot_tracked'] ) ) {
        return false;
    }
    $GLOBALS['omsk_ai_bot_tracked'] = true;

    if ( ! class_exists( 'MatomoTracker' ) ) {
        require_once dirname( __DIR__ ) . '/server-tracking/MatomoTracker.php';
    }

    try {
        $tracker = new MatomoTracker( (int) $config['id_site'], (string) $config['host'] );
        $tracker->setRequestTimeout( 1 );
        $tracker->disableCookieSupport();
        $tracker->setUserAgent( (string) $user_agent );
        $tracker->setUrl( (string) $url );
        $tracker->setCustomTrackingParameter( 'recMode', '1' );
        $tracker->setCustomTrackingParameter( 'source', 'WordPress' );
        $tracker->setCustomTrackingParameter( 'http_status', (string) ( $http_status ? (int) $http_status : 200 ) );

        if ( null !== $elapsed_ms ) {
            $tracker->setCustomTrackingParameter( 'pf_srv', (string) (int) $elapsed_ms );
        }

        $tracker->doTrackPageView( (string) $title );

        return true;
    } catch ( Exception $e ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging.
            error_log( 'Matomo AI Bot Tracking Error: ' . $e->getMessage() );
        }

        return false;
    }
}

/**
 * The endpoint is public: only accept page URLs of this site.
 *
 * @since 2.4.0
 * @param string $url  URL received in the ESI request.
 * @param string $home Site home URL.
 * @return bool
 */
function omsk_ai_bot_is_own_url( $url, $home ) {
    $parts      = parse_url( $url );
    $home_parts = parse_url( $home );

    if ( empty( $parts['host'] ) || empty( $parts['scheme'] ) || empty( $home_parts['host'] ) ) {
        return false;
    }

    return in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true )
        && strtolower( $parts['host'] ) === strtolower( $home_parts['host'] );
}
