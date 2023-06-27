<?php

function omsk_add_matomo_tag_manager_tracking_code() {
	$options      = get_option( 'omsk-settings' );
	$enabled      = isset( $options['omsk-matomo-enable-mtm-tracking-code-field'] ) ? $options['omsk-matomo-enable-mtm-tracking-code-field'] : '';
	$host         = isset( $options['omsk-matomo-host-field'] ) ? $options['omsk-matomo-host-field'] : '';
	$id_container = isset( $options['omsk-matomo-idcontainer-field'] ) ? $options['omsk-matomo-idcontainer-field'] : '';

	if ( $enabled && $host && $id_container ): ?>
        <link rel="dns-prefetch" href="<?php echo esc_attr($host); ?>">
        <link rel="preload" href="<?php echo omsk_get_matomo_cdn_host(); ?>/js/container_<?php echo esc_attr($id_container); ?>.js" as="script">
		<!-- Matomo Tag Manager -->
		<script>
            var _mtm = window._mtm = window._mtm || [];
            _mtm.push({'mtm.startTime': (new Date().getTime()), 'event': 'mtm.Start'});
            var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
            g.async = true;
            g.src = '<?php echo omsk_get_matomo_cdn_host(); ?>/js/container_<?php echo esc_attr($id_container); ?>.js';
            s.parentNode.insertBefore(g, s);
		</script>
		<!-- End Matomo Tag Manager -->


    <?php echo omsk_get_matomo_plan(); ?>

	<?php endif;
}

add_action( 'wp_head', 'omsk_add_matomo_tag_manager_tracking_code', 17 );
