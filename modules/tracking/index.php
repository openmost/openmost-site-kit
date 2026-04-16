<?php
/**
 * Tracking Code Injection Module
 *
 * Handles both Classic Matomo tracking and Tag Manager (MTM) tracking.
 * - Classic tracking: Supports consent mode options (requireConsent, requireCookieConsent).
 * - Tag Manager: GDPR options are managed in MTM UI. Two optional dataLayer pushes
 *   are provided for MTM triggers and variables:
 *     1. Config context: matomo.{host,site_id,container_id} + wordpress.environment.
 *     2. Page context: page_type, post_type label, post_id, taxonomies (category, tag,
 *        custom taxonomy slugs), author, locale, user_login_status, and user_id /
 *        user_role when User ID tracking is enabled.
 *
 * @package Openmost_Site_Kit
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Hook into wp_head for tracking code injection.
add_action( 'wp_head', 'omsk_inject_tracking_code', 10 );

// Hook into wp_body_open for noscript fallback.
add_action( 'wp_body_open', 'omsk_inject_noscript_tracker', 1 );

/**
 * Inject noscript image tracker at the beginning of body.
 *
 * @since 1.0.0
 * @return void
 */
function omsk_inject_noscript_tracker() {
    $options = get_option( 'omsk-settings', array() );

    if ( empty( $options ) ) {
        return;
    }

    $host                   = isset( $options['omsk-matomo-host-field'] ) ? $options['omsk-matomo-host-field'] : '';
    $id_site                = isset( $options['omsk-matomo-idsite-field'] ) ? $options['omsk-matomo-idsite-field'] : '';
    $enable_classic         = ! empty( $options['omsk-matomo-enable-classic-tracking-code-field'] );
    $enable_mtm             = ! empty( $options['omsk-matomo-enable-mtm-tracking-code-field'] );
    $id_container           = isset( $options['omsk-matomo-idcontainer-field'] ) ? $options['omsk-matomo-idcontainer-field'] : '';
    $excluded_roles         = isset( $options['omsk-matomo-excluded-roles-field'] ) ? (array) $options['omsk-matomo-excluded-roles-field'] : array();
    $enable_ai_bot_tracking = ! empty( $options['omsk-matomo-enable-ai-bot-tracking-field'] );

    if ( empty( $host ) || empty( $id_site ) ) {
        return;
    }

    // Check if any tracking is enabled.
    $tracking_enabled = ( $enable_mtm && $id_container ) || ( $enable_classic && ! $enable_mtm );

    if ( ! $tracking_enabled ) {
        return;
    }

    // Check if current user should be excluded from tracking.
    if ( omsk_should_exclude_user( $excluded_roles ) ) {
        return;
    }

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in omsk_get_noscript_tracker.
    echo omsk_get_noscript_tracker( $host, $id_site, $enable_ai_bot_tracking );
}

/**
 * Inject tracking code in the head.
 *
 * @since 1.0.0
 * @return void
 */
