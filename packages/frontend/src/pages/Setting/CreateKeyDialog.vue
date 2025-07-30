<template>
  <q-dialog persistent :value="props.value" @click="handleClose">
    <q-card style="width: 600px; max-width: 60vw">
      <q-form greedy @submit="fetchKey">
        <q-card-section>
          <h6 class="q-ma-none text-main-color">建立私鑰</h6>
        </q-card-section>

        <q-separator inset />

        <q-card-section>
          <AccountPasswordInput
            :user-data="password"
            @update-type="handleType"
          />
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="q-my-lg q-mr-sm">
          <q-btn
            color="indigo-1"
            text-color="main-color"
            label="取消"
            padding="sm xl"
            unelevated
            @click="handleClose"
          />
          <q-btn
            type="submit"
            color="main-color"
            label="建立"
            unelevated
            padding="sm xl"
          />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useStore } from 'vuex';
import {
  $_successNotify,
  $_errorNotify,
  $_handleDownload,
} from 'src/mixin/common';

import AccountPasswordInput from 'src/components/AccountPasswordInput';

const emit = defineEmits(['close', 'updateList']);
const store = useStore();

const props = defineProps({
  value: {
    type: Boolean,
    default: false,
  },
});
const handleClose = () => {
  emit('close');
  clear();
};
const clear = () => {
  password.value.psw.val = '';
};

const password = ref({
  psw: {
    label: '密碼',
    placeholder: '十二位數，且含大小寫字母、數字',
    val: '',
    type: 'password',
    isNumberCheck: false,
    isCaseCheck: false,
    lazyRules: true,
    dense: true,
    rules: [
      (val) => {
        const reg = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{12,}$/;
        let isCase = reg.test(val);
        return isCase ? true : 'invalid';
      },
    ],
  },
});

const handleType = ({ type, name }) => {
  password.value[name].type = type;
};

const fetchKey = () => {
  const { psw } = password.value;

  const formData = new FormData();
  formData.append('privatePwd', psw.val);

  return store
    .dispatch('app/postPrivatekey', formData)
    .then((res) => {
      if (res.Result) {
        const blob = new Blob([res.json], {
          type: 'application/x-x509-ca-cert',
        });
        const url = URL.createObjectURL(blob);
        $_handleDownload(url, res.filename);

        store.commit('app/setPrivateKey', '');
        $_successNotify('Successful Operation');
        handleClose();
        return;
      }
      $_errorNotify('Fail To Operation');
    })
    .catch(() => {
      $_errorNotify('Fail To Operation');
    });
};

watch(
  () => props.value,
  () => {},
);
</script>

<style lang="scss" scoped></style>
