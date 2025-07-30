<template>
  <q-dialog persistent :value="props.value" @click="handleClose">
    <q-card style="width: 600px; max-width: 60vw">
      <q-form greedy @submit="fetchCheckKey">
        <q-card-section>
          <h6 class="q-ma-none text-main-color">匯入私鑰</h6>
        </q-card-section>

        <q-separator inset />

        <q-card-section>
          <FormGroup :all-data="fileData" class="" />
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
            label="匯入私鑰"
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
  $_isEmpty,
  $_successNotify,
  $_errorNotify,
  $_parseFileToJson,
} from 'src/mixin/common';
import { $_handleFile } from 'src/mixin/key';
import AccountPasswordInput from 'src/components/AccountPasswordInput';
import FormGroup from 'src/components/FormGroup';

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
  fileData.value.file.val = null;
};
const fileData = ref({
  file: {
    title: '私鑰',
    label: '選擇檔案',
    type: 'file',
    val: null,
    accept: '.json',
    rules: [$_isEmpty],
  },
});

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

const fetchCheckKey = async () => {
  const { file } = fileData.value;
  const { psw } = password.value;
  const json = await $_parseFileToJson(file.val);

  const formData = new FormData();
  formData.append('privatePwd', psw.val);
  formData.append('json', json);

  return store
    .dispatch('app/getPrivatekeyCheck', formData)
    .then((res) => {
      addPrivateKey(res.private_key, JSON.parse(json).memberId);
    })
    .catch((err) => {
      $_errorNotify(`匯入失敗，${err.message}`);
    });
};
const addPrivateKey = (privateKey, isImport) => {
  $_handleFile(privateKey, 2)
    .then(() => {
      store.commit('app/setPrivateKey', isImport);
      $_successNotify('Successful Operation');
      return handleClose();
    })
    .catch((err) => {
      store.commit('app/setPrivateKey', '');
      $_errorNotify(`匯入失敗，${err}`);
    });
};

watch(
  () => props.value,
  () => {},
);

watch(
  () => props.chPublicKey,
  () => { publicKey.value = props.publicKey }
);

</script>

<style lang="scss" scoped></style>
