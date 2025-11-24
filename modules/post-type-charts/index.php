<?php
/**
 * Post Type Charts Module
 * Adds analytics meta boxes to post/page edit screens
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
     * Add meta boxes to all public post types
     */
    function omsk_add_post_type_charts_box()
    {
        $screens = array_values(get_post_types(array('public' => true)));

        foreach ($screens as $screen) {
            add_meta_box(
                'omsk_post_type_charts',
                __('Matomo - Visits Summary', 'openmost-site-kit'),
                'omsk_post_type_charts_box_content',
                $screen
            );
        }
    }

    add_action('add_meta_boxes', 'omsk_add_post_type_charts_box');

    /**
     * Render meta box content
     */
    function omsk_post_type_charts_box_content()
    {
        global $post;
        $post_id = $post->ID;

        // Check if Matomo is configured
        $host = omsk_get_matomo_host();
        $idSite = omsk_get_matomo_idsite();
        $tokenAuth = omsk_get_matomo_token_auth();

        if (!$host || !$idSite || !$tokenAuth) {
            echo '<div class="notice notice-warning inline"><p>';
            echo esc_html__('Matomo is not configured. Please configure your Matomo instance in the Site Kit settings.', 'openmost-site-kit');
            echo ' <a href="' . esc_url(admin_url('admin.php?page=omsk-settings')) . '">' . esc_html__('Go to Settings', 'openmost-site-kit') . '</a>';
            echo '</p></div>';
            return;
        }

        // Render React root with post ID as data attribute
        echo '<div id="omsk-post-analytics-root-' . esc_attr($post_id) . '" data-post-id="' . esc_attr($post_id) . '"></div>';
    }
}
