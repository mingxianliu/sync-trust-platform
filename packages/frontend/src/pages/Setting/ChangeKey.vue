<template>
  <p class="text-blue6 text-bold">私鑰設定</p>
  <div class="row">
    <q-card class="col-5">
      <q-card-section>
        <p class="break">私鑰: {{ privateKey }}</p>

        <div class="text-right">
          <q-btn
            color="main-color"
            label="建立私鑰"
            class="btn-height q-ml-md q-mt-sm"
            unelevated
            @click="importantNotice"
          />
          <q-btn
            color="indigo-1"
            text-color="main-color"
            label="匯入私鑰"
            class="btn-height q-ml-md q-mt-sm"
            unelevated
            @click="
              {
                importKeyShow = true;
              }
            "
          />
        </div>
      </q-card-section>
    </q-card>
  </div>
  <!-- 跳窗 -->
  <CreateKeyDialog
    v-model="createKeyShow"
    :value="createKeyShow"
    @close="createKeyShow = false"
  />
  <ImportKeyDialog
    v-model="importKeyShow"
    :value="importKeyShow"
    @close="importKeyShow = false"
  />
</template>

<script setup>
import { useQuasar } from 'quasar';
import { ref, computed } from 'vue';
import CreateKeyDialog from './CreateKeyDialog';
import ImportKeyDialog from './ImportKeyDialog';
import { useStore } from 'vuex';
const store = useStore();
const $q = useQuasar();

const privateKey = computed(() => {
  return store.getters['app/getPrivateKey'] || '未設定';
});
const createKeyShow = ref(false);
const importKeyShow = ref(false);

const importantNotice = () => {
  $q.dialog({
    title: '重要通知',
    message: '如需重新產生私鑰，檔案需重新上傳，舊檔案將無法解密成功。',
  }).onOk(() => {
    createKeyShow.value = true;
  });
};
</script>

<style lang="scss" scoped>
.break {
  word-break: break-word;
}
</style>
