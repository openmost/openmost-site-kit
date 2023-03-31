<template>
  <div class="wrap">
    <h1 class="wp-heading-inline">Dashboard</h1>

    <div class="charts-wrapper">

      <Postbox title="Visits Summary">
        <VisitsSummary/>
      </Postbox>

    </div>
  </div>
</template>
<script setup>
import VisitsSummary from "../components/charts/VisitsSummary.vue";
import Postbox from "../components/Postbox.vue";

const props = defineProps({
  host: {
    type: String,
    required: true,
  },
  idSite: {
    type: String,
    required: true,
  },
  tokenAuth: {
    type: String,
    required: true,
  }
});
</script>