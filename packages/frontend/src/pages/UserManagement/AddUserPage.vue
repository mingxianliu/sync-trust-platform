<template>
  <q-card>
    <q-form greedy @submit.prevent="submit">
      <q-card-section
        class="flex justify-between q-pt-md bg-indigo-1 box-border"
      >
        <div>
          <q-icon
            name="eva-arrow-back-outline"
            class="text-indigo-3 cursor-pointer"
            @click="router.go(-1)"
          />
          <p class="q-mt-sm q-mb-none text-blue6 text-bold d-inline">
            新增使用者
          </p>
        </div>
        <div>
          <q-btn
            type="submit"
            color="main-color"
            label="儲存變更"
            class="col btn-height self-center q-ml-md q-mt-sm"
            unelevated
            icon="save_alt"
          />
        </div>
      </q-card-section>
      <q-card-section>
        <EditUserForm v-model="editUserData" mode="add" />
      </q-card-section>
    </q-form>
  </q-card>
  <q-card class="q-mt-lg">
    <q-card-section class="flex justify-between q-pt-md bg-indigo-1 box-border">
      <p class="q-mt-sm q-mb-none text-blue6 text-bold">使用者公鑰列表</p>
    </q-card-section>
    <q-table
      v-model:pagination="pagination"
      :rows-per-page-options="rowsPerPageOptions"
      :rows="[]"
      :columns="userPublicKeyColumns"
      :table-header-class="'bg-indigo-1 text-blue6'"
      no-data-label="'No Results'"
      row-key="name"
      flat
      @request="changePagination"
    >
      <template #body-cell="props">
        <q-td :props="props">
          <template v-if="props.col.name === 'verifyAction'">
            <div class="text-center">
              <q-btn
                color="indigo-1"
                text-color="blue6"
                label="驗證"
                class="btn-height"
                unelevated
                style="width: 100px"
              />
            </div>
          </template>

          <template v-else-if="props.col.name === 'IPFSHash'">
            <div class="cursor-pointer">
              {{ props.value }}
            </div>
          </template>

          <template v-else-if="props.col.name === 'file'">
            <q-chip
              v-for="(file, index) in props.value"
              :key="file"
              removable
              square
              :tabindex="index"
              color="indigo-1"
              text-color="blue6"
              class="q-ma-none q-mr-xs"
              @remove="console.log(props)"
            >
              {{ file }}
            </q-chip>
          </template>

          <template v-else>
            {{ props.value }}
          </template>
        </q-td>
      </template>
    </q-table>
  </q-card>
</template>

<script setup>
import EditUserForm from 'src/components/UserManagement/EditUserForm';
import { ref, computed } from 'vue';
import { useStore } from 'vuex';
import { $_successNotify, $_errorNotify } from 'src/mixin/common';
import { useRouter } from 'vue-router';

const store = useStore();
const router = useRouter();

const editUserData = ref({
  acc: {
    val: null,
  },
  name: null,
  email: null,
  password: null,
  org: null,
  group: null,
  isAdmin: false,
  enableUser: false,
});

const userPublicKeyColumns = ref([
  {
    name: 'createdDate',
    label: '創建日期',
    align: 'left',
    field: 'createdDate',
    sortable: false,
    style: 'width: 15%',
  },
  {
    name: 'file',
    label: '公鑰加密過檔案',
    align: 'left',
    field: 'file',
    sortable: false,
    style: 'width: 500px',
  },
  {
    name: 'IPFSHash',
    label: 'IPFS區塊鏈Hash',
    align: 'left',
    field: 'IPFSHash',
    classes: 'ipfs-box',
    sortable: false,
    style: 'width: 15%',
  },
]);

const pagination = ref({
  page: 1,
  rowsPerPage: 5,
});

const rowsPerPageOptions = computed(() => {
  return store.getters['app/getRowsPerPageOptions'];
});

const changePagination = (requestProp) => {
  if (requestProp?.pagination) {
    pagination.value = requestProp.pagination;
    store.commit('app/setRowsPerPage', requestProp.pagination);
  }
};

const getUnassignedGroupNoByOrgNo = async (orgNo) => {
  const formData = new FormData();
  formData.append('Type', 'all');
  formData.append('Count', '99999');
  const res = await store.dispatch('group/getGroupList', formData);
  const groupList = res.Message;
  const unassignedGroup = groupList.find(
    (group) =>
      group.OrganizationNo === orgNo && group.GroupNo.includes('_UNASSIGNED'),
  );
  return unassignedGroup?.GroupNo;
};

const submit = async () => {
  const { acc, name, email, org, group, password, isAdmin, enableUser } =
    editUserData.value;
  const orgNo = org?.value ?? org;

  let groupNo = group?.value ?? group;
  if (!groupNo) {
    groupNo = await getUnassignedGroupNoByOrgNo(orgNo);
    editUserData.value.group = groupNo;
  }
  const formData = new FormData();
  formData.append('OrgNo', orgNo);
  formData.append('MemberAcc', acc.val);
  formData.append('MemberName', name);
  formData.append('MemberPwd', password);
  formData.append('GroupNo', groupNo);
  formData.append('Email', email);
  formData.append('IsAdmin', isAdmin ? '1' : '0');
  formData.append('Status', enableUser ? '1' : '0');
  try {
    const res = await store.dispatch('user/postAddMember', formData);
    if (res.Result) {
      return $_successNotify('Successful Operation');
    }
    const error = Object.values(res.Errors).join(',');
    $_errorNotify(error);
  } catch {
    $_errorNotify('新增失敗');
  }
};
</script>

<style lang="scss" scoped>
.ipfs-box {
  max-width: 12vw;
  word-break: break-all;
  white-space: normal;
  padding: 20px;
  background: $blue4;
  color: $blue2;
  box-shadow: 0px 0px 0px 10px white inset;
}
</style>
