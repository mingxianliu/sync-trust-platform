<template>
  <q-dialog persistent :value="props.value" @click="handleClose">
    <q-card style="width: 600px; max-width: 60vw">
      <q-form greedy @submit="submit">
        <q-card-section>
          <h6 class="q-ma-none text-main-color">編輯</h6>
        </q-card-section>

        <q-separator inset />

        <q-card-section>
          <FormGroup :all-data="fileData" />
        </q-card-section>

        <q-separator inset />

        <q-card-section>
          <FormGroup :all-data="radioData" class="row" />
        </q-card-section>

        <q-separator inset />

        <q-card-section>
          <FormGroup :all-data="detailOrganizationData" class="row" />
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="between" class="q-my-lg q-mr-sm">
          <q-btn
            color="main-color"
            label="編輯接收方"
            unelevated
            padding="sm xl"
            class="q-ml-sm"
            @click="editReceiver"
          />
          <div>
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
              label="修改"
              unelevated
              padding="sm xl"
              class="q-ml-sm"
            />
          </div>
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useStore } from 'vuex';
import { $_isEmpty, $_successNotify, $_errorNotify } from 'src/mixin/common';
import FormGroup from 'src/components/FormGroup';
import { useRouter } from 'vue-router';

const emit = defineEmits(['close', 'updateList']);
const store = useStore();
const router = useRouter();

const props = defineProps({
  value: {
    type: Boolean,
    default: false,
  },
  data: {
    type: Object,
    default: () => {},
  },
});
const handleClose = () => {
  emit('close');
  clear();
};
const clear = () => {
  Object.keys(fileData.value).map((item) => {
    fileData.value[item].val = null;
  });
  Object.keys(radioData.value).map((item) => {
    radioData.value[item].val = null;
    radioData.value[item].isDisable = false;
  });
};
const fileData = ref({
  // OrganizationNo: {
  //   title: '組織代碼',
  //   label: '請輸入組織代碼',
  //   type: 'input',
  //   val: null,
  //   rules: [$_isEmpty],
  // },
  OrganizationName: {
    title: '組織名稱',
    label: '請輸入組織名稱',
    type: 'input',
    val: null,
    rules: [$_isEmpty],
  },
});

const radioData = ref({
  IsSend: {
    label: '發送權限',
    type: 'radio',
    val: null,
    options: [
      { label: '是', value: '1' },
      { label: '否', value: '0' },
    ],
    col: 'col-6',
    isDisable: false,
  },
  IsReceive: {
    label: '接收權限',
    type: 'radio',
    val: null,
    options: [
      { label: '是', value: '1' },
      { label: '否', value: '0' },
    ],
    col: 'col-6',
    isDisable: false,
  },
  // IsAdmin: {
  //   label: '平台擁有者',
  //   type: 'radio',
  //   val: null,
  //   options: [
  //     { label: '是', value: '1' },
  //     { label: '否', value: '0' },
  //   ],
  //   col: 'col-6',
  //   isDisable: false,
  // },
  Status: {
    label: '停用',
    type: 'radio',
    val: null,
    options: [
      { label: '是', value: '0' },
      { label: '否', value: '1' },
    ],
    col: 'col-6',
  },
});

const detailOrganizationData = ref({
  groupLimit: {
    title: '組織內群組上限',
    label: undefined,
    type: 'input',
    val: null,
    rules: [
      $_isEmpty,
      (val) => {
        const num = parseInt(val) || 0;
        return num >= 2 || '群組至少需要2人';
      },
    ],
    col: 'col-6',
  },
  memberLimit: {
    title: '組織內使用者上限',
    label: undefined,
    type: 'input',
    val: null,
    rules: [
      $_isEmpty,
      (val) => {
        const num = parseInt(val) || 0;
        return num >= 1 || '使用者至少需要1人';
      },
    ],
    col: 'col-6',
  },
});

const fetchOrgUpdate = () => {
  const { OrganizationName } = fileData.value;
  const { IsSend, IsReceive, Status } = radioData.value;
  const { memberLimit, groupLimit } = detailOrganizationData.value;
  const formData = new FormData();
  formData.append('OrgId', props.data.ID);
  formData.append('OrgName', OrganizationName.val);
  formData.append('OrgNo', props.data.OrganizationNo);

  formData.append(
    'IsSend',
    Status.val === '0' ? '2' : IsSend.val === '2' ? '0' : IsSend.val,
  );
  formData.append(
    'IsReceive',
    Status.val === '0' ? '2' : IsReceive.val === '2' ? '0' : IsReceive.val,
  );
  formData.append(
    'IsAdmin',
    Status.val === '0' ? '2' : props.data.OrganizationNo === 'TASA' ? '1' : '0',
  );
  formData.append('Status', Status.val);
  // status 0: 停用 綠 1: 啟用 灰

  formData.append('MemberLimit', parseInt(memberLimit.val) || 0);
  formData.append('GroupLimit', parseInt(groupLimit.val) || 0);

  return store.dispatch('organizational/putOrganizationUpdate', formData);
};

const submit = async () => {
  const res = await fetchOrgUpdate();
  if (res.Result) {
    $_successNotify('Successful Operation, 請使用者重新登入');
    emit('updateList');
    return handleClose();
  }
  $_errorNotify('更新失敗');
};

const editReceiver = async () => {
  const res = await fetchOrgUpdate();
  if (res.Result) {
    $_successNotify('儲存成功');
    router.push({
      name: 'EditReceivedOrganization',
      params: { OrganizationNo: props.data.OrganizationNo },
    });
    return;
  }
  $_errorNotify('更新失敗');
};

watch(
  () => props.value,
  (val) => {
    if (val) {
      Object.keys(fileData.value).map((item) => {
        fileData.value[item].val = props.data[item];
      });
      Object.keys(radioData.value).map((item) => {
        if (props.data.Status === '0' && item !== 'Status') {
          radioData.value[item].isDisable = true;
        }
        radioData.value[item].val = props.data[item];
      });

      detailOrganizationData.value.groupLimit.val = props.data.GroupLimit;
      detailOrganizationData.value.memberLimit.val = props.data.MemberLimit;
    }
  },
);
</script>

<style lang="scss" scoped></style>
