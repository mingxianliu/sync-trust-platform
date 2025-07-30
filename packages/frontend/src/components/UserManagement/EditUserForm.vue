<template>
  <FormGroup :all-data="userData" class="row">
    <div class="col-2">
      <p class="text-bold q-mb-sm">組織管理者</p>
      <q-toggle
        v-model="isAdmin"
        color="main-color"
        :disable="isDisabledForAdmin"
      />
    </div>
    <div class="col-2">
      <p class="text-bold q-mb-sm">是否啟用</p>
      <q-toggle
        v-model="enableUser"
        color="main-color"
        :disable="isDisabledForAdmin"
      />
    </div>
  </FormGroup>
</template>

<script setup>
import FormGroup from 'src/components/FormGroup';
import { ref, onMounted, watch, reactive, computed } from 'vue';
import { useStore } from 'vuex';
import { $_isEmpty } from 'src/mixin/common';

const store = useStore();

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      acc: {
        val: null,
        readonly: false,
      },
      name: null,
      email: null,
      password: null,
      org: null,
      group: null,
      isAdmin: false,
      enableUser: false,
    }),
  },
  mode: {
    type: String,
    default: 'update',
  },
});

const emits = defineEmits(['update:modelValue']);

const authMeta = computed(() => {
  return store.getters['app/getAuthMeta'];
});

const isDisabledForAdmin = computed(() => {
  return props.modelValue.acc.val === 'admin';
});

const userData = reactive({
  acc: {
    title: '使用者帳號',
    type: 'input',
    val: props.modelValue.acc.val,
    col: 'col-4',
    readonly: props.modelValue.acc.readonly,
    rules: [$_isEmpty],
  },
  name: {
    id: 1,
    title: '姓名',
    val: props.modelValue.name,
    type: 'input',
    options: [],
    col: 'col-4',
    rules: [$_isEmpty],
  },
  email: {
    id: 2,
    title: 'Email',
    val: props.modelValue.email,
    type: 'input',
    col: 'col-4',
    rules: [$_isEmpty],
  },
  password: {
    id: 3,
    title: '密碼',
    val: props.modelValue.password,
    type: 'password',
    col: 'col-4',
    dense: true,
    pwdType: 'password',
    label: '密碼',
    rules: [
      (val) => {
        if (!val && props.mode !== 'add') return true;
        // 包含大小寫
        const reg = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{12,}$/;
        let isCase = reg.test(val);
        return isCase ? true : '必須十二位數，且含大小寫字母、數字';
      },
    ],
  },
  org: {
    id: 4,
    title: '組織',
    val: props.modelValue.org,
    type: 'select',
    options: [],
    col: 'col-2',
    rules: [$_isEmpty],
    disable: isDisabledForAdmin.value,
  },
  group: {
    id: 5,
    title: '群組',
    val: props.modelValue.group,
    type: 'select',
    options: [],
    col: 'col-2',
    disable: isDisabledForAdmin.value,
  },
});

const isAdmin = ref(props.modelValue.isAdmin);
const enableUser = ref(props.modelValue.enableUser);
const organizationList = ref([]);
const groupList = ref([]);

const fetchOrganizationListOptions = async () => {
  const formData = new FormData();
  formData.append('Type', 'all');
  formData.append('Count', '99999');
  const res = await store.dispatch(
    'organizational/getOrganizationList',
    formData,
  );
  organizationList.value = res.Message;
  if (authMeta.value.MemberAcc === 'admin') {
    userData.org.options = res.Message.filter(
      (org) => org.OrganizationNo !== 'NSPOEncode',
    ).map((org) => ({
      label: org.OrganizationName,
      value: org.OrganizationNo,
    }));
    return;
  }
  userData.org.options = [
    {
      label: authMeta.value.OrganizationName,
      value: authMeta.value.OrganizationNo,
    },
  ];
};

const fetchGroupListOptions = async () => {
  const formData = new FormData();
  formData.append('Type', 'all');
  formData.append('Count', '99999');
  const res = await store.dispatch('group/getGroupList', formData);
  groupList.value = res.Message;
  userData.group.options = res.Message.map((x) => ({
    label: x.GroupName,
    value: x.GroupNo,
  }));
};

const filterGroupByOrgNo = (orgNo) => {
  userData.group.options = groupList.value
    .filter((x) => x.OrganizationNo === orgNo)
    .map((x) => ({
      label: x.GroupName,
      value: x.GroupNo,
    }));
};

watch(
  () => userData.org.val?.value,
  (newVal) => {
    filterGroupByOrgNo(newVal);
  },
);

watch(
  () => [
    userData.acc.val,
    userData.name.val,
    userData.email.val,
    userData.password.val,
    userData.org.val,
    userData.group.val,
    isAdmin.value,
    enableUser.value,
  ],
  () => {
    emits('update:modelValue', {
      acc: {
        val: userData.acc.val,
        readonly: userData.acc.readonly,
      },
      name: userData.name.val,
      email: userData.email.val,
      password: userData.password.val,
      org: userData.org.val,
      group: userData.group.val,
      isAdmin: isAdmin.value,
      enableUser: enableUser.value,
    });
  },
);

watch(
  () => props.modelValue,
  () => {
    userData.acc.val = props.modelValue.acc.val;
    userData.name.val = props.modelValue.name;
    userData.email.val = props.modelValue.email;
    userData.password.val = props.modelValue.password;
    userData.org.val = props.modelValue.org;
    userData.group.val = props.modelValue.group;
    isAdmin.value = props.modelValue.isAdmin;
    enableUser.value = props.modelValue.enableUser;
    userData.org.disable = isDisabledForAdmin.value;
    userData.group.disable = isDisabledForAdmin.value;
  },
  {
    deep: true,
  },
);

onMounted(async () => {
  await Promise.all([fetchOrganizationListOptions(), fetchGroupListOptions()]);

  if (props.modelValue.org) {
    const targetOrg = organizationList.value.find(
      (x) => x.OrganizationNo === props.modelValue.org,
    );
    userData.org.val = {
      label: targetOrg.OrganizationName,
      value: targetOrg.OrganizationNo,
    };
    filterGroupByOrgNo(targetOrg.OrganizationNo);
  }

  if (props.modelValue.group) {
    const targetGroup = groupList.value.find(
      (x) => x.GroupNo === props.modelValue.group,
    );
    if (targetGroup) {
      userData.group.val = {
        label: targetGroup.GroupName,
        value: targetGroup.GroupNo,
      };
    }
  }
});
</script>
