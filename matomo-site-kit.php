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
if (!function_exists('add_action')) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

define('MATOMO_SITE_KIT_VERSION', '5.1');
define('MATOMO_SITE_KIT__MINIMUM_WP_VERSION', '5.0');
define('MATOMO_SITE_KIT__PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once MATOMO_SITE_KIT__PLUGIN_DIR . 'inc/author.php';
require_once MATOMO_SITE_KIT__PLUGIN_DIR . 'inc/term.php';
require_once MATOMO_SITE_KIT__PLUGIN_DIR . 'inc/type.php';

require_once MATOMO_SITE_KIT__PLUGIN_DIR . 'inc/archive.php';
require_once MATOMO_SITE_KIT__PLUGIN_DIR . 'inc/single-page.php';


add_action('wp_head', 'matomo_site_kit_init');
function matomo_site_kit_init()
{
    $dataLayer = array();

    if (is_front_page() && is_home()) {
        // Default homepage
    } elseif (is_front_page()) {
        // static homepage
    } elseif (is_home()) {
        // blog page
    }

    if (is_single() || is_page()) {
        $dataLayer['page'] = msk_get_single_page_details();
    }

    if (is_archive()) {
        var_dump('IS Archive');
    }

    if (is_author()) {
        //
    }

    if (is_search()) {
        //
    }

    if (is_404()) {
        //
    }

    if(is_attachment() ){
        //
    }

    $html = '<script id="matomo-site-kit-datalayer">let_mtm=window._mtm=window._mtm||[];_mtm.push(' . json_encode($dataLayer) . ');</script>';

    echo $html;
}