function omsk_inject_tracking_code() {
    $options = get_option( 'omsk-settings', array() );

    if ( empty( $options ) ) {
        return;
    }

    $host                    = isset( $options['omsk-matomo-host-field'] ) ? $options['omsk-matomo-host-field'] : '';
    $id_site                 = isset( $options['omsk-matomo-idsite-field'] ) ? $options['omsk-matomo-idsite-field'] : '';
    $id_container            = isset( $options['omsk-matomo-idcontainer-field'] ) ? $options['omsk-matomo-idcontainer-field'] : '';
    $enable_classic          = ! empty( $options['omsk-matomo-enable-classic-tracking-code-field'] );
    $enable_mtm              = ! empty( $options['omsk-matomo-enable-mtm-tracking-code-field'] );
    $excluded_roles          = isset( $options['omsk-matomo-excluded-roles-field'] ) ? (array) $options['omsk-matomo-excluded-roles-field'] : array();
    $consent_mode            = isset( $options['omsk-matomo-consent-mode-field'] ) ? $options['omsk-matomo-consent-mode-field'] : 'disabled';
    $enable_mtm_datalayer    = isset( $options['omsk-matomo-enable-mtm-datalayer-field'] ) ? (bool) $options['omsk-matomo-enable-mtm-datalayer-field'] : true;
    $enable_mtm_page_context = ! empty( $options['omsk-matomo-enable-mtm-page-context-field'] );
    $enable_userid_tracking  = ! empty( $options['omsk-matomo-enable-userid-tracking-field'] );
    $enable_heartbeat_timer  = ! empty( $options['omsk-matomo-enable-heartbeat-timer-field'] );
    $heartbeat_timer_delay   = isset( $options['omsk-matomo-heartbeat-timer-delay-field'] ) ? absint( $options['omsk-matomo-heartbeat-timer-delay-field'] ) : 15;
    $enable_ai_bot_tracking  = ! empty( $options['omsk-matomo-enable-ai-bot-tracking-field'] );
    $enable_js_search        = ! empty( $options['omsk-matomo-enable-js-search-tracking-field'] );

    if ( empty( $host ) || empty( $id_site ) ) {
        return;
    }

    // Check if current user should be excluded from tracking.
    if ( omsk_should_exclude_user( $excluded_roles ) ) {
        return;
    }

    // Get plan type (cloud or on-premise).
    $plan     = omsk_get_matomo_plan();
    $cdn_host = omsk_get_matomo_cdn_host();

    // Get User ID if tracking is enabled.
    $user_id   = null;
    $user_role = null;
    if ( $enable_userid_tracking ) {
        $user_id   = omsk_get_hashed_user_id();
        $user_role = omsk_get_current_user_role();
    }

    // On search pages with JS search tracking enabled, skip trackPageView
    // because trackSiteSearch will be called instead (avoids double tracking).
    $skip_track_pageview = is_search() && $enable_classic && $enable_js_search;

    // Inject Tag Manager tracking code (recommended).
    if ( $enable_mtm && $id_container ) {
        omsk_inject_mtm_code( $cdn_host, $id_container, $host, $id_site, $enable_mtm_datalayer, $user_id, $enable_ai_bot_tracking, $enable_mtm_page_context, $user_role );
    }

    // Inject classic tracking code (fallback) - only if MTM is not enabled.
    if ( $enable_classic && ! $enable_mtm ) {
        omsk_inject_classic_code( $host, $id_site, $plan, $consent_mode, $user_id, $enable_heartbeat_timer, $heartbeat_timer_delay, $enable_ai_bot_tracking, $skip_track_pageview );
    }
}

/**
 * Get SHA256 hashed user ID from logged-in user's email.
 *
 * @since 1.0.0
 * @return string|null SHA256 hash of user email or null if not logged in.
 */
function omsk_get_hashed_user_id() {
    if ( ! is_user_logged_in() ) {
        return null;
    }

    $user = wp_get_current_user();

    if ( ! $user || empty( $user->user_email ) ) {
        return null;
    }

    // Return SHA256 hash of email (lowercase for consistency).
    return hash( 'sha256', strtolower( $user->user_email ) );
}

/**
 * Get the primary role of the currently logged-in user.
 *
 * @since 1.0.0
 * @return string|null Role slug or null if not logged in.
 */
function omsk_get_current_user_role() {
    if ( ! is_user_logged_in() ) {
        return null;
    }

    $user = wp_get_current_user();

    if ( ! $user || empty( $user->roles ) ) {
        return null;
    }

    return (string) reset( $user->roles );
}

/**
 * Get WordPress environment type.
 *
 * @since 1.0.0
 * @return string Environment type (production, staging, development, local).
 */
function omsk_get_wp_environment() {
    // Check for wp_get_environment_type function (WP 5.5+).
    if ( function_exists( 'wp_get_environment_type' ) ) {
        return wp_get_environment_type();
    }

    // Fallback: Check WP_ENVIRONMENT_TYPE constant.
    if ( defined( 'WP_ENVIRONMENT_TYPE' ) ) {
        return WP_ENVIRONMENT_TYPE;
    }

    // Default to production.
    return 'production';
}

/**
 * Inject Matomo Tag Manager (MTM) code.
 *
 * Consent mode is NOT included here because GDPR options are managed directly
 * in the Tag Manager UI. Two optional dataLayer pushes are emitted before
 * mtm.Start so MTM triggers and variables can read them:
 *   - Config context ($enable_datalayer): matomo.{host,site_id,container_id}
 *     and wordpress.environment.
 *   - Page context ($enable_page_context): page_type, post_type label, post_id,
 *     taxonomies, locale, user_login_status, plus user_id/user_role when
 *     provided.
 *
 * @since 1.0.0
 * @param string      $cdn_host                CDN host URL.
 * @param string      $id_container            Container ID.
 * @param string      $host                    Matomo host URL.
 * @param string      $id_site                 Site ID.
 * @param bool        $enable_datalayer        Whether to push context to dataLayer.
 * @param string|null $user_id                 SHA256 hashed user ID or null.
 * @param bool        $enable_ai_bot_tracking  Whether AI bot tracking is enabled.
 * @param bool        $enable_page_context     Whether to push page context (page_type, taxonomies) to the dataLayer.
 * @param string|null $user_role               Primary role of the logged-in user or null.
 * @return void
 */
