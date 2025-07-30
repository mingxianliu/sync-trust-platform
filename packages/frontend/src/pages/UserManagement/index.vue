<template>
  <div class="row q-col-gutter-md">
    <div class="col-3">
      <q-card>
        <q-card-section
          class="flex justify-between q-pt-md bg-indigo-1 box-border"
        >
          <p class="q-mt-sm q-mb-none text-blue6 text-bold">組織與群組</p>
        </q-card-section>
        <q-card-section>
          <GroupTreeView
            :nodes="groupTreeData"
            node-key="key"
            @on-node-click="handleGroupViewChange"
            @on-edit-button-click="handleGroupEdit"
            @on-root-node-click="fetchUserListByMemberAcc"
          />
          <q-separator />

          <div class="row flex-center q-mt-md">
            <q-btn
              color="grey-4"
              text-color="blue6"
              label="新增群組"
              class="btn-height"
              style="width: 100%"
              unelevated
              @click="addGroupDialogShow = true"
            />
          </div>
        </q-card-section>
      </q-card>
    </div>
    <div class="col-9">
      <q-card>
        <q-card-section
          class="flex justify-between q-pt-md bg-indigo-1 box-border"
        >
          <p class="q-mt-sm q-mb-none text-blue6 text-bold">使用者管理</p>

          <div>
            <q-btn
              color="grey-4"
              text-color="blue6"
              label="新增使用者"
              class="btn-height q-ml-md"
              unelevated
              icon="add"
              @click="handleAdd"
            />
          </div>
        </q-card-section>
        <q-card-section
          class="row q-pt-lg"
        >
          <FormGroup
            :all-data="orgSearchBar"
            :is-title="false"
            class="row col-10"
          />
          <q-btn
            color="main-color"
            label="搜尋"
            class="col btn-height q-ml-md"
            unelevated
            icon="search"
            @click="fetchUserListByOrgName"
          />
        </q-card-section>
        <q-table
          v-model:pagination="pagination"
          :rows-per-page-options="rowsPerPageOptions"
          :rows="tableListData.List"
          :columns="columns"
          :table-header-class="'bg-indigo-1 text-blue6'"
          no-data-label="'No Results'"
          row-key="name"
          flat
          @request="changePagination"
        >
          <template #body-cell="props">
            <q-td :props="props">
              <template
                v-if="['Send', 'Receive', 'Owner'].includes(props.col.name)"
              >
                <template v-if="props.value === '0'">
                  <q-icon name="eva-minus-outline" color="grey-4" size="sm" />
                </template>

                <template v-else-if="props.value === '1'">
                  <q-icon
                    name="eva-checkmark-outline"
                    color="green-6"
                    size="sm"
                  />
                </template>

                <template v-else-if="props.value === '2'">
                  <q-icon name="eva-slash-outline" color="red-6" size="sm" />
                </template>
              </template>

              <template v-else-if="props.col.name === 'Stop'">
                <template v-if="props.value === '0'">
                  <q-icon
                    name="eva-checkmark-outline"
                    color="green-6"
                    size="sm"
                  />
                </template>

                <template v-else-if="props.value === '1'">
                  <q-icon name="eva-minus-outline" color="grey-4" size="sm" />
                </template>
              </template>

              <template v-else-if="props.col.name === 'PlatformAdmin'">
                <template v-if="props.row.MemberAcc === 'admin'">
                  <q-icon
                    name="eva-checkmark-outline"
                    color="green-6"
                    size="sm"
                  />
                </template>

                <template v-else>
                  <q-icon name="eva-minus-outline" color="grey-4" size="sm" />
                </template>
              </template>

              <template v-else-if="props.col.name === 'actions'">
                <q-btn
                  flat
                  rounded
                  color="main-color"
                  icon="eva-edit-outline"
                  label="編輯"
                  @click="handleEdit(props.row)"
                />
              </template>

              <template v-else>
                {{ props.value }}
              </template>
            </q-td>
          </template>
        </q-table>
      </q-card>
    </div>
  </div>
  <AddGroupDialog
    :show="addGroupDialogShow"
    @close="addGroupDialogShow = false"
    @update-list="fetchGroupListByMemberAcc"
  />
  <EditGroupDialog
    :show="editGroupDialogShow"
    :group-no="editGroupNo"
    @close="editGroupDialogShow = false"
    @update-list="fetchGroupListByMemberAcc"
  />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useStore } from 'vuex';
