<?php
/**
 * Minimal WordPress stubs for unit tests, state lives in $GLOBALS['omsk_wp'].
 *
 * @package Openmost_Site_Kit
 */

function omsk_tests_reset_wp(): void {
    $GLOBALS['omsk_wp'] = array(
        'options'     => array(),
        'filters'     => array(),
        'is_admin'    => false,
        'is_preview'  => false,
        'doing_ajax'  => false,
        'current_url' => 'https://example.test/hello-world/',
        'title'       => 'Hello world',
    );
    $GLOBALS['omsk_ai_bot_tracked'] = false;
    $_COOKIE                        = array();
    $_SERVER['HTTP_USER_AGENT']     = '';
    $_SERVER['SERVER_SOFTWARE']     = 'Apache/2.4.65 (Win64)';
}

omsk_tests_reset_wp();

function add_action( ...$args ) {
    return true;
}

function apply_filters( $tag, $value ) {
    return isset( $GLOBALS['omsk_wp']['filters'][ $tag ] ) ? $GLOBALS['omsk_wp']['filters'][ $tag ]( $value ) : $value;
}

function get_option( $name, $default = false ) {
    return array_key_exists( $name, $GLOBALS['omsk_wp']['options'] ) ? $GLOBALS['omsk_wp']['options'][ $name ] : $default;
}

function update_option( $name, $value, $autoload = null ) {
    $GLOBALS['omsk_wp']['options'][ $name ] = $value;
    return true;
}

function is_admin() {
    return $GLOBALS['omsk_wp']['is_admin'];
}

function is_preview() {
    return $GLOBALS['omsk_wp']['is_preview'];
}

function wp_doing_ajax() {
    return $GLOBALS['omsk_wp']['doing_ajax'];
}

function omsk_get_current_url() {
    return $GLOBALS['omsk_wp']['current_url'];
}

function wp_get_document_title() {
    return $GLOBALS['omsk_wp']['title'];
}

function plugins_url( $path, $plugin_file ) {
    return 'https://example.test/wp-content/plugins/openmost-site-kit/' . ltrim( $path, '/' );
}

function esc_attr( $text ) {
    return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function wp_json_encode( $data ) {
    return json_encode( $data );
}

function wp_normalize_path( $path ) {
    return str_replace( '\\', '/', (string) $path );
}
