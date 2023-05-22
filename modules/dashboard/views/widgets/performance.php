<div class="postbox" style="margin-bottom: 0">
    <div class="inner">
        <div id="performance-chart" style="width: 100%; height: 600px;"></div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', async function () {

        let response = await fetchMatomoApi({
            'method': 'PagePerformance.get',
            'period': 'day',
            'date': '<?php echo omsk_get_matomo_date(); ?>',
            'filter_limit': 100,
            'format_metrics': 0,
            'expanded': 1,

        }).then(response => response.json());

        let data = {
            'avg_time_network': [],
            'avg_time_server': [],
            'avg_time_transfer': [],
            'avg_time_dom_processing': [],
            'avg_time_dom_completion': [],
            'avg_page_load_time': [],
            'avg_time_on_load': [],
        };

        Object.values(response.data).map(item => {
            Object.keys(item).map((index, value) => {
                data[index].push(item[index] ?? 0)
            })
        });

        delete data.avg_time_on_load;

        let series = [];
        Object.keys(data).map(i => {
            series.push({
                name: i,
                type: 'bar',
                stack: 'total',
                label: {
                    show: true,
                },
                emphasis: {
                    focus: 'series'
                },
                data: data[i] ?? 0,
            },)
        })


        // Render chart
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
                data: Object.keys(response.data)
            },
            yAxis: {
                type: 'value'
            },
            series: series
        })
    })
    ;
</script>