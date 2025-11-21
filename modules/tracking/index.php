<?php
/**
 * Tracking Code Injection Module
 * Handles both Classic Matomo tracking and Tag Manager (MTM) tracking
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Inject tracking code in the head
 */
add_action('wp_head', 'omsk_inject_tracking_code', 10);

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

    if (!$host || !$idSite) {
        return;
    }

    // Get plan type (cloud or on-premise)
    $plan = omsk_get_matomo_plan();
    $cdnHost = omsk_get_matomo_cdn_host();

    // Inject Tag Manager tracking code (recommended)
    if ($enableMtm && $idContainer) {
        omsk_inject_mtm_code($cdnHost, $idContainer);
    }

    // Inject classic tracking code (fallback)
    if ($enableClassic && !$enableMtm) {
        omsk_inject_classic_code($host, $idSite, $plan);
    }
}

/**
 * Inject Matomo Tag Manager (MTM) code
 */
function omsk_inject_mtm_code($cdnHost, $idContainer)
{
    $plan = omsk_get_matomo_plan();
    $host = omsk_get_matomo_host();

    // Build script URL based on plan type
    if ($plan === 'cloud') {
        $scriptUrl = $cdnHost . '/container_' . $idContainer . '.js';
    } else {
        $scriptUrl = $cdnHost . '/js/container_' . $idContainer . '.js';
    }
    ?>
    <link rel="dns-prefetch" href="<?php echo esc_attr($host); ?>">
    <link rel="preload" href="<?php echo esc_attr($scriptUrl); ?>" as="script">
    <!-- Matomo Tag Manager -->
    <script>
    var _mtm = window._mtm = window._mtm || [];
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
 */
function omsk_inject_classic_code($host, $idSite, $plan)
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
