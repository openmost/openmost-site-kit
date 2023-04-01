<div class="wrap">

    <div class="heading-wrapper" style="display: flex; justify-content: space-between; align-items: center; padding-bottom: .5rem;">
        <h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <form action="?" method="GET">
            <input type="hidden" name="page" value="osk-matomo-dashboard">

            <label for="period">Period :</label>
            <select name="period" id="period" onchange="this.parentNode.submit()">
                <option value="day" <?php echo osk_get_matomo_period() === 'day' ? 'selected' : ''; ?>><?php _e( 'Day' ); ?></option>
                <option value="week" <?php echo osk_get_matomo_period() === 'week' ? 'selected' : ''; ?>><?php _e( 'Week' ); ?></option>
                <option value="month" <?php echo osk_get_matomo_period() === 'month' ? 'selected' : ''; ?>><?php _e( 'Month' ); ?></option>
                <option value="year" <?php echo osk_get_matomo_period() === 'year' ? 'selected' : ''; ?>><?php _e( 'Year' ); ?></option>
            </select>

        </form>
    </div>

    <div class="charts-wrapper">

        <div class="chart-wrapper">
			<?php require_once plugin_dir_path(__FILE__) . 'widgets/visits-summary.php'; ?>
        </div>

        <div class="chart-wrapper">
			<?php require_once plugin_dir_path(__FILE__) . 'widgets/channel-types.php'; ?>
        </div>

        <div class="chart-wrapper">
			<?php require_once plugin_dir_path(__FILE__) . 'widgets/pages.php'; ?>
        </div>

        <div class="chart-wrapper">
			<?php require_once plugin_dir_path(__FILE__) . 'widgets/performance.php'; ?>
        </div>

    </div>

</div>