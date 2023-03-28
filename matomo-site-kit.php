<?php
/*
Plugin Name: Matomo Site Kit
Plugin URI: https://openmost.io/matomo-site-kit
Description: A site kit plugin for Matomo
Author: Openmost
Version: 1.0
Author URI: http://openmost.io
*/

// Make sure we don't expose any info if called directly
if ( ! function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'MATOMO_SITE_KIT_VERSION', '1.0' );
define( 'MATOMO_SITE_KIT__MINIMUM_WP_VERSION', '5.0' );
define( 'MATOMO_SITE_KIT__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );


require_once MATOMO_SITE_KIT__PLUGIN_DIR . 'datalayer.php';

