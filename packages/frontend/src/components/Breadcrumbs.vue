<template>
  <template v-if="usePage">
    <div class="title">
      <p class="q-ma-none">{{ pageName }}</p>
    </div>
  </template>
  <template v-else>
    <div class="breadcrumbs">
      <span class="parent">{{ routerParent }}</span>
      <span class="child">{{ routeChild }}</span>
    </div>
  </template>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

// console.log('router', router);
const usePage = ref(true);
const routerParent = computed(() => {
  return route.name;
});
const routeChild = computed(() => {
  return 'Patient';
});
const pageName = computed(() => {
  return t(router.currentRoute.value.meta.title);
});
</script>

<style lang="scss" scoped>
.breadcrumbs {
  font-weight: bold;
  font-size: 1rem;
  color: $grey;
  .parent {
    &::after {
      content: '/';
      margin: 0 12px;
      font-size: 0.5rem;
      color: #333;
    }
  }
  .child {
    color: #333;
  }
}
.title {
  font-size: 1.625rem;
  font-weight: bold;
}
</style>
