<div class="postbox" style="margin-bottom: 0">
    <div class="inner">
        <div id="channel-types-chart" style="width: 100%; height: 400px;"></div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', async function () {

        let response = await fetchMatomoApi({
            'method': 'Referrers.getReferrerType',
            'period': 'range',
            'date': '<?php echo omsk_get_matomo_date(); ?>',
            'filter_limit': 100,
            'filter_truncate': 5,
            'format_metrics': 1,
            'expanded': 1,
        }).then(response => response.json());

        let data = Object.values(response.data).map(item => ({
            name: item.label,
            value: item.nb_visits,
        }));

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
                    data: data,
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