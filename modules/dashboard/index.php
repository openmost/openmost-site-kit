<?php
/**
 * Dashboard Module
 * Main plugin dashboard page with React-based analytics display
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
     * Register dashboard page
     */
    function omsk_dashboard_page()
    {
        add_submenu_page(
            'openmost-site-kit',
            __('Dashboard', 'openmost-site-kit'),
            __('Dashboard', 'openmost-site-kit'),
            'edit_posts',
            'openmost-site-kit',
            'omsk_view_dashboard',
            1
        );
    }

    add_action('admin_menu', 'omsk_dashboard_page');

    /**
     * Render dashboard page
     */
    function omsk_view_dashboard()
    {
        // Check if Matomo is configured
        $host = omsk_get_matomo_host();
        $idSite = omsk_get_matomo_idsite();
        $tokenAuth = omsk_get_matomo_token_auth();

        echo '<div class="wrap">';

        if (!$host || !$idSite || !$tokenAuth) {
            echo '<h1>' . esc_html__('Dashboard', 'openmost-site-kit') . '</h1>';
            echo '<div class="notice notice-warning"><p>';
            echo esc_html__('Matomo is not configured. Please configure your Matomo instance in the Site Kit settings.', 'openmost-site-kit');
            echo ' <a href="' . esc_url(admin_url('admin.php?page=omsk-settings')) . '">' . esc_html__('Go to Settings', 'openmost-site-kit') . '</a>';
            echo '</p></div>';
            echo '</div>';
            return;
        }

        echo '<div id="omsk-dashboard-root"></div>';
        echo '</div>';
    }
}
