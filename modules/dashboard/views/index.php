<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php if ( osk_get_matomo_host() && osk_get_matomo_idsite() && osk_get_matomo_token_auth() ): ?>

        <form action="?" method="GET" style="float: right">
            <input type="hidden" name="page" value="<?php echo $_GET['page']; ?>">

            <label for="date">Date :</label>
            <select name="date" id="date" onchange="this.parentNode.submit()">
                <option value="last7" <?php echo osk_get_matomo_date() === 'last7' ? 'selected' : ''; ?>><?php _e( 'Last 7 days' ); ?></option>
                <option value="last14" <?php echo osk_get_matomo_date() === 'last14' ? 'selected' : ''; ?>><?php _e( 'Last 14 days' ); ?></option>
                <option value="last28" <?php echo osk_get_matomo_date() === 'last28' ? 'selected' : ''; ?>><?php _e( 'Last 28 days' ); ?></option>
                <option value="last90" <?php echo osk_get_matomo_date() === 'last90' ? 'selected' : ''; ?>><?php _e( 'Last 90 days' ); ?></option>
            </select>
        </form>

        <div class="charts-wrapper">

            <div class="chart-wrapper">
				<?php require_once plugin_dir_path( __FILE__ ) . 'widgets/visits-summary.php'; ?>
            </div>

            <div class="chart-wrapper">
				<?php require_once plugin_dir_path( __FILE__ ) . 'widgets/channel-types.php'; ?>
            </div>

            <div class="chart-wrapper">
				<?php require_once plugin_dir_path( __FILE__ ) . 'widgets/pages.php'; ?>
            </div>

            <div class="chart-wrapper">
				<?php require_once plugin_dir_path( __FILE__ ) . 'widgets/performance.php'; ?>
            </div>

        </div>
	<?php endif; ?>
</div>