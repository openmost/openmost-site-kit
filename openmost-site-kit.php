<?php
/*
Plugin Name: Openmost Site Kit
Plugin URI: https://openmost.io/openmost-site-kit
Description: A site kit plugin for Matomo
Author: Openmost
Version: 1.0
Author URI: https://openmost.io
*/

// Make sure we don't expose any info if called directly
if ( ! function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

// Contant
define( 'OPENMOSTSITEKIT_VERSION', '1.0' );
define( 'OPENMOSTSITEKIT_PHP_MINIMUM', '5.6.0' );
define( 'OPENMOSTSITEKIT_WP_MINIMUM', '5.2.0' );
define( 'OPENMOSTSITEKIT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Admin pages
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'admin/settings.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'admin/datalayer.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'admin/dashboard.php';

// Modules
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/datalayer/index.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/matomo-classic-tracking-code/index.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/matomo-tag-manager-tracking-code/index.php';
require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/matomo-dashboard/index.php';
