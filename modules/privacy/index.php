<?php
/**
 * Privacy Module
 * Provides Matomo opt-out shortcode functionality for privacy compliance
 * Settings are now managed in Settings page (Privacy tab)
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register opt-out shortcodes
 * Usage: [matomo_opt_out] or [omsk_matomo_opt_out] (legacy)
 */
add_shortcode('matomo_opt_out', 'omsk_matomo_opt_out_shortcode');
add_shortcode('omsk_matomo_opt_out', 'omsk_matomo_opt_out_shortcode');

/**
 * Render opt-out shortcode
 * Uses iframe approach to comply with WordPress coding standards
 *
 * @param array $params Shortcode attributes
 * @return string HTML output
 */
function omsk_matomo_opt_out_shortcode($params)
{
    $host = omsk_get_matomo_host();

    if (!$host) {
        return '<p>' . esc_html__('Matomo is not configured.', 'openmost-site-kit') . '</p>';
    }

    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'language'         => 'auto',
        'show_intro'       => '1',
        'width'            => '100%',
        'height'           => '200px',
        'background_color' => '',
        'font_color'       => '',
        'font_size'        => '',
        'font_family'      => '',
    ), $params);

    // Build opt-out iframe URL (using optOut action for iframe embedding)
    $iframe_params = array(
        'module'    => 'CoreAdminHome',
        'action'    => 'optOut',
        'language'  => esc_attr($atts['language']),
        'showIntro' => esc_attr($atts['show_intro']),
    );

    // Add optional style parameters if provided
    if (!empty($atts['background_color'])) {
        $iframe_params['backgroundColor'] = omsk_sanitize_hex_color_no_hash($atts['background_color']);
    }
    if (!empty($atts['font_color'])) {
        $iframe_params['fontColor'] = omsk_sanitize_hex_color_no_hash($atts['font_color']);
    }
    if (!empty($atts['font_size'])) {
        $iframe_params['fontSize'] = esc_attr($atts['font_size']);
    }
    if (!empty($atts['font_family'])) {
        $iframe_params['fontFamily'] = esc_attr($atts['font_family']);
    }

    $iframe_url = add_query_arg($iframe_params, trailingslashit($host) . 'index.php');

    $html = sprintf(
        '<iframe src="%s" style="border: 0; width: %s; height: %s;" title="%s"></iframe>',
        esc_url($iframe_url),
        esc_attr($atts['width']),
        esc_attr($atts['height']),
        esc_attr__('Matomo Opt-Out', 'openmost-site-kit')
    );

    return $html;
}

/**
 * Sanitize hex color without hash
 *
 * @param string $color Hex color without hash
 * @return string Sanitized hex color
 */
function omsk_sanitize_hex_color_no_hash($color)
{
    $color = ltrim($color, '#');
    if (preg_match('/^[a-fA-F0-9]{6}$/', $color)) {
        return strtoupper($color);
    }
    return '';
}
