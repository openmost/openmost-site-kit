<?php if ( osk_get_matomo_host() && osk_get_matomo_idsite() && osk_get_matomo_token_auth() ): ?>
    <div class="post-type-charts-wrapper">

        <div class="chart-wrapper">
			<?php require_once plugin_dir_path( __FILE__ ) . 'widgets/visits-summary.php'; ?>
        </div>

    </div>
<?php endif; ?>
