<?php
/**
 * PHPUnit bootstrap.
 *
 * Unit tests load the WordPress-independent core plus the plugin module on top
 * of the minimal stubs below. Integration tests never load WordPress in this
 * process: they boot a real site in a background server (see WordPressSite).
 *
 * @package Openmost_Site_Kit
 */

require __DIR__ . '/vendor/autoload.php';

define( 'OMSK_TESTS_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'OMSK_TESTS_TMP_DIR', __DIR__ . '/.tmp' );

define( 'ABSPATH', '/var/www/html/' );
define( 'OPENMOSTSITEKIT_PLUGIN_DIR', OMSK_TESTS_PLUGIN_DIR );
define( 'OPENMOSTSITEKIT_PLUGIN_FILE', OMSK_TESTS_PLUGIN_DIR . 'openmost-site-kit.php' );

require __DIR__ . '/Support/wp-stubs.php';
require OMSK_TESTS_PLUGIN_DIR . 'modules/ai-bot-tracking/index.php';
