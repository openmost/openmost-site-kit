<?php
/**
 * Stands in for a page cache served from advanced-cache.php (WP Super Cache simple mode,
 * W3 Total Cache Disk: Basic): when flags/cache-hit exists its content is the cached page.
 */
$omsk_cached_page = dirname( __DIR__ ) . '/flags/cache-hit';

if ( is_file( $omsk_cached_page ) && 'GET' === ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) && 'cli' !== PHP_SAPI ) {
    header( 'Content-Type: text/html; charset=UTF-8' );
    header( 'X-Test-Cache: hit' );
    echo file_get_contents( $omsk_cached_page );
    exit;
}