import AddGroupDialog from './AddGroupDialog';
import { $_fetchOrgListOption } from 'src/mixin/common';
import { useRouter } from 'vue-router';
import GroupTreeView from 'src/components/UserManagement/GroupTreeView.vue';
import EditGroupDialog from './EditGroupDialog';
import FormGroup from 'src/components/FormGroup';

const store = useStore();
const router = useRouter();

const addGroupDialogShow = ref(false);
const editGroupDialogShow = ref(false);

const editGroupNo = ref('');

const orgSearchBar = ref({
  searchOrg: {
    label: '組織名稱',
    type: 'input',
    val: null,
    col: 'col-4',
  },
  searchMemberName: {
    label: '使用者名稱',
    type: 'input',
    val: null,
    col: 'col-4',
  }
});

const columns = ref([
  {
    name: 'OrganizationName',
    label: '組織名稱',
    align: 'left',
    field: 'OrganizationName',
    sortable: true,
  },
  {
    name: 'MemberAcc',
    label: '使用者帳號',
    align: 'left',
    field: 'MemberAcc',
    style: 'max-width: 200px;',
    classes: 'ellipsis',
    sortable: true,
  },
  {
    name: 'MemberName',
    label: '姓名',
    align: 'left',
    field: 'MemberName',
    sortable: true,
  },
  {
    name: 'Email',
    label: 'Email',
    align: 'left',
    field: 'Email',
    style: 'width: 250px;',
    sortable: false,
  },
  {
    name: 'Send',
    label: '發送權限',
    align: 'left',
    field: 'IsSend',
    sortable: false,
    style: 'width: 120px;',
  },
  {
    name: 'Receive',
    label: '接收權限',
    align: 'left',
    field: 'IsReceive',
    sortable: false,
    style: 'width: 120px;',
  },
  {
    name: 'Owner',
    label: '組織管理者',
    align: 'left',
    field: 'IsAdmin',
    classes: 'ellipsis',
    sortable: false,
    style: 'width: 120px;',
  },
  {
    name: 'Stop',
    label: '停用',
    align: 'left',
    field: 'Status',
    classes: 'ipfs-box',
    sortable: false,
    style: 'width: 120px;',
  },
  {
    name: 'PlatformAdmin',
    label: '平台管理者',
    align: 'left',
    field: 'PlatformAdmin',
    classes: 'ipfs-box',
    sortable: false,
    style: 'width: 120px;',
  },
  {
    name: 'actions',
    align: 'left',
    classes: '',
    sortable: false,
    style: 'width: 130px;',
  },
]);
const tableListData = ref({});
let pagination = ref({
  page: 1,
  rowsNumber: null,
  descending: true,
});

const groupTreeData = ref([
  {
    label: '群組管理',
    key: `root`,
    header: 'root',
    children: [],
  },
]);

const rowsPerPageOptions = computed(() => {
  return store.getters['app/getRowsPerPageOptions'];
});
const rowsPerPage = computed(() => {
  return store.getters['app/getRowsPerPage'];
});

const authMeta = computed(() => {
  return store.getters['app/getAuthMeta'];
});

const changePagination = (requestProp) => {
  if (requestProp?.pagination) {
    pagination.value = requestProp.pagination;
    store.commit('app/setRowsPerPage', requestProp.pagination);
  }
  fetchUserListByOrgName();
};

const fetchAllUserList = () => {
  const formData = new FormData();
  formData.append('Page', pagination.value.page - 1);
  formData.append('Count', rowsPerPage.value);
  if (pagination.value.sortBy) {
    formData.append('Order', pagination.value.sortBy);
  }
  formData.append('Desc', pagination.value.descending ? 1 : 0);

  store.dispatch('user/getMemberList', formData).then((res) => {
    if (res.Result) {
      tableListData.value.List = res.Members;
      pagination.value.rowsNumber = res.Total;
      // status 0: 停用 綠 1: 啟用 灰
      // IsSend 0: 無狀態 灰 1: 有權限 綠 2: 無權限 紅
    }
  });
};

