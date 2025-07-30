<template>
  <q-avatar :size="`${props.size}px`">
    <q-img
      :src="props.path || picDefault"
      :placeholder-src="picDefault"
      ratio="1"
    />
  </q-avatar>
</template>

<script setup>
import picDefault from 'src/assets/img/no_img_default.svg';

const props = defineProps({
  path: {
    type: String,
    default: '',
  },
  size: {
    type: Number,
    default: 48,
  },
});
</script>

<style lang="scss" scoped></style>
