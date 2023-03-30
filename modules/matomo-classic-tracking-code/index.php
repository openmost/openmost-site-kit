<?php

function osk_add_matomo_classic_tracking_code() {
	$options = get_option( 'osk-settings' );
	$enabled = isset( $options['osk-matomo-enable-classic-tracking-code-field'] ) ? $options['osk-matomo-enable-classic-tracking-code-field'] : '';
	$host    = isset( $options['osk-matomo-host-field'] ) ? $options['osk-matomo-host-field'] : '';
	$id_site = isset( $options['osk-matomo-idsite-field'] ) ? $options['osk-matomo-idsite-field'] : '';

	if ( $enabled && $host && $id_site ): ?>

		<!-- Matomo -->
		<script>
            var _paq = window._paq = window._paq || [];
            /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
            _paq.push(["setExcludedQueryParams", ["\/.*\/"]]);
            _paq.push(['trackPageView']);
            _paq.push(['enableLinkTracking']);
            (function () {
                var u = "<?php echo esc_attr( $host ); ?>";
                _paq.push(['setTrackerUrl', u + 'matomo.php']);
                _paq.push(['setSiteId', '<?php echo esc_attr( $id_site ); ?>']);
                var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
                g.async = true;
                g.src = u + 'matomo.js';
                s.parentNode.insertBefore(g, s);
            })();
		</script>
		<!-- End Matomo Code -->

	<?php endif;
}

add_action( 'wp_head', 'osk_add_matomo_classic_tracking_code', 17 );