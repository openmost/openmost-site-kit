<?php
/**
 * Tracking Code Injection Module
 * Handles both Classic Matomo tracking and Tag Manager (MTM) tracking
 *
 * Logic:
 * - Classic tracking: Supports consent mode options (requireConsent, requireCookieConsent)
 * - Tag Manager: GDPR options are managed in MTM UI, so we only provide a dataLayer
 *   initial push with context (host, siteId, container, WP environment)
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Inject tracking code in the head
 */
add_action('wp_head', 'omsk_inject_tracking_code', 10);

/**
 * Inject noscript image tracker at the beginning of body
 */
add_action('wp_body_open', 'omsk_inject_noscript_tracker', 1);

function omsk_inject_noscript_tracker()
{
    $options = get_option('omsk-settings');

    if (!$options) {
        return;
    }

    $host = isset($options['omsk-matomo-host-field']) ? $options['omsk-matomo-host-field'] : '';
    $idSite = isset($options['omsk-matomo-idsite-field']) ? $options['omsk-matomo-idsite-field'] : '';
    $enableClassic = isset($options['omsk-matomo-enable-classic-tracking-code-field']) ? $options['omsk-matomo-enable-classic-tracking-code-field'] : false;
    $enableMtm = isset($options['omsk-matomo-enable-mtm-tracking-code-field']) ? $options['omsk-matomo-enable-mtm-tracking-code-field'] : false;
    $idContainer = isset($options['omsk-matomo-idcontainer-field']) ? $options['omsk-matomo-idcontainer-field'] : '';
    $excludedRoles = isset($options['omsk-matomo-excluded-roles-field']) ? (array) $options['omsk-matomo-excluded-roles-field'] : array();

    if (!$host || !$idSite) {
        return;
    }

    // Check if any tracking is enabled
    $trackingEnabled = ($enableMtm && $idContainer) || ($enableClassic && !$enableMtm);
    if (!$trackingEnabled) {
        return;
    }

    // Check if current user should be excluded from tracking
    if (omsk_should_exclude_user($excludedRoles)) {
        return;
    }

    echo omsk_get_noscript_tracker($host, $idSite);
}

function omsk_inject_tracking_code()
{
    $options = get_option('omsk-settings');

    if (!$options) {
        return;
    }

    $host = isset($options['omsk-matomo-host-field']) ? $options['omsk-matomo-host-field'] : '';
    $idSite = isset($options['omsk-matomo-idsite-field']) ? $options['omsk-matomo-idsite-field'] : '';
    $idContainer = isset($options['omsk-matomo-idcontainer-field']) ? $options['omsk-matomo-idcontainer-field'] : '';
    $enableClassic = isset($options['omsk-matomo-enable-classic-tracking-code-field']) ? $options['omsk-matomo-enable-classic-tracking-code-field'] : false;
    $enableMtm = isset($options['omsk-matomo-enable-mtm-tracking-code-field']) ? $options['omsk-matomo-enable-mtm-tracking-code-field'] : false;
    $excludedRoles = isset($options['omsk-matomo-excluded-roles-field']) ? (array) $options['omsk-matomo-excluded-roles-field'] : array();
    // Consent mode only applies to classic tracking code
    $consentMode = isset($options['omsk-matomo-consent-mode-field']) ? $options['omsk-matomo-consent-mode-field'] : 'disabled';
    // MTM dataLayer push option
    $enableMtmDataLayer = isset($options['omsk-matomo-enable-mtm-datalayer-field']) ? $options['omsk-matomo-enable-mtm-datalayer-field'] : true;
    // User ID tracking option
    $enableUserIdTracking = isset($options['omsk-matomo-enable-userid-tracking-field']) ? $options['omsk-matomo-enable-userid-tracking-field'] : false;
    // Heartbeat timer options (classic only)
    $enableHeartBeatTimer = isset($options['omsk-matomo-enable-heartbeat-timer-field']) ? $options['omsk-matomo-enable-heartbeat-timer-field'] : false;
    $heartBeatTimerDelay = isset($options['omsk-matomo-heartbeat-timer-delay-field']) ? absint($options['omsk-matomo-heartbeat-timer-delay-field']) : 15;

    if (!$host || !$idSite) {
        return;
    }

    // Check if current user should be excluded from tracking
    if (omsk_should_exclude_user($excludedRoles)) {
        return;
    }

    // Get plan type (cloud or on-premise)
    $plan = omsk_get_matomo_plan();
    $cdnHost = omsk_get_matomo_cdn_host();

    // Get User ID if tracking is enabled
    $userId = null;
    if ($enableUserIdTracking) {
        $userId = omsk_get_hashed_user_id();
    }

    // Inject Tag Manager tracking code (recommended)
    if ($enableMtm && $idContainer) {
        omsk_inject_mtm_code($cdnHost, $idContainer, $host, $idSite, $enableMtmDataLayer, $userId);
    }

    // Inject classic tracking code (fallback) - only if MTM is not enabled
    if ($enableClassic && !$enableMtm) {
        omsk_inject_classic_code($host, $idSite, $plan, $consentMode, $userId, $enableHeartBeatTimer, $heartBeatTimerDelay);
    }
}

