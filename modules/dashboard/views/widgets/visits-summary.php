<?php

$fetch_url = '&method=VisitsSummary.getVisits';
$fetch_url .= '&period=day';
$fetch_url .= '&date=' . omsk_get_matomo_date();

$data = omsk_fetch_matomo_api( $fetch_url );

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
            tooltip: {
                trigger: 'axis'
            },
            grid: {
                top: '32px',
                left: '16px',
                right: '32px',
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
                {
                    name: '<?php _e('Visits', 'openmost-site-kit'); ?>',
                    data: <?php echo json_encode( $values ); ?>,
                    type: 'line'
                }
            ]
        })
    });
</script>