<?php
/**
 * Site Search Tracking Module
 *
 * Tracks WordPress site searches with keyword, category, and result count.
 * Supports three tracking methods:
 * - Classic JS (_paq.push(['trackSiteSearch', ...]))
 * - Tag Manager (_mtm.push with search event)
 * - Server-Side PHP (MatomoTracker->doTrackSiteSearch)
 *
 * @package Openmost_Site_Kit
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Hook into wp for search tracking initialization.
add_action( 'wp', 'omsk_init_search_tracking' );

/**
 * Initialize search tracking based on settings.
 *
 * @since 1.0.0
 * @return void
 */
function omsk_init_search_tracking() {
    // Only process on search pages.
    if ( ! is_search() ) {
        return;
    }

    $options = get_option( 'omsk-settings', array() );

    if ( empty( $options ) ) {
        return;
    }

    // Check which tracking method is enabled.
    $enable_classic = ! empty( $options['omsk-matomo-enable-classic-tracking-code-field'] );
    $enable_mtm     = ! empty( $options['omsk-matomo-enable-mtm-tracking-code-field'] );
    $enable_server  = ! empty( $options['omsk-matomo-enable-server-tracking-field'] );

    // Check if search tracking is enabled for each method.
    $enable_js_search        = ! empty( $options['omsk-matomo-enable-js-search-tracking-field'] );
    $enable_datalayer_search = ! empty( $options['omsk-matomo-enable-datalayer-search-tracking-field'] );
    $enable_server_search    = ! empty( $options['omsk-matomo-enable-server-search-tracking-field'] );

    // Classic JS tracking.
    if ( $enable_classic && $enable_js_search ) {
        add_action( 'wp_footer', 'omsk_output_classic_search_tracking', 100 );
    }

    // Tag Manager dataLayer tracking.
    if ( $enable_mtm && $enable_datalayer_search ) {
        add_action( 'wp_footer', 'omsk_output_datalayer_search_tracking', 100 );
    }

    // Server-side PHP tracking.
    if ( $enable_server && $enable_server_search ) {
        omsk_track_search_server_side( $options );
    }
}

/**
 * Get search data from WordPress query.
 *
 * @since 1.0.0
 * @return array Search data with keyword, category, and count.
 */
function omsk_get_search_data() {
    global $wp_query;

    $search_keyword  = get_search_query();
    $search_category = '';
    $search_count    = $wp_query->found_posts;

    // Check if search is filtered by category.
    if ( is_category() ) {
        $category = get_queried_object();
        if ( $category && isset( $category->name ) ) {
            $search_category = $category->name;
        }
    }

    // Check for category filter in query.
    $cat_id = get_query_var( 'cat' );
    if ( $cat_id ) {
        $category = get_category( $cat_id );
        if ( $category && ! is_wp_error( $category ) ) {
            $search_category = $category->name;
        }
    }

    // Check for post type filter.
    $post_type = get_query_var( 'post_type' );
    if ( $post_type && 'any' !== $post_type ) {
        if ( is_array( $post_type ) ) {
            $post_type = implode( ', ', $post_type );
        }
        $post_type_obj = get_post_type_object( $post_type );
        if ( $post_type_obj ) {
            $search_category = $post_type_obj->labels->singular_name;
        }
    }

    return array(
        'keyword'  => $search_keyword,
        'category' => $search_category,
        'count'    => absint( $search_count ),
    );
}

/**
 * Output Classic JS search tracking code.
 *
 * @since 1.0.0
 * @return void
 */
function omsk_output_classic_search_tracking() {
    $search_data = omsk_get_search_data();

    // Don't track empty searches.
    if ( empty( $search_data['keyword'] ) ) {
        return;
    }

    $keyword  = esc_js( $search_data['keyword'] );
    $category = $search_data['category'] ? "'" . esc_js( $search_data['category'] ) . "'" : 'false';
    $count    = absint( $search_data['count'] );
    ?>
    <script>
    var _paq = window._paq = window._paq || [];
    _paq.push(['trackSiteSearch', '<?php echo $keyword; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped. ?>', <?php echo $category; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped. ?>, <?php echo $count; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Integer. ?>]);
    </script>
    <?php
}

/**
 * Output dataLayer search tracking for Matomo Tag Manager.
 *
 * @since 1.0.0
 * @return void
 */
function omsk_output_datalayer_search_tracking() {
    $search_data = omsk_get_search_data();

    // Don't track empty searches.
    if ( empty( $search_data['keyword'] ) ) {
        return;
    }

    $event_data = array(
        'event'           => 'search',
        'search_keyword'  => $search_data['keyword'],
        'search_category' => $search_data['category'] ? $search_data['category'] : null,
        'search_count'    => $search_data['count'],
    );
    ?>
    <script>
    window._mtm = window._mtm || [];
    _mtm.push(<?php echo wp_json_encode( $event_data ); ?>);
    </script>
    <?php
}

/**
 * Track search server-side using Matomo PHP tracker.
 *
 * @since 1.0.0
 * @param array $options Plugin settings.
 * @return void
 */
function omsk_track_search_server_side( $options ) {
    $host       = isset( $options['omsk-matomo-host-field'] ) ? $options['omsk-matomo-host-field'] : '';
    $id_site    = isset( $options['omsk-matomo-idsite-field'] ) ? $options['omsk-matomo-idsite-field'] : '';
    $token_auth = isset( $options['omsk-matomo-token-auth-field'] ) ? $options['omsk-matomo-token-auth-field'] : '';

    // Validate required settings.
    if ( empty( $host ) || empty( $id_site ) ) {
        return;
    }

    // Ensure MatomoTracker class is available.
    if ( ! class_exists( 'MatomoTracker' ) ) {
        return;
    }

    $search_data = omsk_get_search_data();

    // Don't track empty searches.
    if ( empty( $search_data['keyword'] ) ) {
        return;
    }

    try {
        $tracker = new MatomoTracker( absint( $id_site ), $host );

        if ( $token_auth ) {
            $tracker->setTokenAuth( $token_auth );
        }

        // Disable cookies for server-side tracking.
        $tracker->disableCookieSupport();

        // Set visitor IP.
        $visitor_ip = omsk_get_visitor_ip();
        if ( $visitor_ip ) {
            $tracker->setIp( $visitor_ip );
        }

        // Set User-Agent.
        if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
            $tracker->setUserAgent( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) );
        }

        // Set Accept-Language.
        if ( ! empty( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
            $tracker->setBrowserLanguage( sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) );
        }

        // Set page URL.
        $page_url = omsk_get_current_url();
        $tracker->setUrl( $page_url );

        // Track site search.
        $tracker->doTrackSiteSearch(
            $search_data['keyword'],
            $search_data['category'] ? $search_data['category'] : false,
            $search_data['count']
        );

    } catch ( Exception $e ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging.
            error_log( 'Matomo Search Tracking Error: ' . $e->getMessage() );
        }
    }
}
