<?php
/**
 * AI Chatbot Tracking Module
 *
 * AI chatbots (ChatGPT-User, Claude-User, Perplexity-User...) fetch pages
 * without executing JavaScript, so neither the classic tracker nor Tag Manager
 * can see them. This module sends one server-side bot request per rendered
 * page (recMode=1) whatever tracking method is selected, so the Matomo
 * "AI Assistants > AI Chatbots" reports are fed natively (Matomo 5.7+).
 *
 * Same coverage as Connect Matomo (wp-piwik):
 * - pages rendered by WordPress: request sent in wp_footer, with http_status and pf_srv,
 * - pages served by advanced-cache.php: track.php drop-in required from wp-config.php,
 * - pages served by LiteSpeed or an ESI-capable CDN: <esi:include> to track.php,
 * - classic JS sends recMode=2 and flags JS-capable AI agents with a
 *   `matomo_has_js` cookie so they are not counted twice server-side.
 *
 * Enabled by default, can be switched off in Settings.
 *
 * @package Openmost_Site_Kit
 * @since 2.4.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/core.php';

add_action( 'init', 'omsk_ai_bot_tracking_apply_default' );
add_action( 'litespeed_init', 'omsk_ai_bot_litespeed_init' );
add_action( 'wp_footer', 'omsk_track_ai_bot_request', 999999 );

/**
 * Whether AI chatbot tracking is enabled. Defaults to true when never saved.
 *
 * @since 2.4.0
 * @param array|null $options Plugin options, read from the database when null.
 * @return bool
 */
function omsk_is_ai_bot_tracking_enabled( $options = null ) {
    if ( null === $options ) {
        $options = get_option( 'omsk-settings', array() );
    }

    if ( ! is_array( $options ) || ! isset( $options['omsk-matomo-enable-ai-bot-tracking-field'] ) ) {
        return true;
    }

    return (bool) $options['omsk-matomo-enable-ai-bot-tracking-field'];
}

/**
 * Switch the feature on once for sites that saved their settings while it
 * was still opt-in (saved value 0 was the old default, not a user choice).
 *
 * @since 2.4.0
 * @return void
 */
function omsk_ai_bot_tracking_apply_default() {
    if ( get_option( 'omsk-ai-bot-tracking-default-applied' ) ) {
        return;
    }

    $options = get_option( 'omsk-settings', array() );

    if ( is_array( $options ) && ! empty( $options ) ) {
        $options['omsk-matomo-enable-ai-bot-tracking-field'] = 1;
        update_option( 'omsk-settings', $options );
    }

    update_option( 'omsk-ai-bot-tracking-default-applied', 1, true );
}

/**
 * Check if the request comes from a known AI chatbot.
 *
 * @since 2.3.0
 * @return bool
 */
function omsk_is_ai_bot() {
    return omsk_is_ai_bot_user_agent( omsk_get_request_user_agent() );
}

/**
 * Tell LiteSpeed Cache the page holds an ESI block, otherwise it is not processed.
 *
 * @since 2.4.0
 * @return void
 */
function omsk_ai_bot_litespeed_init() {
    $config = omsk_get_ai_bot_tracking_config( get_option( 'omsk-settings', array() ) );

    if ( null !== $config && $config['esi'] && class_exists( '\LiteSpeed\ESI' ) && method_exists( '\LiteSpeed\ESI', 'set_has_esi' ) ) {
        \LiteSpeed\ESI::set_has_esi();
    }
}

/**
 * Send the AI chatbot request for a page rendered by WordPress, or print the
 * ESI include when pages are cached by LiteSpeed.
 *
 * @since 2.3.0
 * @return void
 */
function omsk_track_ai_bot_request() {
    static $done = false;

    if ( $done ) {
        return;
    }
    $done = true;

    if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_preview() ) {
        return;
    }

    $config = omsk_get_ai_bot_tracking_config( get_option( 'omsk-settings', array() ) );

    if ( null === $config ) {
        return;
    }

    // The HTML is cached and served to everyone, the include is evaluated per request at the edge.
    if ( $config['esi'] ) {
        $src = plugins_url( 'modules/ai-bot-tracking/track.php', OPENMOSTSITEKIT_PLUGIN_FILE ) . '?omsk_esi=' . rawurlencode( omsk_get_current_url() );
        echo '<esi:include src="' . esc_attr( $src ) . '" cache-control="no-cache" />';
        return;
    }

    if ( omsk_ai_bot_has_js_cookie() || ! omsk_is_ai_bot() ) {
        return;
    }

    $status = http_response_code();

    omsk_send_ai_bot_request(
        $config,
        omsk_get_request_user_agent(),
        omsk_get_current_url(),
        $status ? (int) $status : 200,
        omsk_ai_bot_elapsed_ms(),
        wp_get_document_title()
    );
}

/**
 * Classic tracker snippet that flags JS-capable AI agents with a cookie, so
 * the server-side request is skipped for them. The cookie is only ever set
 * for known AI User-Agents, never for humans.
 *
 * @since 2.4.0
 * @return string JavaScript, to be printed inside the classic Matomo script.
 */
function omsk_get_ai_bot_js_cookie_snippet() {
    $user_agents = wp_json_encode( array_values( omsk_get_ai_bot_user_agents() ) );

    return "_paq.push([function () {
        var ua = navigator.userAgent.toLowerCase(), list = {$user_agents};
        for (var i = 0; i < list.length; i++) {
          if (ua.indexOf(list[i].toLowerCase()) !== -1) {
            var path = this.getCookiePath(), domain = this.getCookieDomain();
            document.cookie = 'matomo_has_js=1;path=' + (path || '/') + (domain ? ';domain=' + domain : '') + ';SameSite=Lax';
            return;
          }
        }
      }]);";
}

/**
 * Cache status shown in Settings.
 *
 * @since 2.4.0
 * @return array{pageCache: bool, dropinLoaded: bool, dropinSnippet: string, liteSpeed: bool}
 */
function omsk_get_ai_bot_cache_status() {
    return array(
        'pageCache'     => defined( 'WP_CACHE' ) && WP_CACHE,
        'dropinLoaded'  => function_exists( 'omsk_ai_bot_dropin_shutdown' ),
        'dropinSnippet' => omsk_get_ai_bot_dropin_snippet(),
        'liteSpeed'     => defined( 'LSCWP_V' ) || class_exists( '\LiteSpeed\Core' ),
    );
}

/**
 * Lines to paste into wp-config.php, the is_file() guard keeps the site up if the plugin is deleted.
 *
 * @since 2.4.0
 * @return string
 */
function omsk_get_ai_bot_dropin_snippet() {
    $file    = wp_normalize_path( OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/ai-bot-tracking/track.php' );
    $abspath = wp_normalize_path( ABSPATH );

    if ( 0 === strpos( $file, $abspath ) ) {
        $path = "ABSPATH . '" . substr( $file, strlen( $abspath ) ) . "'";
    } else {
        $path = "'" . $file . "'";
    }

    return "if ( is_file( {$path} ) ) {\n\trequire_once {$path};\n}";
}