/**
 * Get SHA256 hashed user ID from logged-in user's email
 *
 * @return string|null SHA256 hash of user email or null if not logged in
 */
function omsk_get_hashed_user_id()
{
    if (!is_user_logged_in()) {
        return null;
    }

    $user = wp_get_current_user();
    if (!$user || empty($user->user_email)) {
        return null;
    }

    // Return SHA256 hash of email (lowercase for consistency)
    return hash('sha256', strtolower($user->user_email));
}

/**
 * Get WordPress environment type
 *
 * @return string Environment type (production, staging, development, local)
 */
function omsk_get_wp_environment()
{
    // Check for wp_get_environment_type function (WP 5.5+)
    if (function_exists('wp_get_environment_type')) {
        return wp_get_environment_type();
    }

    // Fallback: Check WP_ENVIRONMENT_TYPE constant
    if (defined('WP_ENVIRONMENT_TYPE')) {
        return WP_ENVIRONMENT_TYPE;
    }

    // Default to production
    return 'production';
}

/**
 * Inject Matomo Tag Manager (MTM) code
 *
 * Note: Consent mode is NOT included here because GDPR options are managed
 * directly in the Tag Manager UI. Instead, we provide a dataLayer initial
 * push with context information that can be used by MTM triggers.
 *
 * @param string $cdnHost CDN host URL
 * @param string $idContainer Container ID
 * @param string $host Matomo host URL
 * @param string $idSite Site ID
 * @param bool $enableDataLayer Whether to push context to dataLayer
 * @param string|null $userId SHA256 hashed user ID or null
 */
function omsk_inject_mtm_code($cdnHost, $idContainer, $host, $idSite, $enableDataLayer = true, $userId = null)
{
    $plan = omsk_get_matomo_plan();

    // Build script URL based on plan type
    if ($plan === 'cloud') {
        $scriptUrl = $cdnHost . '/container_' . $idContainer . '.js';
    } else {
        $scriptUrl = $cdnHost . '/js/container_' . $idContainer . '.js';
    }

    $wpEnv = omsk_get_wp_environment();
    ?>
    <link rel="dns-prefetch" href="<?php echo esc_attr($host); ?>">
    <link rel="preload" href="<?php echo esc_attr($scriptUrl); ?>" as="script">
    <!-- Matomo Tag Manager -->
    <script>
    var _mtm = window._mtm = window._mtm || [];
    <?php if ($enableDataLayer) : ?>
    // Context dataLayer push for MTM triggers
    _mtm.push({
        'matomo': {
            'host': '<?php echo esc_js(trailingslashit($host)); ?>',
            'site_id': '<?php echo esc_js($idSite); ?>',
            'container_id': '<?php echo esc_js($idContainer); ?>'
        },
        'wordpress': {
            'environment': '<?php echo esc_js($wpEnv); ?>'<?php if ($userId) : ?>,
            'user_id': '<?php echo esc_js($userId); ?>'<?php endif; ?>
        }
    });
    <?php endif; ?>
    _mtm.push({'mtm.startTime': (new Date().getTime()), 'event': 'mtm.Start'});
    (function() {
      var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
      g.async=true; g.src='<?php echo esc_url($scriptUrl); ?>'; s.parentNode.insertBefore(g,s);
    })();
    </script>
    <!-- End Matomo Tag Manager -->
    <?php
}

