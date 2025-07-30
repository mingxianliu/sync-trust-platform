<template>
  <div>
    <canvas ref="canvas"></canvas>
  </div>
</template>
<script setup>
import { ref, onMounted, watch } from 'vue';
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

const props = defineProps({
  chartData: { type: Object, required: true },
});
const canvas = ref(null);
let chartInstance = null;

const renderChart = () => {
  if (chartInstance) chartInstance.destroy();
  chartInstance = new Chart(canvas.value, {
    type: 'pie',
    data: props.chartData,
    options: {
      responsive: true,
      plugins: { legend: { display: true } },
    },
  });
};
onMounted(renderChart);
watch(() => props.chartData, renderChart, { deep: true });
</script>
