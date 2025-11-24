<?php
/**
 * WordPress Dashboard Widget Module
 * Displays Matomo analytics summary on the main WordPress dashboard
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the dashboard widget
 */
add_action('wp_dashboard_setup', 'omsk_register_dashboard_widget');

function omsk_register_dashboard_widget()
{
    // Only show if user can edit posts (same as main plugin menu)
    if (!current_user_can('edit_posts')) {
        return;
    }

    // Check if Matomo is configured
    $options = get_option('omsk-settings');
    $host = isset($options['omsk-matomo-host-field']) ? $options['omsk-matomo-host-field'] : '';
    $idSite = isset($options['omsk-matomo-idsite-field']) ? $options['omsk-matomo-idsite-field'] : '';
    $tokenAuth = isset($options['omsk-matomo-token-auth-field']) ? $options['omsk-matomo-token-auth-field'] : '';

    // Only register widget if Matomo is configured
    if (!$host || !$idSite || !$tokenAuth) {
        return;
    }

    wp_add_dashboard_widget(
        'omsk_dashboard_widget',
        __('Matomo Analytics', 'openmost-site-kit'),
        'omsk_dashboard_widget_render',
        null,
        null,
        'normal',
        'high'
    );
}

/**
 * Render the dashboard widget container
 */
function omsk_dashboard_widget_render()
{
    echo '<div id="omsk-wp-dashboard-widget-root"></div>';
}
