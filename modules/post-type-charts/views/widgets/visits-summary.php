<?php
$page_url = get_the_permalink(get_the_ID());

$fetch_url = '&method=API.get';
$fetch_url .= '&period=day';
$fetch_url .= '&date=last30';
$fetch_url .= '&showColumns=nb_visits,nb_pageviews';
$fetch_url .= '&segment=pageUrl==' . $page_url;

$data = osk_fetch_matomo_api( $fetch_url );

$indexes      = array();
$nb_visits    = array();
$nb_pageviews = array();

foreach ( $data as $index => $value ) {
	$indexes[]      = $index;
	$nb_visits[]    = $value->nb_visits ?? 0;
	$nb_pageviews[] = $value->nb_pageviews ?? 0;
}
?>
<div id="visits-summary-chart" style="width: 1570px; height: 400px;"></div>
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
                boundaryGap: false,
                data: <?php echo json_encode( $indexes ); ?>
            },
            yAxis: {
                type: 'value'
            },
            series: [
                {
                    name: '<?php _e('Visits', 'osk'); ?>',
                    data: <?php echo json_encode( $nb_visits ); ?>,
                    type: 'line'
                },
                {
                    name: '<?php _e('Pageviews', 'osk'); ?>',
                    data: <?php echo json_encode( $nb_pageviews ); ?>,
                    type: 'line'
                }
            ]
        })
    });
</script>