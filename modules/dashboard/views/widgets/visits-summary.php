<?php
$data = osk_fetch_matomo_api( '&method=VisitsSummary.getVisits&filter_limit=100&format_metrics=1&expanded=1&period=day' );

$indexes = array();
$values  = array();

foreach ( $data as $index => $value ) {
	$indexes[] = $index;
	$values[]  = $value ?? 0;
}
?>
<div class="postbox" style="margin-bottom: 0">
    <div class="inner">
        <div id="visits-summary-chart" style="width: 100%; height: 400px;"></div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {

        let el = document.getElementById('visits-summary-chart');

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
                {
                    data: <?php echo json_encode( $values ); ?>,
                    type: 'line'
                }
            ]
        })
    });
</script>