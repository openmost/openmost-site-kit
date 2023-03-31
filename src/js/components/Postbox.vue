<template>
  <div class="postbox">
    <div class="postbox-header">
      <h2>Visits summary</h2>
    </div>
    <div class="inner">
      <slot/>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  title: {
    type: String,
    default: 'Title'
  }
});
</script>