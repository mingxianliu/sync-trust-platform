<template>
  <div class="row">
    <div class="user-info">
      <q-btn unelevated icon-right="eva-chevron-down-outline">
        <AvatarImg
          :path="
            authMeta?.MemberPic
              ? `https://synckeytech.winshare.tw/imagerender/${authMeta?.MemberPic}`
              : ''
          "
        />
        <div class="flex column items-start q-mx-md">
          <p class="q-ma-none">{{ authMeta?.MemberAcc || '' }}</p>
          <p class="q-ma-none">
            {{ authMeta?.OrganizationName }}・{{ authMeta?.MemberName }}
          </p>
        </div>

        <q-menu
          v-model="isOpen"
          transition-show="scale"
          transition-hide="scale"
        >
          <q-list style="min-width: 200px">
            <q-item v-for="data in userMenuFilter" :key="data.label" clickable>
              <q-item-section side>
                <q-icon :name="data.icon" size="20px" />
              </q-item-section>
              <q-item-section @click="handleRouter(data.path_name)">
                {{ data.label }}
              </q-item-section>
            </q-item>
          </q-list>
        </q-menu>
      </q-btn>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import AvatarImg from 'src/components/AvatarImg';
import { useStore } from 'vuex';

const store = useStore();
const router = useRouter();
const isOpen = ref(false);

const authMeta = computed(() => {
  return store.getters['app/getAuthMeta'];
});

const userMenu = ref([
  {
    label: '變更密碼',
    icon: 'eva-unlock-outline',
    path_name: '/setting/change-password',
  },
  {
    label: '私鑰設定',
    icon: 'key',
    path_name: '/setting/change-key',
  },
  {
    label: '系統公鑰管理',
    icon: 'settings',
    path_name: '/management/systemPublicKey',
    permission: ['admin'],
  },
  {
    label: '登出',
    icon: 'eva-log-out-outline',
    path_name: '/login',
  },
]);

const userMenuFilter = computed(() => {
  return userMenu.value.filter((item) => {
    if (item.permission) {
      return item.permission.includes(authMeta.value?.MemberAcc);
    }
    return true;
  });
});

const handleRouter = (path) => {
  if (path === '/login') store.commit('app/logout');
  router.push(path);
  isOpen.value = false;
};
</script>

<style lang="scss" scoped></style>
