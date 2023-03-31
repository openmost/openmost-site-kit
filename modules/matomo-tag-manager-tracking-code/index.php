<?php

function osk_add_matomo_tag_manager_tracking_code() {
	$options      = get_option( 'osk-settings' );
	$enabled      = isset( $options['osk-matomo-enable-mtm-tracking-code-field'] ) ? $options['osk-matomo-enable-mtm-tracking-code-field'] : '';
	$host         = isset( $options['osk-matomo-host-field'] ) ? $options['osk-matomo-host-field'] : '';
	$id_container = isset( $options['osk-matomo-idcontainer-field'] ) ? $options['osk-matomo-idcontainer-field'] : '';

	if ( $enabled && $host && $id_container ): ?>

        <!-- Matomo Tag Manager -->
        <script>
            var _mtm = window._mtm = window._mtm || [];
            _mtm.push({'mtm.startTime': (new Date().getTime()), 'event': 'mtm.Start'});
            var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
            g.async = true;
            g.src = '<?php echo esc_attr($host); ?>/js/container_<?php echo esc_attr($id_container); ?>.js';
            s.parentNode.insertBefore(g, s);
        </script>
        <!-- End Matomo Tag Manager -->

	<?php endif;
}

add_action( 'wp_head', 'osk_add_matomo_tag_manager_tracking_code', 17 );
