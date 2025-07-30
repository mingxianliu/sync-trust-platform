<template>
  <svg
    class="icon"
    :class="[`icon-${props.name}`, classes]"
    aria-hidden="true"
    :width="size"
    :height="size"
  >
    <use :xlink:href="`#icon-${props.name}`" />
  </svg>
</template>

<script setup>
const props = defineProps({
  name: {
    type: String,
    required: true,
  },
  size: {
    type: Number,
    default: 22,
  },
  classes: {
    type: String,
    default: '',
  },
});
</script>
<style lang="scss" scoped>
path,
circle,
rect,
ellipse,
line,
polyline,
polygon {
  fill: currentColor !important;
}
</style>