function omsk_inject_mtm_code( $cdn_host, $id_container, $host, $id_site, $enable_datalayer = true, $user_id = null, $enable_ai_bot_tracking = false, $enable_page_context = false, $user_role = null ) {
    $plan = omsk_get_matomo_plan();

    // Build script URL based on plan type.
    if ( 'cloud' === $plan ) {
        $script_url = $cdn_host . '/container_' . $id_container . '.js';
    } else {
        $script_url = $cdn_host . '/js/container_' . $id_container . '.js';
    }

    $wp_env       = omsk_get_wp_environment();
    $cdn_origin   = wp_parse_url( $cdn_host, PHP_URL_SCHEME ) . '://' . wp_parse_url( $cdn_host, PHP_URL_HOST );
    $is_cross_origin = ( $cdn_origin !== $host );
    ?>
    <?php if ( $is_cross_origin ) : ?>
    <link rel="preconnect" href="<?php echo esc_attr( $cdn_origin ); ?>" crossorigin>
    <link rel="dns-prefetch" href="<?php echo esc_attr( $host ); ?>">
    <link rel="preload" href="<?php echo esc_attr( $script_url ); ?>" as="script" crossorigin>
    <?php else : ?>
    <link rel="preconnect" href="<?php echo esc_attr( $host ); ?>">
    <link rel="preload" href="<?php echo esc_attr( $script_url ); ?>" as="script">
    <?php endif; ?>
    <!-- Matomo Tag Manager -->
    <script>
    var _mtm = window._mtm = window._mtm || [];
    <?php if ( $enable_datalayer ) : ?>
    _mtm.push({
        'matomo': {
            'host': '<?php echo esc_js( untrailingslashit( $host ) ); ?>',
            'site_id': <?php echo absint( $id_site ); ?>,
            'container_id': '<?php echo esc_js( $id_container ); ?>'
        },
        'wordpress': {
            'environment': '<?php echo esc_js( $wp_env ); ?>'
        }
    });
    <?php endif; ?>
    <?php
    if ( $enable_page_context ) {
        $page_context = omsk_get_page_context( $user_id, $user_role );
        if ( ! empty( $page_context ) ) {
            echo '    _mtm.push(' . wp_json_encode( $page_context ) . ");\n";
        }
    }
    ?>
    _mtm.push({'mtm.startTime': (new Date().getTime()), 'event': 'mtm.Start'});
    (function() {
      var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
      g.async=true; g.src='<?php echo esc_url( $script_url ); ?>'; s.parentNode.insertBefore(g,s);
    })();
    </script>
    <!-- End Matomo Tag Manager -->
    <?php
}

/**
 * Inject classic Matomo tracking code.
 *
 * This code supports consent mode options for GDPR compliance.
 *
 * @since 1.0.0
 * @param string      $host                    Matomo host URL.
 * @param string      $id_site                 Site ID.
 * @param string      $plan                    Plan type (cloud or on_premise).
 * @param string      $consent_mode            Consent mode (disabled, require_consent, require_cookie_consent).
 * @param string|null $user_id                 SHA256 hashed user ID or null.
 * @param bool        $enable_heartbeat_timer  Enable heartbeat timer for accurate time tracking.
 * @param int         $heartbeat_timer_delay   Heartbeat timer delay in seconds.
 * @param bool        $enable_ai_bot_tracking  Whether AI bot tracking is enabled.
 * @param bool        $skip_track_pageview     Skip trackPageView (e.g. on search pages where trackSiteSearch replaces it).
 * @return void
 */
