<?php
/**
 * Tracking Code Injection Module
 *
 * Handles both Classic Matomo tracking and Tag Manager (MTM) tracking.
 * - Classic tracking: Supports consent mode options (requireConsent, requireCookieConsent)
 * - Tag Manager: GDPR options are managed in MTM UI, so we only provide a dataLayer
 *   initial push with context (host, siteId, container, WP environment)
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

    $host           = isset( $options['omsk-matomo-host-field'] ) ? $options['omsk-matomo-host-field'] : '';
    $id_site        = isset( $options['omsk-matomo-idsite-field'] ) ? $options['omsk-matomo-idsite-field'] : '';
    $enable_classic = ! empty( $options['omsk-matomo-enable-classic-tracking-code-field'] );
    $enable_mtm     = ! empty( $options['omsk-matomo-enable-mtm-tracking-code-field'] );
    $id_container   = isset( $options['omsk-matomo-idcontainer-field'] ) ? $options['omsk-matomo-idcontainer-field'] : '';
    $excluded_roles = isset( $options['omsk-matomo-excluded-roles-field'] ) ? (array) $options['omsk-matomo-excluded-roles-field'] : array();

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
    echo omsk_get_noscript_tracker( $host, $id_site );
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
    $enable_userid_tracking  = ! empty( $options['omsk-matomo-enable-userid-tracking-field'] );
    $enable_heartbeat_timer  = ! empty( $options['omsk-matomo-enable-heartbeat-timer-field'] );
    $heartbeat_timer_delay   = isset( $options['omsk-matomo-heartbeat-timer-delay-field'] ) ? absint( $options['omsk-matomo-heartbeat-timer-delay-field'] ) : 15;

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
    $user_id = null;
    if ( $enable_userid_tracking ) {
        $user_id = omsk_get_hashed_user_id();
    }

    // Inject Tag Manager tracking code (recommended).
    if ( $enable_mtm && $id_container ) {
        omsk_inject_mtm_code( $cdn_host, $id_container, $host, $id_site, $enable_mtm_datalayer, $user_id );
    }

    // Inject classic tracking code (fallback) - only if MTM is not enabled.
    if ( $enable_classic && ! $enable_mtm ) {
        omsk_inject_classic_code( $host, $id_site, $plan, $consent_mode, $user_id, $enable_heartbeat_timer, $heartbeat_timer_delay );
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
 * Note: Consent mode is NOT included here because GDPR options are managed
 * directly in the Tag Manager UI. Instead, we provide a dataLayer initial
 * push with context information that can be used by MTM triggers.
 *
 * @since 1.0.0
 * @param string      $cdn_host         CDN host URL.
 * @param string      $id_container     Container ID.
 * @param string      $host             Matomo host URL.
 * @param string      $id_site          Site ID.
 * @param bool        $enable_datalayer Whether to push context to dataLayer.
 * @param string|null $user_id          SHA256 hashed user ID or null.
 * @return void
 */
function omsk_inject_mtm_code( $cdn_host, $id_container, $host, $id_site, $enable_datalayer = true, $user_id = null ) {
    $plan = omsk_get_matomo_plan();

    // Build script URL based on plan type.
    if ( 'cloud' === $plan ) {
        $script_url = $cdn_host . '/container_' . $id_container . '.js';
    } else {
        $script_url = $cdn_host . '/js/container_' . $id_container . '.js';
    }

    $wp_env = omsk_get_wp_environment();
    ?>
    <link rel="dns-prefetch" href="<?php echo esc_attr( $host ); ?>">
    <link rel="preload" href="<?php echo esc_attr( $script_url ); ?>" as="script">
    <!-- Matomo Tag Manager -->
    <script>
    var _mtm = window._mtm = window._mtm || [];
    <?php if ( $enable_datalayer ) : ?>
    _mtm.push({
        'matomo': {
            'host': '<?php echo esc_js( trailingslashit( $host ) ); ?>',
            'site_id': '<?php echo esc_js( $id_site ); ?>',
            'container_id': '<?php echo esc_js( $id_container ); ?>'
        },
        'wordpress': {
            'environment': '<?php echo esc_js( $wp_env ); ?>'<?php if ( $user_id ) : ?>,
            'user_id': '<?php echo esc_js( $user_id ); ?>'<?php endif; ?>
        }
    });
    <?php endif; ?>
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
 * @param string      $host                   Matomo host URL.
 * @param string      $id_site                Site ID.
 * @param string      $plan                   Plan type (cloud or on_premise).
 * @param string      $consent_mode           Consent mode (disabled, require_consent, require_cookie_consent).
 * @param string|null $user_id                SHA256 hashed user ID or null.
 * @param bool        $enable_heartbeat_timer Enable heartbeat timer for accurate time tracking.
 * @param int         $heartbeat_timer_delay  Heartbeat timer delay in seconds.
 * @return void
 */
function omsk_inject_classic_code( $host, $id_site, $plan, $consent_mode = 'disabled', $user_id = null, $enable_heartbeat_timer = false, $heartbeat_timer_delay = 15 ) {
    // Determine script URL based on plan type.
    if ( 'cloud' === $plan ) {
        $cdn_host   = omsk_get_matomo_cdn_host();
        $script_url = $cdn_host . '/matomo.js';
    } else {
        $script_url = trailingslashit( $host ) . 'matomo.js';
    }

    ?>
    <link rel="dns-prefetch" href="<?php echo esc_attr( $host ); ?>">
    <link rel="preload" href="<?php echo esc_attr( $script_url ); ?>" as="script">
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
      _paq.push(['trackPageView']);
      _paq.push(['enableLinkTracking']);
      (function() {
        var u="<?php echo esc_js( trailingslashit( $host ) ); ?>";
        _paq.push(['setTrackerUrl', u+'matomo.php']);
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
 * @since 1.0.0
 * @param string $host    Matomo host URL.
 * @param string $id_site Site ID.
 * @return string HTML noscript block with image tracker.
 */
function omsk_get_noscript_tracker( $host, $id_site ) {
    // Build image tracker URL with parameters.
    $tracker_params = array(
        'idsite'      => absint( $id_site ),
        'rec'         => 1,
        'action_name' => wp_get_document_title(),
        'url'         => home_url( add_query_arg( array() ) ),
        'apiv'        => 1,
        'rand'        => wp_rand( 100000, 999999 ),
    );

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
