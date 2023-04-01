<?php
$data = osk_fetch_matomo_api( '&method=Referrers.getReferrerType&filter_limit=100&filter_truncate=5&format_metrics=1&expanded=1&period=range' );

$values = array();
foreach ( $data as $value ) {
	$values[] = array(
		'name'  => $value->label,
		'value' => $value->nb_visits,
	);
}
?>
<div class="postbox" style="margin-bottom: 0">
    <div class="inner">
        <div id="channel-types-chart" style="width: 100%; height: 400px;"></div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {

        let el = document.getElementById('channel-types-chart');

        let chart = echarts.init(el);
        chart.setOption({
            tooltip: {
                trigger: 'item'
            },
            series: [
                {
                    name: 'Access From',
                    type: 'pie',
                    radius: '50%',
                    data: <?php echo json_encode( $values ); ?>,
                    emphasis: {
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    }
                }
            ]
        })
    });
</script>