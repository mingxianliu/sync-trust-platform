<template>
  <q-card-section>
    <FormGroup :all-data="groupData" class="row" />
  </q-card-section>
  <q-separator inset />
  <q-card-section>
    <FormGroup :all-data="radioData" class="row" />
  </q-card-section>
</template>

<script setup>
import { onMounted, reactive, ref, watch, computed } from 'vue';
import FormGroup from 'src/components/FormGroup';
import { $_isEmpty } from 'src/mixin/common';
import { useStore } from 'vuex';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      groupNo: null,
      groupName: null,
      org: null,
      isSend: {
        val: false,
        isDisable: false,
      },
      isReceive: {
        val: false,
        isDisable: false,
      },
    }),
  },
});
const emit = defineEmits(['update:modelValue']);

const store = useStore();

const organizationList = ref([]);

const groupData = reactive({
  groupNo: {
    title: '群組代碼',
    label: '請輸入群組代碼',
    type: 'input',
    val: props.modelValue.groupNo,
    rules: [$_isEmpty],
    col: 'col-12',
  },
  groupName: {
    title: '群組名稱',
    label: '請輸入群組名稱',
    type: 'input',
    val: props.modelValue.groupName,
    rules: [$_isEmpty],
    col: 'col-12',
  },
  org: {
    title: '上層組織',
    type: 'select',
    options: [],
    val: props.modelValue.org,
    rules: [$_isEmpty],
    col: 'col-12',
    mapOptions: true,
  },
});

const radioData = reactive({
  isSend: {
    label: '發送權限',
    type: 'radio',
    val: props.modelValue.isSend.val,
    options: [
      { label: '是', value: true },
      { label: '否', value: false },
    ],
    col: 'col-6',
    isDisable: props.modelValue.isSend.isDisable ?? false,
  },
  isReceive: {
    label: '接收權限 ',
    type: 'radio',
    val: props.modelValue.isReceive.val,
    options: [
      { label: '是', value: true },
      { label: '否', value: false },
    ],
    col: 'col-6',
    isDisable: props.modelValue.isReceive.isDisable ?? false,
  },
});

const authMeta = computed(() => {
  return store.getters['app/getAuthMeta'];
});

const fetchOrganizationListOptions = async () => {
  const formData = new FormData();
  formData.append('Type', 'all');
  formData.append('Count', '99999');
  const res = await store.dispatch(
    'organizational/getOrganizationList',
    formData,
  );
  organizationList.value = res.Message;

  if (authMeta.value?.MemberAcc === 'admin') {
    groupData.org.options = res.Message.filter(
      (org) => org.OrganizationNo !== 'NSPOEncode',
    ).map((org) => ({
      label: org.OrganizationName,
      value: org.OrganizationNo,
    }));
    return;
  }
  groupData.org.options = [
    {
      label: authMeta.value.OrganizationName,
      value: authMeta.value.OrganizationNo,
    },
  ];
};

onMounted(async () => {
  await fetchOrganizationListOptions();
  const selectedOrgNo = groupData.org.val;
  const selectedOrg = organizationList.value.find(
    (org) => org.OrganizationNo === selectedOrgNo,
  );
  if (selectedOrg) {
    radioData.isReceive.isDisable = !(selectedOrg.IsReceive === '1');
    radioData.isSend.isDisable = !(selectedOrg.IsSend === '1');
  }
});

watch(
  () => groupData.org.val,
  (newVal) => {
    if (!newVal) return;
    const selectedOrgNo = newVal.value;
    const selectedOrg = organizationList.value.find(
      (org) => org.OrganizationNo === selectedOrgNo,
    );
    if (selectedOrg) {
      radioData.isReceive.val = selectedOrg.IsReceive === '1';
      radioData.isSend.val = selectedOrg.IsSend === '1';
      radioData.isReceive.isDisable = !(selectedOrg.IsReceive === '1');
      radioData.isSend.isDisable = !(selectedOrg.IsSend === '1');
    }
  },
);

watch(
  () => [
    groupData.groupName.val,
    groupData.groupNo.val,
    groupData.org.val,
    radioData.isReceive.val,
    radioData.isSend.val,
    radioData.isReceive.isDisable,
    radioData.isSend.isDisable,
  ],
  () => {
    emit('update:modelValue', {
      groupNo: groupData.groupNo.val,
      groupName: groupData.groupName.val,
      org: groupData.org.val,
      isSend: {
        val: radioData.isSend.val,
        isDisable: radioData.isSend.isDisable,
      },
      isReceive: {
        val: radioData.isReceive.val,
        isDisable: radioData.isReceive.isDisable,
      },
    });
  },
);

watch(
  () => props.modelValue,
  (newVal) => {
    groupData.groupName.val = newVal.groupName;
    groupData.groupNo.val = newVal.groupNo;
    groupData.org.val = newVal.org;
    radioData.isSend.val = newVal.isSend.val;
    radioData.isReceive.val = newVal.isReceive.val;
    radioData.isReceive.isDisable = newVal.isReceive.isDisable;
    radioData.isSend.isDisable = newVal.isSend.isDisable;
  },
  {
    deep: true,
  },
);
</script>