const fetchUserListByOrgName = async () => {
  if (!orgSearchBar.value.searchOrg.val && !orgSearchBar.value.searchMemberName.val) {
    return fetchAllUserList();
  }
  const formData = new FormData();
  formData.append('OrgName', orgSearchBar.value.searchOrg.val ?? "");
  formData.append('MemberName', orgSearchBar.value.searchMemberName.val ?? "");

  formData.append('Exclude', '0');
  formData.append('Desc', pagination.value.descending ? 1 : 0);
  if (pagination.value.sortBy) {
    formData.append('Order', pagination.value.sortBy);
  }
  const res = await store.dispatch('sentFile/getOrganizationMember', formData);
  if (res.Result) {
    tableListData.value.List = res.List;
  }
};

const fetchSelfUserList = async () => {
  const formData = new FormData();
  formData.append('OrgNo', authMeta.value.OrganizationNo);
  formData.append('Exclude', '0');
  formData.append('Desc', pagination.value.descending ? 1 : 0);
  if (pagination.value.sortBy) {
    formData.append('Order', pagination.value.sortBy);
  }
  const res = await store.dispatch('sentFile/getOrganizationMember', formData);
  if (res.Result) {
    tableListData.value.List = res.List;
  }
};

const fetchAllGroupList = async () => {
  const formData = new FormData();
  formData.append('Type', 'All');
  formData.append('Count', '99999');
  formData.append('Desc', '1');

  const resList = await Promise.all([
    store.dispatch('organizational/getOrganizationList', formData),
    store.dispatch('group/getGroupList', formData),
  ]);
  if (!resList.every((res) => res.Result)) return;
  const [organizationListRes, groupListRes] = resList;
  const allOrgs = organizationListRes.Message.map((org) => ({
    key: `org-${org.OrganizationNo}`,
    organizationNo: org.OrganizationNo,
    label: org.OrganizationName,
    children: [],
  }));

  for (let i = 0; i < groupListRes.Message.length; i++) {
    const groupInfo = groupListRes.Message[i];
    const targetOrg = allOrgs.find(
      ({ organizationNo }) => organizationNo === groupInfo.OrganizationNo,
    );
    if (targetOrg) {
      targetOrg.children.push({
        key: `group-${groupInfo.GroupNo}`,
        groupNo: groupInfo.GroupNo,
        label: groupInfo.GroupName,
        header: 'group',
        organizationName: targetOrg.label,
      });
    }
  }
  groupTreeData.value[0].children = allOrgs;
};

const fetchSelfGroupList = async () => {
  const formData = new FormData();
  formData.append('Type', 'All');
  formData.append('Count', '99999');
  formData.append('Desc', '1');

  const res = await store.dispatch('group/getGroupList', formData);
  if (!res.Result) return;

  const selfGroup = res.Message.filter(
    (x) => x.OrganizationNo === authMeta.value.OrganizationNo,
  ).map((x) => ({
    key: `group-${x.GroupNo}`,
    groupNo: x.GroupNo,
    label: x.GroupName,
    header: 'group',
    organizationName: authMeta.value.OrganizationName,
  }));

  groupTreeData.value[0].children = [
    {
      key: `org-${authMeta.value.OrganizationNo}`,
      organizationNo: authMeta.value.OrganizationNo,
      label: authMeta.value.OrganizationName,
      children: selfGroup,
    },
  ];
};

const handleGroupEdit = (node) => {
  editGroupNo.value = node.groupNo;
  editGroupDialogShow.value = true;
};

const handleAdd = () => {
  router.push({ name: 'AddUser' });
};

const handleEdit = (val) => {
  router.push({ name: 'editUser', params: val });
};

const handleGroupViewChange = async (node) => {
  const formData = new FormData();
  formData.append('GroupNo', node.groupNo);
  formData.append('Exclude', 0);

  const res = await store.dispatch('user/getMemberByGroupNo', formData);
  if (res.Result) {
    tableListData.value.List = res.List.map((x) => ({
      ...x,
      OrganizationName: node.organizationName,
    }));
  }
};

const fetchGroupListByMemberAcc = () => {
  if (authMeta.value.MemberAcc === 'admin') {
    fetchAllGroupList();
    return;
  }
  fetchSelfGroupList();
};

const fetchUserListByMemberAcc = () => {
  if (authMeta.value.MemberAcc === 'admin') {
    fetchAllUserList();
    return;
  }
  fetchSelfUserList();
};

onMounted(() => {
  pagination.value.rowsPerPage = rowsPerPage.value;
  fetchUserListByMemberAcc();
  fetchGroupListByMemberAcc();
  $_fetchOrgListOption();
});
</script>

<style lang="scss" scoped>
.box-border {
  border-bottom: 1px solid $blue7;
}
</style>