function omsk_inject_classic_code( $host, $id_site, $plan, $consent_mode = 'disabled', $user_id = null, $enable_heartbeat_timer = false, $heartbeat_timer_delay = 15, $enable_ai_bot_tracking = false, $skip_track_pageview = false ) {
    // Determine script URL based on plan type.
    if ( 'cloud' === $plan ) {
        $cdn_host    = omsk_get_matomo_cdn_host();
        $script_url  = $cdn_host . '/matomo.js';
        $cdn_origin  = wp_parse_url( $cdn_host, PHP_URL_SCHEME ) . '://' . wp_parse_url( $cdn_host, PHP_URL_HOST );
    } else {
        $script_url  = trailingslashit( $host ) . 'matomo.js';
        $cdn_origin  = null;
    }

    $is_cross_origin = ( null !== $cdn_origin && $cdn_origin !== $host );
    ?>
    <?php if ( $is_cross_origin ) : ?>
    <link rel="preconnect" href="<?php echo esc_attr( $cdn_origin ); ?>" crossorigin>
    <link rel="dns-prefetch" href="<?php echo esc_attr( $host ); ?>">
    <link rel="preload" href="<?php echo esc_attr( $script_url ); ?>" as="script" crossorigin>
    <?php else : ?>
    <link rel="preconnect" href="<?php echo esc_attr( $host ); ?>">
    <link rel="preload" href="<?php echo esc_attr( $script_url ); ?>" as="script">
    <?php endif; ?>
    <!-- Matomo -->
    <script>
      var _paq = window._paq = window._paq || [];
      <?php if ( 'require_consent' === $consent_mode ) : ?>
      _paq.push(['requireConsent']);
      <?php elseif ( 'require_cookie_consent' === $consent_mode ) : ?>
      _paq.push(['requireCookieConsent']);
      <?php endif; ?>
      <?php if ( $user_id ) : ?>
      _paq.push(['setUserId', '<?php echo esc_js( $user_id ); ?>']);
      <?php endif; ?>
      <?php if ( $enable_heartbeat_timer ) : ?>
      _paq.push(['enableHeartBeatTimer', <?php echo absint( $heartbeat_timer_delay ); ?>]);
      <?php endif; ?>
      <?php if ( ! $skip_track_pageview ) : ?>
      _paq.push(['trackPageView']);
      <?php endif; ?>
      _paq.push(['enableLinkTracking']);
      (function() {
        var u="<?php echo esc_js( trailingslashit( $host ) ); ?>";
        <?php if ( $enable_ai_bot_tracking ) : ?>
        _paq.push(['setTrackerUrl', u+'matomo.php?recMode=2&source=WordPress']);
        <?php else : ?>
        _paq.push(['setTrackerUrl', u+'matomo.php']);
        <?php endif; ?>
        _paq.push(['setSiteId', '<?php echo esc_js( $id_site ); ?>']);
        var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
        g.async=true; g.src='<?php echo esc_url( $script_url ); ?>'; s.parentNode.insertBefore(g,s);
      })();
    </script>
    <!-- End Matomo Code -->
    <?php
}

/**
 * Generate noscript image tracker for users with JavaScript disabled.
 *
 * When AI bot tracking is enabled, uses recMode=2 (auto mode) so Matomo
 * automatically detects and categorizes AI bot requests from the pixel.
 *
 * @since 1.0.0
 * @param string $host                   Matomo host URL.
 * @param string $id_site                Site ID.
 * @param bool   $enable_ai_bot_tracking Whether AI bot tracking is enabled.
 * @return string HTML noscript block with image tracker.
 */
function omsk_get_noscript_tracker( $host, $id_site, $enable_ai_bot_tracking = false ) {
    // Build image tracker URL with parameters.
    $tracker_params = array(
        'idsite'      => absint( $id_site ),
        'rec'         => 1,
        'action_name' => wp_get_document_title(),
        'url'         => home_url( add_query_arg( array() ) ),
        'apiv'        => 1,
        'rand'        => wp_rand( 100000, 999999 ),
    );

    // Use auto mode (recMode=2) when AI bot tracking is enabled.
    // Matomo will auto-detect AI bots from User-Agent and track them separately.
    if ( $enable_ai_bot_tracking ) {
        $tracker_params['recMode'] = 2;
        $tracker_params['source']  = 'WordPress';
    }

    // Add referrer if available.
    if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
        $tracker_params['urlref'] = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
    }

    $tracker_url = add_query_arg( $tracker_params, trailingslashit( $host ) . 'matomo.php' );

    $html  = '<!-- Matomo Image Tracker -->' . "\n";
    $html .= '<noscript>' . "\n";
    $html .= sprintf(
        '<img referrerpolicy="no-referrer-when-downgrade" src="%s" style="border:0;position:absolute;left:-9999px;" alt="" />',
        esc_url( $tracker_url )
    ) . "\n";
    $html .= '</noscript>' . "\n";
    $html .= '<!-- End Matomo Image Tracker -->';

    return $html;
}

/**
 * Check if current user should be excluded from tracking.
 *
 * @since 1.0.0
 * @param array $excluded_roles Array of role keys to exclude.
 * @return bool True if user should be excluded, false otherwise.
 */