/**
 * Inject classic Matomo tracking code
 *
 * This code supports consent mode options for GDPR compliance.
 *
 * @param string $host Matomo host URL
 * @param string $idSite Site ID
 * @param string $plan Plan type (cloud or on_premise)
 * @param string $consentMode Consent mode (disabled, require_consent, require_cookie_consent)
 * @param string|null $userId SHA256 hashed user ID or null
 * @param bool $enableHeartBeatTimer Enable heartbeat timer for accurate time tracking
 * @param int $heartBeatTimerDelay Heartbeat timer delay in seconds
 */
function omsk_inject_classic_code($host, $idSite, $plan, $consentMode = 'disabled', $userId = null, $enableHeartBeatTimer = false, $heartBeatTimerDelay = 15)
{
    // Determine script URL based on plan type
    if ($plan === 'cloud') {
        $cdnHost = omsk_get_matomo_cdn_host();
        $scriptUrl = $cdnHost . '/matomo.js';
    } else {
        $scriptUrl = $host . '/matomo.js';
    }

    ?>
    <link rel="dns-prefetch" href="<?php echo esc_attr($host); ?>">
    <link rel="preload" href="<?php echo esc_attr($scriptUrl); ?>" as="script">
    <!-- Matomo -->
    <script>
      var _paq = window._paq = window._paq || [];
      <?php if ($consentMode === 'require_consent') : ?>
      _paq.push(['requireConsent']);
      <?php elseif ($consentMode === 'require_cookie_consent') : ?>
      _paq.push(['requireCookieConsent']);
      <?php endif; ?>
      <?php if ($userId) : ?>
      _paq.push(['setUserId', '<?php echo esc_js($userId); ?>']);
      <?php endif; ?>
      <?php if ($enableHeartBeatTimer) : ?>
      _paq.push(['enableHeartBeatTimer', <?php echo absint($heartBeatTimerDelay); ?>]);
      <?php endif; ?>
      /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
      _paq.push(['trackPageView']);
      _paq.push(['enableLinkTracking']);
      (function() {
        var u="<?php echo esc_js(trailingslashit($host)); ?>";
        _paq.push(['setTrackerUrl', u+'matomo.php']);
        _paq.push(['setSiteId', '<?php echo esc_js($idSite); ?>']);
        var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
        g.async=true; g.src='<?php echo esc_url($scriptUrl); ?>'; s.parentNode.insertBefore(g,s);
      })();
    </script>
    <!-- End Matomo Code -->
    <?php
}

/**
 * Generate noscript image tracker for users with JavaScript disabled
 *
 * @param string $host Matomo host URL
 * @param string $idSite Site ID
 * @return string HTML noscript block with image tracker
 */
function omsk_get_noscript_tracker($host, $idSite)
{
    // Build image tracker URL with parameters
    $tracker_params = array(
        'idsite'      => $idSite,
        'rec'         => 1,
        'action_name' => wp_get_document_title(),
        'url'         => home_url(add_query_arg(array())),
        'apiv'        => 1,
        'rand'        => wp_rand(100000, 999999),
    );

    // Add referrer if available
    if (!empty($_SERVER['HTTP_REFERER'])) {
        $tracker_params['urlref'] = esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER']));
    }

    $tracker_url = add_query_arg($tracker_params, trailingslashit($host) . 'matomo.php');

    $html = '<!-- Matomo Image Tracker -->' . "\n";
    $html .= '<noscript>' . "\n";
    $html .= sprintf(
        '<img referrerpolicy="no-referrer-when-downgrade" src="%s" style="border:0;position:absolute;left:-9999px;" alt="" />',
        esc_url($tracker_url)
    ) . "\n";
    $html .= '</noscript>' . "\n";
    $html .= '<!-- End Matomo Image Tracker -->';

    return $html;
}

/**
 * Check if current user should be excluded from tracking
 *
 * @param array $excluded_roles Array of role keys to exclude
 * @return bool True if user should be excluded, false otherwise
 */
function omsk_should_exclude_user($excluded_roles)
{
    // If no roles to exclude, don't exclude anyone
    if (empty($excluded_roles)) {
        return false;
    }

    // If user is not logged in, don't exclude
    if (!is_user_logged_in()) {
        return false;
    }

    // Get current user
    $user = wp_get_current_user();

    // Check if any of the user's roles are in the excluded list
    foreach ($user->roles as $role) {
        if (in_array($role, $excluded_roles, true)) {
            return true;
        }
    }

    return false;
}
