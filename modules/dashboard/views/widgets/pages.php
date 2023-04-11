<?php
$fetch_url = '&method=Actions.getPageUrls';
$fetch_url .= '&flat=1';
$fetch_url .= '&filter_limit=10';
$fetch_url .= '&period=range';
$fetch_url .= '&date=' . osk_get_matomo_date();

$data = osk_fetch_matomo_api( $fetch_url );

$values = array();
foreach ( $data as $value ) {
	$values[] = $value;
}
?>
<div class="postbox" style="margin-bottom: 0">
    <div class="inner">
        <table class="osk-table">
            <thead>
            <tr>
                <th>URL</th>
                <th>Pageviews</th>
                <th>Unique Pageviews</th>
                <th>Bounce Rate</th>
                <th>Session Duration</th>
            </tr>
            </thead>
            <tbody>
			<?php foreach ( $values as $value ): ?>
                <tr>
                    <td><?php echo $value->label ?? '-'; ?></td>
                    <td><?php echo $value->nb_visits ?? '-'; ?></td>
                    <td><?php echo $value->nb_visits ?? '-'; ?></td>
                    <td><?php echo $value->bounce_rate ?? '-'; ?></td>
                    <td><?php echo $value->nb_visits ?? '-'; ?></td>
                </tr>
			<?php endforeach; ?>
            </tbody>
        </table>

    </div>
</div>