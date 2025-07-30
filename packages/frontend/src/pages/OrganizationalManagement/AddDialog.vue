<template>
  <q-dialog persistent :value="props.value" @click="handleClose">
    <q-card style="width: 600px; max-width: 60vw">
      <q-form greedy @submit="submit">
        <q-card-section>
          <h6 class="q-ma-none text-main-color">新增組織</h6>
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
              label="新增"
              unelevated
              padding="sm xl"
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
  });
};
const fileData = ref({
  code: {
    title: '組織代碼',
    label: '請輸入組織代碼',
    type: 'input',
    val: null,
    rules: [$_isEmpty],
  },
  name: {
    title: '組織名稱',
    label: '請輸入組織名稱',
    type: 'input',
    val: null,
    rules: [$_isEmpty],
  },
});

const radioData = ref({
  open: {
    label: '啟用',
    type: 'radio',
    val: true,
    options: [
      { label: '是', value: true },
      { label: '否', value: false },
    ],
    col: 'col-6',
  },
  // pm: {
  //   label: '平台擁有者',
  //   type: 'radio',
  //   val: null,
  //   options: [
  //     { label: '是', value: true },
  //     { label: '否', value: false },
  //   ],
  //   col: 'col-6',
  // },
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

const submit = async () => {
  try {
    const addOrgRes = await fetchAddNewOrg();
    if (!addOrgRes.Result)
      throw new Error(Object.values(addOrgRes.Errors).join(','));

    const addGroupRes = await fetchUnassignedGroup();
    if (!addGroupRes.Result)
      throw new Error(Object.values(addGroupRes.Errors).join(','));

    $_successNotify('Successful Operation');
    emit('updateList');
    handleClose();
  } catch (error) {
    const message = Object.values(error.message).join(',');
    $_errorNotify(message);
  }
};

const fetchAddNewOrg = () => {
  const { code, name } = fileData.value;
  const { open } = radioData.value;
  const { memberLimit, groupLimit } = detailOrganizationData.value;
  const formData = new FormData();
  formData.append('OrgNo', code.val);
  formData.append('OrgName', name.val);
  formData.append('Status', open.val ? '1' : '0');
  // status 0: 停用 綠 1: 啟用 灰

  formData.append('MemberLimit', parseInt(memberLimit.val) || 0);
  formData.append('GroupLimit', parseInt(groupLimit.val) || 0);

  return store.dispatch('organizational/postAddOrganization', formData);
};

const fetchUnassignedGroup = async () => {
  const { code } = fileData.value;

  const formData = new FormData();
  formData.append('GroupNo', `${code.val}_UNASSIGNED`);
  formData.append('GroupName', '未分配群組');
  formData.append('Status', 1);
  formData.append('OrgNo', code.val);
  formData.append('IsAdmin', '0');
  formData.append('IsSend', '0');
  formData.append('IsReceive', '0');

  return store.dispatch('group/postAddGroup', formData);
};

const editReceiver = async () => {
  try {
    if (!validate()) throw new Error('請填寫完整資料');
    const addOrgRes = await fetchAddNewOrg();
    if (!addOrgRes.Result)
      throw new Error(Object.values(addOrgRes.Errors).join(','));

    const addGroupRes = await fetchUnassignedGroup();
    if (!addGroupRes.Result)
      throw new Error(Object.values(addGroupRes.Errors).join(','));

    $_successNotify('Successful Operation');
    router.push({
      name: 'EditReceivedOrganization',
      params: { OrganizationNo: fileData.value.code.val },
    });
    return;
  } catch (error) {
    const message = Object.values(error.message);
    $_errorNotify(message);
  }
};

const validate = () => {
  const { code, name } = fileData.value;
  const { memberLimit, groupLimit } = detailOrganizationData.value;

  const codeValid = code.rules.every((rule) => rule(code.val) === true);
  const nameValid = name.rules.every((rule) => rule(name.val) === true);
  const memberLimitValid = memberLimit.rules.every(
    (rule) => rule(memberLimit.val) === true,
  );
  const groupLimitValid = groupLimit.rules.every(
    (rule) => rule(groupLimit.val) === true,
  );

  return codeValid && nameValid && memberLimitValid && groupLimitValid;
};

watch(
  () => props.value,
  () => {},
);
</script>

<style lang="scss" scoped></style>
