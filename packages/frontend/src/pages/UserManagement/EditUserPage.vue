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
            編輯使用者
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
        <EditUserForm v-model="editUserData" />
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
      :rows="userPublicKeyTableListData.List"
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

          <template v-else-if="props.col.name === 'Hash'">
            <div class="cursor-pointer ipfs-box">
              {{ props.value }}
            </div>
          </template>

          <template v-else-if="props.col.name === 'Details'">
            <div class="file-list-box">
              <q-chip
                v-for="(file, index) in getFileClipList(props.value)"
                :key="file"
                square
                :tabindex="index"
                color="indigo-1"
                text-color="blue6"
                class="q-ma-none q-mr-xs"
              >
                {{ file }}
              </q-chip>
            </div>
          </template>

          <template v-else-if="props.col.name === 'actions'">
            <div class="text-center">
              <q-btn
                dense
                flat
                round
                color="blue7"
                icon="eva-arrow-forward-outline"
                @click="handleAction(props.row)"
              />
            </div>
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
import { ref, computed, onMounted } from 'vue';
import { useStore } from 'vuex';
import { $_successNotify, $_errorNotify } from 'src/mixin/common';
import { useRoute, useRouter } from 'vue-router';
import { $_sha512 } from 'src/mixin/key';

const route = useRoute();
const router = useRouter();

const store = useStore();

const editUserData = ref({
  acc: {
    val: null,
    readonly: true,
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
    name: 'CreateTime',
    label: '創建日期',
    align: 'left',
    field: 'CreateTime',
    sortable: false,
    style: 'width: 3%',
  },
  {
    name: 'Hash',
    label: '使用者公鑰Hash',
    align: 'left',
    field: 'Hash',
    sortable: false,
    style: 'width:450px;',
  },
  {
    name: 'Details',
    label: '公鑰加密過檔案',
    align: 'left',
    field: 'Details',
    sortable: false,
    style: 'max-width: 500px',
  },
  {
    name: 'actions',
    label: '存取紀錄',
    align: 'content',
    sortable: false,
  },
]);

const userPublicKeyTableListData = ref({
  List: [],
});

const pagination = ref({
  page: 1,
  rowsPerPage: 5,
});

const userInfo = ref(route.params);

const rowsPerPageOptions = computed(() => {
  return store.getters['app/getRowsPerPageOptions'];
});

const changePagination = (requestProp) => {
  if (requestProp?.pagination) {
    pagination.value = requestProp.pagination;
    store.commit('app/setRowsPerPage', requestProp.pagination);
  }
};

const fetchHistoricalKeyList = async () => {
  const formData = new FormData();
  formData.append('MemberNo', userInfo.value.MemberNo);
  formData.append('Page', pagination.value.page);
  formData.append('Count', pagination.value.rowsPerPage);
  formData.append('Mode', 'member');

  const res = await store.dispatch('user/getHistoricalKeyByMemberNo', formData);
  if (res.Result) {
    for (let i = 0; i < res.List.length; i++) {
      res.List[i].Hash = await $_sha512(res.List[i].PublicKey);
    }
    userPublicKeyTableListData.value.List = res.List;
  }
};

const getFileClipList = (fileList) => {
  const fileClipList = [];
  const length = fileList.length > 4 ? 4 : fileList.length;
  const leftBehindFile = fileList.length - length;

  for (let i = 0; i < length; i++) {
    fileClipList.push(fileList[i].FileName);
  }

  if (leftBehindFile > 0) {
    fileClipList.push(`+${leftBehindFile}`);
  }

  return fileClipList;
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

const getPermissionByGroupNo = async (groupNo) => {
  if (!groupNo) {
    return {
      IsSend: 0,
      IsReceive: 0,
    };
  }
  const formData = new FormData();
  formData.append('GroupNo', groupNo);
  const res = await store.dispatch('group/getGroupByGroupNo', formData);
  return {
    IsSend: res.Message?.IsSend ?? 0,
    IsReceive: res.Message?.IsReceive ?? 0,
  };
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
  const { IsSend, IsReceive } = await getPermissionByGroupNo(groupNo);

  const formData = new FormData();
  formData.append('OrgNo', orgNo);
  formData.append('MemberAcc', acc.val);
  formData.append('MemberName', name);
  formData.append('MemberPwd', password ?? '');
  formData.append('GroupNo', groupNo);
  formData.append('Email', email);
  formData.append('MemberId', userInfo.value.ID);
  formData.append('IsAdmin', isAdmin ? '1' : '0');
  formData.append('Status', enableUser ? '1' : '0');
  formData.append('IsSend', IsSend);
  formData.append('IsReceive', IsReceive);
  try {
    const res = await store.dispatch('user/putMemberUpdate', formData);
    if (res.Result) {
      return $_successNotify('Successful Operation');
    }
    const error = Object.values(res.Errors).join(',');
    $_errorNotify(error);
  } catch {
    $_errorNotify('更新失敗');
  }
};

const handleAction = (row) => {
  store.commit('user/setSelectedUserPublicKeyFileRecords', row.Details);
  router.push({
    name: 'userEncryptedFiles',
    params: {
      memberNo: userInfo.value.MemberNo,
    },
  });
};

onMounted(async () => {
  if (route.params.MemberAcc) {
    editUserData.value.acc.val = route.params.MemberAcc;
    editUserData.value.name = route.params.MemberName;
    editUserData.value.email = route.params.Email;
    editUserData.value.org = route.params.OrganizationNo;
    editUserData.value.group = route.params.GroupNo;
    editUserData.value.isAdmin = route.params.IsAdmin === '1';
    editUserData.value.enableUser = route.params.Status === '1';
  } else {
    const formData = new FormData();
    formData.append('MemberNo', userInfo.value.MemberNo);
    const { Data } = await store.dispatch('user/getMemberByMemberNo', formData);
    userInfo.value = Data;
    editUserData.value.acc.val = Data.MemberAcc;
    editUserData.value.name = Data.MemberName;
    editUserData.value.email = Data.Email;
    editUserData.value.org = Data.OrganizationNo;
    editUserData.value.group = Data.GroupNo;
    editUserData.value.isAdmin = Data.IsAdmin === '1';
    editUserData.value.enableUser = Data.Status === '1';
  }
  fetchHistoricalKeyList();
});
</script>

<style lang="scss" scoped>
.ipfs-box {
  word-break: break-all;
  white-space: normal;
  margin: 10px 0 10px;
  padding: 10px;
  background: $blue4;
  color: $blue2;
}
.file-list-box {
  display: flex;
  flex-wrap: wrap;
  width: 100%;
}
</style>
