<?php
$data = fetch_matomo_api( '&method=PagePerformance.get&filter_limit=100&format_metrics=0&expanded=1&period=day&date=last30' );

$indexes = array();
$values  = array();


foreach ( $data as $index => $value ) {
	$indexes[] = $index;

	foreach ( $value as $i => $val ) {
		$values[ $i ][] = $val;
	}
}
?>
<div class="postbox" style="margin-bottom: 0">
    <div class="inner">
        <div id="performance-chart" style="width: 100%; height: 600px;"></div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {

        let el = document.getElementById('performance-chart');

        let chart = echarts.init(el);
        chart.setOption({
            xAxis: {
                type: 'category',
                data: <?php echo json_encode( $indexes ); ?>
            },
            yAxis: {
                type: 'value'
            },
            series: [
				<?php foreach ($values as $index => $value): ?>
                {
                    name: '<?php echo $index; ?>',
                    type: 'bar',
                    stack: 'total',
                    label: {
                        show: true,
                    },
                    emphasis: {
                        focus: 'series'
                    },
                    data: <?php echo json_encode( $values[ $index ] ); ?>,
                },
				<?php endforeach; ?>
            ]
        })
    });
</script>