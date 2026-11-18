<?php
/**
 * AI chatbot tracking for pages that never reach the plugin.
 *
 * Two entry points:
 *
 * 1. Drop-in, required from wp-config.php. Page caches that serve from
 *    advanced-cache.php (WP Super Cache simple mode, W3 Total Cache Disk: Basic,
 *    WP Rocket PHP fallback...) exit before plugins are loaded. A shutdown
 *    function then boots the minimum of WordPress needed to read the settings
 *    and sends the chatbot request.
 *
 * 2. ESI endpoint, requested by LiteSpeed (or any CDN supporting ESI) through
 *    the <esi:include> tag printed in the cached HTML. LiteSpeed forwards the
 *    visitor User-Agent, the page URL travels in the omsk_esi parameter.
 *
 * Normal visitors cost one User-Agent check, WordPress is only booted for AI chatbots.
 *
 * phpcs:disable WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification, WordPress.WP.AlternativeFunctions
 *
 * @package Openmost_Site_Kit
 * @since 2.4.0
 */

require_once __DIR__ . '/core.php';

if ( ! defined( 'ABSPATH' ) ) {
    if ( isset( $_GET['omsk_esi'] ) ) {
        omsk_ai_bot_handle_esi_request();
    }
    return;
}

register_shutdown_function( 'omsk_ai_bot_dropin_shutdown' );

/**
 * Track the chatbot when the page was served by advanced-cache.php.
 *
 * @since 2.4.0
 * @return void
 */
function omsk_ai_bot_dropin_shutdown() {
    if ( ! empty( $GLOBALS['omsk_ai_bot_tracked'] ) || ! empty( $GLOBALS['omsk_ai_bot_in_esi'] ) ) {
        return;
    }

    // Without a page cache, WordPress always reaches the plugin module.
    if ( ! defined( 'WP_CACHE' ) || ! WP_CACHE || 'cli' === PHP_SAPI ) {
        return;
    }

    // WordPress ran normally: the plugin module handled the request (or the plugin is inactive).
    if ( function_exists( 'did_action' ) && did_action( 'plugins_loaded' ) ) {
        return;
    }

    $method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';
    if ( 'GET' !== $method && 'HEAD' !== $method ) {
        return;
    }

    $user_agent = omsk_get_request_user_agent();
    if ( ! omsk_is_ai_bot_user_agent( $user_agent ) || omsk_ai_bot_has_js_cookie() ) {
        return;
    }

    $status = http_response_code();
    $status = $status ? (int) $status : 200;
    if ( $status >= 500 ) {
        return;
    }

    $elapsed_ms = omsk_ai_bot_elapsed_ms();

    if ( ! omsk_ai_bot_boot_options_api() || ! omsk_ai_bot_plugin_is_active() ) {
        return;
    }

    $config = omsk_get_ai_bot_tracking_config( get_option( 'omsk-settings', array() ) );

    // With ESI the cached HTML carries the include, LiteSpeed sends the request.
    if ( null === $config || $config['esi'] ) {
        return;
    }

    omsk_send_ai_bot_request( $config, $user_agent, omsk_ai_bot_current_url(), $status, $elapsed_ms );
}

/**
 * Load the database layer and the options API after advanced-cache.php exited,
 * mirroring the order of wp-settings.php.
 *
 * @since 2.4.0
 * @return bool Whether get_option() is usable.
 */
function omsk_ai_bot_boot_options_api() {
    global $wpdb;

    if ( function_exists( 'get_option' ) && ! empty( $wpdb ) ) {
        return true;
    }

    $wpinc = ABSPATH . ( defined( 'WPINC' ) ? WPINC : 'wp-includes' ) . '/';

    if ( ! is_file( $wpinc . 'functions.php' ) || ! function_exists( 'require_wp_db' ) ) {
        return false;
    }

    $files = array(
        'class-wp-list-util.php' => class_exists( 'WP_List_Util', false ),
        'class-wp-token-map.php' => class_exists( 'WP_Token_Map', false ),
        'utf8.php'               => function_exists( 'wp_has_noncharacters' ),
        'formatting.php'         => function_exists( 'wptexturize' ),
        'functions.php'          => function_exists( 'wp_parse_args' ),
    );

    foreach ( $files as $file => $loaded ) {
        if ( ! $loaded && is_file( $wpinc . $file ) ) {
            require_once $wpinc . $file;
        }
    }

    if ( empty( $wpdb ) ) {
        require_wp_db();
        wp_set_wpdb_vars();
    }

    if ( ! function_exists( 'wp_cache_get' ) ) {
        wp_start_object_cache();
    }

    return function_exists( 'get_option' ) && ! empty( $wpdb );
}

/**
 * The drop-in stays in wp-config.php when the plugin is deactivated.
 *
 * @since 2.4.0
 * @return bool
 */
function omsk_ai_bot_plugin_is_active() {
    // Network activation lives in sitemeta, not worth booting multisite for.
    if ( function_exists( 'is_multisite' ) && is_multisite() ) {
        return true;
    }

    foreach ( (array) get_option( 'active_plugins', array() ) as $plugin ) {
        if ( '/openmost-site-kit.php' === substr( (string) $plugin, -22 ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Handle the ESI subrequest printed in cached pages.
 *
 * @since 2.4.0
 * @return void
 */
function omsk_ai_bot_handle_esi_request() {
    if ( ! headers_sent() ) {
        header( 'Content-Type: text/html; charset=UTF-8' );
        header( 'Cache-Control: no-store, max-age=0' );
        header( 'X-Robots-Tag: noindex' );
    }

    $user_agent = omsk_get_request_user_agent();
    if ( ! omsk_is_ai_bot_user_agent( $user_agent ) || omsk_ai_bot_has_js_cookie() ) {
        return;
    }

    $wp_config = omsk_ai_bot_find_wp_config();
    if ( null === $wp_config ) {
        return;
    }

    $GLOBALS['omsk_ai_bot_in_esi'] = true;

    if ( ! defined( 'SHORTINIT' ) ) {
        define( 'SHORTINIT', true );
    }

    require_once $wp_config;

    if ( ! function_exists( 'get_option' ) || ! omsk_ai_bot_plugin_is_active() ) {
        return;
    }

    $config = omsk_get_ai_bot_tracking_config( get_option( 'omsk-settings', array() ) );
    if ( null === $config || ! $config['esi'] ) {
        return;
    }

    $url = (string) $_GET['omsk_esi'];
    if ( ! omsk_ai_bot_is_own_url( $url, (string) get_option( 'home' ) ) ) {
        return;
    }

    omsk_send_ai_bot_request( $config, $user_agent, $url );
}

/**
 * Locate wp-config.php the way wp-load.php does, from wp-content/plugins/openmost-site-kit/modules/ai-bot-tracking.
 *
 * @since 2.4.0
 * @return string|null
 */
function omsk_ai_bot_find_wp_config() {
    $root = dirname( __DIR__, 5 );

    if ( is_file( $root . '/wp-config.php' ) ) {
        return $root . '/wp-config.php';
    }

    $parent = dirname( $root );
    if ( is_file( $parent . '/wp-config.php' ) && ! is_file( $parent . '/wp-settings.php' ) ) {
        return $parent . '/wp-config.php';
    }

    return null;
}
