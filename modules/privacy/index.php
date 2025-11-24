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

    $language = esc_attr($atts['language']);
    $show_intro = esc_attr($atts['show_intro']);

    // Generate unique ID for multiple opt-out forms on same page
    $unique_id = 'matomo-opt-out-' . uniqid();

    // Build opt-out script URL
    $script_params = array(
        'module'    => 'CoreAdminHome',
        'action'    => 'optOutJS',
        'divId'     => $unique_id,
        'language'  => $language,
        'showIntro' => $show_intro,
    );

    // Add optional style parameters if provided
    if (!empty($atts['background_color'])) {
        $script_params['backgroundColor'] = omsk_sanitize_hex_color_no_hash($atts['background_color']);
    }
    if (!empty($atts['font_color'])) {
        $script_params['fontColor'] = omsk_sanitize_hex_color_no_hash($atts['font_color']);
    }
    if (!empty($atts['font_size'])) {
        $script_params['fontSize'] = esc_attr($atts['font_size']);
    }
    if (!empty($atts['font_family'])) {
        $script_params['fontFamily'] = esc_attr($atts['font_family']);
    }

    $script_url = add_query_arg($script_params, trailingslashit($host) . 'index.php');

    $html = sprintf(
        '<div id="%s" style="width: %s; min-height: %s;"></div>',
        esc_attr($unique_id),
        esc_attr($atts['width']),
        esc_attr($atts['height'])
    );
    $html .= sprintf(
        '<script src="%s"></script>',
        esc_url($script_url)
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
