<template>
  <q-layout view="hHh Lpr lFf">
    <q-header>
      <q-toolbar class="flex justify-between header-bar">
        <div class="row items-center">
          <span class="app-title">SyncONE</span>
          <span class="app-subtitle q-ml-lg">智慧建築區塊鏈平台</span>
        </div>
        <Header />
      </q-toolbar>
    </q-header>

    <q-drawer
      v-model="drawer"
      :width="270"
      :breakpoint="500"
      show-if-above
      bordered
      class="column justify-between bg-img"
    >
      <q-list>
        <q-item-label header class="text-center q-pa-none"> </q-item-label>
        <MenuList />
      </q-list>
    </q-drawer>

    <q-page-container>
      <q-page class="main-page">
        <router-view />
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { ref } from 'vue';
// import { useRouter, useRoute } from 'vue-router';
import MenuList from 'components/MenuList';
import Header from 'components/Header';

// const route = useRoute();
// const router = useRouter();

const drawer = ref(false);
</script>

<style lang="scss" scoped>
.header-bar {
  height: 64px;
  color: #fff;
  background: $primary;
  padding: 0 30px;
  box-shadow: 0 2px 8px rgba(35, 68, 58, 0.08);
}

.app-title {
  font-size: 2.2rem;
  font-weight: bold;
  letter-spacing: 2px;
  color: #fff;
}

.app-subtitle {
  font-size: 1.1rem;
  color: #bfc9c4;
  font-weight: 500;
  letter-spacing: 1px;
  margin-top: 8px;
}

:deep(.bg-img) {
  background: linear-gradient(135deg, $primary 80%, $secondary 100%);
}

.main-page {
  background: #f4f6f5;
  padding: 40px 40px 0 40px;
  min-height: calc(100vh - 64px);
}

:deep(.q-drawer) {
  box-shadow: 2px 0 8px rgba(35, 68, 58, 0.08);
}

.logo-img{
  padding: 10px 0;
  height: 100%;
  box-sizing: border-box;
}
</style>
