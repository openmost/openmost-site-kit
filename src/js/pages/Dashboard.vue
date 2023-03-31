<template>
  <div class="wrap">

    <h1 class="wp-heading-inline">Dashboard</h1>

    <div class="postbox">
      <div class="postbox-header">
        <h2>Visits summary</h2>
      </div>
      <div class="inner">
        <VisitsSummary/>
      </div>
    </div>


  </div>
</template>
<script setup>
import VisitsSummary from "../components/charts/VisitsSummary.vue";
</script>