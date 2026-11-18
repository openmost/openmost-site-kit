<?php
/**
 * Test helper run from the command line: php cli.php <command> [json].
 */
$omsk_command = $argv[1] ?? '';
$omsk_payload = isset( $argv[2] ) ? json_decode( $argv[2], true ) : array();

$_SERVER['HTTP_HOST']      = trim( (string) file_get_contents( __DIR__ . '/flags/host' ) );
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI']    = '/';

if ( 'install' === $omsk_command ) {
    define( 'WP_INSTALLING', true );
}

require __DIR__ . '/wp-config.php';

switch ( $omsk_command ) {
    case 'install':
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        if ( ! is_blog_installed() ) {
            wp_install( 'Site Kit Tests', 'admin', 'admin@example.test', false, '', wp_generate_password( 24 ) );
        }
        update_option( 'template', 'omsk-test' );
        update_option( 'stylesheet', 'omsk-test' );
        update_option( 'permalink_structure', '' );
        update_option( 'active_plugins', array( 'openmost-site-kit/openmost-site-kit.php' ) );
        echo 'installed';
        break;

    case 'state':
        if ( array_key_exists( 'settings', $omsk_payload ) ) {
            if ( null === $omsk_payload['settings'] ) {
                delete_option( 'omsk-settings' );
            } else {
                update_option( 'omsk-settings', $omsk_payload['settings'] );
            }
        }
        if ( array_key_exists( 'defaultApplied', $omsk_payload ) ) {
            if ( $omsk_payload['defaultApplied'] ) {
                update_option( 'omsk-ai-bot-tracking-default-applied', 1 );
            } else {
                delete_option( 'omsk-ai-bot-tracking-default-applied' );
            }
        }
        if ( array_key_exists( 'active', $omsk_payload ) ) {
            update_option( 'active_plugins', $omsk_payload['active'] ? array( 'openmost-site-kit/openmost-site-kit.php' ) : array() );
        }
        echo 'ok';
        break;

    case 'get-option':
        echo wp_json_encode( get_option( $omsk_payload['name'], null ) );
        break;

    case 'cache-status':
        echo wp_json_encode( function_exists( 'omsk_get_ai_bot_cache_status' ) ? omsk_get_ai_bot_cache_status() : null );
        break;

    default:
        fwrite( STDERR, 'Unknown command ' . $omsk_command );
        exit( 1 );
}
