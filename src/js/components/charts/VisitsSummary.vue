<template>
  <div ref="chartRef" style="height: 350px;"></div>
</template>

<script setup>
import {ref, onMounted} from 'vue'
import * as echarts from 'echarts'
import {useFetchMatomoApi} from "../../composables/useFetchMatomoApi";

const chartRef = ref(null)
const chartData = ref([])

const fetchData = async () => {
  try {
    const data = await useFetchMatomoApi('')
    chartData.value = data;
    creatChart();
  } catch (error) {
    console.error(error)
  }
}

function creatChart() {
  const chart = echarts.init(chartRef.value)

  chart.setOption({
    xAxis: {
      type: 'category',
      data: Object.keys(chartData.value)
    },
    yAxis: {
      type: 'value'
    },
    series: [{
      data: Object.values(chartData.value).map(item => item.nb_visits),
      type: 'line'
    }]
  })
}

onMounted(() => {
  fetchData()
})
</script>