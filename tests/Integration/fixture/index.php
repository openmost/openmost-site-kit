<?php
/**
 * Router of the PHP built-in server: real files are served as is, everything
 * else goes through WordPress like wp-blog-header.php would.
 */
$omsk_path = (string) parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

if ( '/' !== $omsk_path && is_file( __DIR__ . $omsk_path ) ) {
    return false;
}

define( 'WP_USE_THEMES', true );
require __DIR__ . '/wp-config.php';
wp();
require ABSPATH . WPINC . '/template-loader.php';
