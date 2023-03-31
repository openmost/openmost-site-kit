<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <div class="charts-wrapper">
        <div class="chart-wrapper">
			<?php require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/matomo-dashboard/views/widgets/visits-summary.php'; ?>
        </div>
        <div class="chart-wrapper">
			<?php require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/matomo-dashboard/views/widgets/channel-types.php'; ?>
        </div>

        <div class="chart-wrapper">
	        <?php require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/matomo-dashboard/views/widgets/pages.php'; ?>
        </div>

        <div class="chart-wrapper">
		    <?php require_once OPENMOSTSITEKIT_PLUGIN_DIR . 'modules/matomo-dashboard/views/widgets/performance.php'; ?>
        </div>

    </div>

</div>