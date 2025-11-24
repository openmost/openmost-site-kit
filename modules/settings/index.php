<?php
/**
 * Settings Module
 * Plugin settings page with tabs for configuration, privacy, and integrations
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin-only functionality
 */
if (is_admin()) {
    /**
     * Register settings page
     */
    function omsk_register_settings_options_page()
    {
        add_submenu_page(
            'openmost-site-kit',
            __('Settings', 'openmost-site-kit'),
            __('Settings', 'openmost-site-kit'),
            'manage_options',
            'omsk-settings',
            'omsk_view_settings',
            50
        );
    }

    add_action('admin_menu', 'omsk_register_settings_options_page');

    /**
     * Render settings page
     */
    function omsk_view_settings()
    {
        echo '<div class="wrap"><div id="omsk-settings-root"></div></div>';
    }
}
