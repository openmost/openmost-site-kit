<?php

$fetch_url = '&method=Referrers.getReferrerType';
$fetch_url .= '&filter_limit=100';
$fetch_url .= '&filter_truncate=5';
$fetch_url .= '&format_metrics=1';
$fetch_url .= '&expanded=1';
$fetch_url .= '&period=range';
$fetch_url .= '&date=' . omsk_get_matomo_date();

$data = omsk_fetch_matomo_api( $fetch_url );

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