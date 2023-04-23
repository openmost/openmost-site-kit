<?php

$fetch_url = '&method=PagePerformance.get';
$fetch_url .= '&filter_limit=100';
$fetch_url .= '&format_metrics=0';
$fetch_url .= '&expanded=1';
$fetch_url .= '&period=day';
$fetch_url .= '&date=' . omsk_get_matomo_date();

$data = omsk_fetch_matomo_api( $fetch_url );

$indexes = array();
$values  = array();

$metrics = [
	'avg_time_network',
	'avg_time_server',
	'avg_time_transfer',
	'avg_time_dom_processing',
	'avg_time_dom_completion',
	'avg_page_load_time',
];

foreach ( $data as $index => $value ) {
	$indexes[] = $index;

	foreach ( $metrics as $metric ) {
		$value                       = (array) $value;
		$values[ $metric ][ $index ] = count( $value ) ? $value[ $metric ] : 0;
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
            tooltip: {
                trigger: 'axis'
            },
            grid: {
                top: '32px',
                left: '16px',
                right: '16px',
                bottom: '16px',
                containLabel: true
            },
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
                    data: <?php echo json_encode( array_values( $values[ $index ] ) ); ?>,
                },
				<?php endforeach; ?>
            ]
        })
    });
</script>