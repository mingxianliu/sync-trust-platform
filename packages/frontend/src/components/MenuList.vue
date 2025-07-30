<template>
  <q-scroll-area class="scroll-box">
    <template v-for="menu in menuList" :key="menu.meta.title">
      <!-- 只有有 children 才顯示下拉式，否則顯示單一選項 -->
      <q-expansion-item
        v-if="
          !menu.meta.hide &&
          !menu.meta.hideChildren &&
          menu.children &&
          menu.children.length > 0
        "
        class="menu text-white"
        default-opened
      >
        <template #header>
          <q-item
            class="col justify-between items-center q-pa-none menu-header"
          >
            <q-item-section v-if="menu.meta.icon || menu.meta.svg" avatar>
              <q-icon v-if="menu.meta.icon" :name="menu.meta.icon" />
              <svg-icon v-else :name="menu.meta.svg" />
            </q-item-section>

            <q-item-section>
              <q-item-label>{{ menu.meta.title }}</q-item-label>
            </q-item-section>
          </q-item>
        </template>

        <template v-for="sub in menu.children" :key="sub.meta.title">
          <q-item
            v-if="sub.meta.leftMenu"
            clickable
            active-class="active-menu-sub"
            class="menu-sub"
            :active="routeActive(sub)"
            @click="handleChangePage(sub)"
          >
            <q-item-section v-if="sub.meta.icon || sub.meta.svg" avatar>
              <q-icon v-if="sub.meta.icon" :name="sub.meta.icon" />
              <svg-icon v-else :name="sub.meta.svg" />
            </q-item-section>

            <q-item-section>
              <q-item-label>{{ sub.meta.title }}</q-item-label>
            </q-item-section>
          </q-item>
        </template>
      </q-expansion-item>

      <q-item
        v-else-if="!menu.meta.hide"
        clickable
        :active="routeActive(menu)"
        active-class="active-menu"
        class="menu"
        @click="handleChangePage(menu)"
      >
        <q-item-section v-if="menu.meta.icon || menu.meta.svg" avatar>
          <q-icon v-if="menu.meta.icon" :name="menu.meta.icon" />
          <svg-icon v-else :name="menu.meta.svg" />
        </q-item-section>
        <q-item-section>
          <q-item-label>{{ menu.meta.title }}</q-item-label>
        </q-item-section>
      </q-item>
    </template>
  </q-scroll-area>
</template>

<script setup>
import { computed } from 'vue';
import { useStore } from 'vuex';
import { useRouter, useRoute } from 'vue-router';
const store = useStore();
const route = useRoute();
const router = useRouter();

// 直接從 router.options.routes[0].children 取得所有主選單
const menuList = computed(() => {
  const children = router.options.routes[0]?.children || [];
  return children.filter(
    (route) => route.meta && !route.meta.hide && route.meta.leftMenu !== false,
  );
});

const handleChangePage = (item) => {
  if (item?.children && item.children.length > 0) {
    router.push({ name: item.children[0].name });
  } else if (item?.name) {
    router.push({ name: item.name });
  } else if (item?.path) {
    router.push(item.path);
  }
};

const authMeta = computed(() => {
  return store.getters['app/getAuthMeta'];
});

const permissions = computed(() => {
  return store.getters['app/getPermissions'];
});

const checkUserPermission = (meta) => {
  if (meta?.userPermissions) {
    return meta.userPermissions?.includes(authMeta.value?.MemberAcc);
  }
  return true;
};

const routeActive = (item) => {
  const { name } = route;
  if (item?.children) {
    return item.name === name
      ? item.name === name
      : item.children.some((child) => {
          return child.name === name;
        });
  }
  if (item?.name) {
    return name === item.name;
  }
  return false;
};
</script>

<style lang="scss" scoped>
.scroll-box {
  margin: 24px auto;
  height: 85vh;
}
.menu,
.menu-sub {
  font-size: 1rem;
  color: #fff;
  font-weight: bold;
  padding: 20px 16px;
  border-radius: 8px;
  transition: background 0.2s, color 0.2s;
}
.menu:hover,
.menu-sub:hover {
  background: rgba(255, 255, 255, 0.08);
  color: $accent;
}
.active-menu,
.active-menu-sub {
  background: $accent;
  color: #fff;
  border-left: 4px solid #fff !important;
  border-radius: 8px;
}
.menu-sub {
  margin: 0;
}
.menu-header {
  ~ :deep(.q-item__section) {
    text-align: right;
    padding: 0;
    color: #fff;
  }
}

.icon {
  margin-left: 2px;
}

:deep(.q-item__section--avatar) {
  align-items: end;
  min-width: 24px;
}

:deep().q-item__section--main ~ .q-item__section--side {
  color: rgb(255, 255, 255, 0.5);
}

:deep().q-focusable {
  border-left: 2px solid;
  border-color: $primary;
}

.q-expansion-item {
  padding: 0;
}
</style>
