<template>
  <div ref="el" style="min-height: 350px; width: 100%"></div>
</template>

<script setup>
import {ref, computed, watch, onMounted} from 'vue';
import * as echarts from 'echarts';

const props = defineProps({
  option: {
    type: Object,
    required: true,
  }
})
const defaultOption = ref({
  tooltip: {
    trigger: 'axis'
  },
  xAxis: {
    data: ['shirt', 'cardigan', 'chiffon', 'pants', 'heels', 'socks']
  },
  yAxis: {},
  series: [
    {
      name: 'vistis',
      type: 'line',
      data: [5, 20, 36, 10, 10, 20]
    }
  ]
});
const el = ref();
const chart = ref();
const option = computed(() => Object.assign({}, defaultOption.value, props.option));
watch(option, () => updateChart())
onMounted(() => {
  chart.value = echarts.init(el.value);
  updateChart();
});

function updateChart() {
  chart.value.setOption(option.value);
}
</script>