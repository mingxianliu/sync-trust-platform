<template>
  <router-view />
</template>

<script setup>
import { computed, watch, onMounted } from 'vue';
import { useStore } from 'vuex';
import { Loading } from 'quasar';
import * as pkg from 'app/package';

const store = useStore();
const { version } = pkg;

const isApiLoading = computed(() => {
  return store.getters['app/isApiLoading'];
});

watch(isApiLoading, (newVal) => {
  if (newVal) {
    Loading.show();
    return;
  }
  Loading.hide();
});

onMounted(() => {
  const metaVersion = document.querySelector('meta[name="version"]');
  metaVersion.setAttribute('content', `v${version}`);
});
</script>
