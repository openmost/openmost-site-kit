<?php
$data = fetch_matomo_api( '&method=Actions.getPageUrls&period=month&date=today&flat=1&filter_limit=10' );

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