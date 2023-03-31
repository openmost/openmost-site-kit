<div class="postbox">
	<div class="postbox-header">
		<h2>Visits summary</h2>
	</div>
    <div class="inside">



        <div id="visits" style="width: 100%; height:300px;"></div>

    </div>
</div>

<?php
/*
$response = osk_fetch_matomo_api();
$indexes  = $values = array();
foreach ( json_decode( $response ) as $index => $value ) {
	$indexes[] = $index;
	$values[]  = isset( $value->nb_visits ) ? $value->nb_visits : 0;
}
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.4.2/echarts.min.js"></script>
<script>
    let dates = <?php echo json_encode( $indexes ); ?>;
    let values = <?php echo json_encode( $values ); ?>;

    document.addEventListener('DOMContentLoaded', function () {
        let el = document.getElementById('visits');
        let myChart = echarts.init(el);
        myChart.setOption({
            xAxis: {
                type: 'category',
                data: dates,
            },
            yAxis: {
                type: 'value'
            },
            series: [
                {
                    data: values,
                    type: 'line'
                }
            ]
        })
    });
</script>
*/ ?>