function omsk_should_exclude_user( $excluded_roles ) {
    // If no roles to exclude, don't exclude anyone.
    if ( empty( $excluded_roles ) ) {
        return false;
    }

    // If user is not logged in, don't exclude.
    if ( ! is_user_logged_in() ) {
        return false;
    }

    // Get current user.
    $user = wp_get_current_user();

    // Check if any of the user's roles are in the excluded list.
    foreach ( $user->roles as $role ) {
        if ( in_array( $role, $excluded_roles, true ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Get the singular human-readable label of a post type.
 *
 * Falls back to the post type slug if no label is set.
 *
 * @since 1.0.0
 * @param string $post_type Post type slug.
 * @return string|null Singular label or null if the post type is unknown.
 */
function omsk_get_post_type_label( $post_type ) {
    if ( empty( $post_type ) ) {
        return null;
    }

    $obj = get_post_type_object( $post_type );
    if ( ! $obj ) {
        return null;
    }

    if ( ! empty( $obj->labels->singular_name ) ) {
        return $obj->labels->singular_name;
    }

    return $post_type;
}

/**
 * Build the page context to push in the MTM dataLayer.
 *
 * Returns a flat associative array with page_type, any taxonomy terms
 * relevant to the current request (category, tag, custom taxonomies, author),
 * plus the current user_id/user_role when User ID tracking is enabled.
 *
 * @since 1.0.0
 * @param string|null $user_id   SHA256 hashed user email or null.
 * @param string|null $user_role Primary role of the logged-in user or null.
 * @return array Page context to push in _mtm.
 */
function omsk_get_page_context( $user_id = null, $user_role = null ) {
    $context = array();

    $context['locale']            = get_locale();
    $context['user_login_status'] = is_user_logged_in() ? 'logged_in' : 'logged_out';

    if ( is_front_page() ) {
        $context['page_type'] = 'home';
    } elseif ( is_home() ) {
        $context['page_type'] = 'blog';
    } elseif ( is_search() ) {
        $context['page_type'] = 'search';
    } elseif ( is_404() ) {
        $context['page_type'] = 'error_404';
    } elseif ( is_author() ) {
        $context['page_type'] = 'author';
        $author = get_queried_object();
        if ( $author && ! empty( $author->display_name ) ) {
            $context['author'] = $author->display_name;
        }
    } elseif ( is_post_type_archive() ) {
        $post_type = get_query_var( 'post_type' );
        if ( is_array( $post_type ) ) {
            $post_type = reset( $post_type );
        }
        $context['page_type'] = 'archive_' . sanitize_key( $post_type );
        $label = omsk_get_post_type_label( $post_type );
        if ( $label ) {
            $context['post_type'] = $label;
        }
    } elseif ( is_tax() || is_category() || is_tag() ) {
        $term = get_queried_object();
        if ( $term && ! empty( $term->taxonomy ) ) {
            $tax_key              = ( 'post_tag' === $term->taxonomy ) ? 'tag' : $term->taxonomy;
            $context['page_type'] = 'archive_' . sanitize_key( $tax_key );
            if ( ! empty( $term->name ) ) {
                $context[ $tax_key ] = $term->name;
            }
        }
    } elseif ( is_date() ) {
        $context['page_type'] = 'archive_date';
    } elseif ( is_archive() ) {
        $context['page_type'] = 'archive';
    } elseif ( is_singular() ) {
        $post_type = get_post_type();
        if ( $post_type ) {
            $context['page_type'] = $post_type;
            $label = omsk_get_post_type_label( $post_type );
            if ( $label ) {
                $context['post_type'] = $label;
            }

            $post_id = get_queried_object_id();
            if ( ! $post_id ) {
                $post_id = get_the_ID();
            }

            if ( $post_id ) {
                $context['post_id'] = (int) $post_id;

                $taxonomies = get_object_taxonomies( $post_type, 'objects' );
                foreach ( $taxonomies as $tax_slug => $tax_obj ) {
                    if ( 'post_format' === $tax_slug ) {
                        continue;
                    }
                    if ( empty( $tax_obj->public ) && empty( $tax_obj->publicly_queryable ) && empty( $tax_obj->show_ui ) ) {
                        continue;
                    }
                    $terms = wp_get_post_terms( $post_id, $tax_slug, array( 'fields' => 'names' ) );
                    if ( is_wp_error( $terms ) || empty( $terms ) ) {
                        continue;
                    }
                    $key = ( 'post_tag' === $tax_slug ) ? 'tag' : $tax_slug;
                    $context[ $key ] = implode( ', ', $terms );
                }
            }
        }
    }

    if ( $user_id ) {
        $context['user_id'] = $user_id;
    }
    if ( $user_role ) {
        $context['user_role'] = $user_role;
    }

    return $context;
}
