<?php
$fetch_url = '&method=Actions.getPageUrls';
$fetch_url .= '&flat=1';
$fetch_url .= '&filter_limit=10';
$fetch_url .= '&period=range';
$fetch_url .= '&date=' . omsk_get_matomo_date();

$data = omsk_fetch_matomo_api( $fetch_url );

$values = array();
foreach ( $data as $value ) {
	$values[] = $value;
}
?>
<div class="postbox" style="margin-bottom: 0">
    <div class="inner">
        <table class="omsk-table">
            <thead>
            <tr>
                <th><?php _e( 'URL', 'openmost-site-kit' ); ?></th>
                <th><?php _e( 'Pageviews', 'openmost-site-kit' ); ?></th>
                <th><?php _e( 'Unique Pageviews', 'openmost-site-kit' ); ?></th>
                <th><?php _e( 'Bounce Rate', 'openmost-site-kit' ); ?></th>
                <th><?php _e( 'Session Duration', 'openmost-site-kit' ); ?></th>
            </tr>
            </thead>
            <tbody>
			<?php foreach ( $values as $value ): ?>
                <tr>
                    <td><?php echo esc_html($value->label) ?? '-'; ?></td>
                    <td><?php echo esc_html($value->nb_visits) ?? '-'; ?></td>
                    <td><?php echo esc_html($value->nb_visits) ?? '-'; ?></td>
                    <td><?php echo esc_html($value->bounce_rate) ?? '-'; ?></td>
                    <td><?php echo esc_html($value->nb_visits) ?? '-'; ?></td>
                </tr>
			<?php endforeach; ?>
            </tbody>
        </table>

    </div>
</div>