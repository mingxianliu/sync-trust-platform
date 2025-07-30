<template>
  <div>
    <UploadCard
      :organize-options="fileData.organize.options"
      @on-upload="fetchList"
    />

    <p class="text-blue6 text-bold q-mt-lg">已數據檔上鏈清單</p>
    <q-card>
      <q-card-section class="row q-pt-lg">
        <FormGroup
          :all-data="fileSearchBar"
          :is-title="false"
          class="row col-10"
          @handle-update="changeOrg"
        />
        <q-btn
          color="main-color"
          label="搜尋"
          class="col btn-height q-ml-md"
          unelevated
          icon="search"
          @click="fetchList"
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
        <template #body="props">
          <q-tr :props="props">
            <q-td v-for="col in props.cols" :key="col.name" :props="props">
              <template v-if="col.name === 'actions'">
                <div class="text-center">
                  <q-btn
                    v-if="props.row.receiveList.length > 0"
                    dense
                    flat
                    round
                    color="blue7"
                    :icon="props.row.expand ? 'remove' : 'add'"
                    class="q-mr-xs"
                    @click="props.row.expand = !props.row.expand"
                  />

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

              <template v-else-if="col.name === 'Status'">
                <div
                  :class="
                    col.value && col.value.classes ? col.value.classes : ''
                  "
                  style="min-width: 80px; white-space: nowrap"
                >
                  {{ (col.value && col.value.label) || '-' }}
                </div>
              </template>

              <template
                v-else-if="col.name === 'IPFSHash' || col.name === 'Blockchain'"
              >
                <div
                  class="cursor-pointer"
                  @click="handleIpfs(col.value, col.name, props.row)"
                >
                  {{ col.value }}
                </div>
              </template>
              <template v-else-if="col.name === 'ReceiveName'">
                {{ col.value }}
                <span class="other-receive">
                  {{
                    props.row.receiveList.length > 0
                      ? `+${props.row.receiveList.length}`
                      : ''
                  }}
                </span>
              </template>

              <template v-else>
                {{ col.value }}
              </template>
            </q-td>
          </q-tr>
          <q-tr
            v-for="receive in props.row.receiveList"
            v-show="props.row.expand"
            :key="receive.ID"
            :props="props"
            class="other-receiver-tr"
          >
            <q-td v-for="col in props.cols" :key="col.name" :props="props">
              <template v-if="col.name === 'Status'">
                <div
                  :class="
                    props.colsMap[col.name].format(receive[col.name]).classes
                  "
                >
                  {{ props.colsMap[col.name].format(receive[col.name]).label }}
                </div>
              </template>

              <template
                v-else-if="col.name === 'IPFSHash' || col.name === 'Blockchain'"
              >
                <div
                  class="cursor-pointer"
                  @click="handleIpfs(receive[col.name], col.name, receive)"
                >
                  {{ receive[col.name] }}
                </div>
              </template>

              <template
                v-else-if="
                  col.name === 'ReceiveOrgName' || col.name === 'ReceiveName'
                "
              >
                {{ receive[col.name] }}
              </template>

              <template v-else-if="col.name === 'actions'">
                <div class="other-receiver-action">
                  <q-btn
                    dense
                    flat
                    round
                    color="blue7"
                    icon="eva-arrow-forward-outline"
                    @click="handleAction(receive)"
                  />
                </div>
              </template>
            </q-td>
          </q-tr>
        </template>
      </q-table>
    </q-card>
  </div>
  <ViewDialog
    v-model="viewDialog"
    :value="viewDialog"
    :title="'IPFS區塊鏈詳細資訊'"
    :data="IPFSData"
    @close="viewDialog = false"
  />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import FormGroup from 'src/components/FormGroup';
import ViewDialog from 'src/components/ViewDialog';
import UploadCard from 'src/components/SentFile/UploadCard.vue';

import { useStore } from 'vuex';
import { $_errorNotify, $_permission } from 'src/mixin/common';
import { useRouter } from 'vue-router';

const router = useRouter();
const store = useStore();

const viewDialog = ref(false);
const IPFSData = ref({});

const fileData = ref({
  organize: {
    id: 1,
    label: '組織',
    val: null,
    type: 'select',
    options: [],
    col: 'col-4',
  },
});

const fileSearchBar = ref({
  file_name: {
    label: '搜尋檔名',
    type: 'input',
    val: null,
    col: 'col-4',
    isSearch: true,
  },
  get_group: {
    id: 3,
    label: '接收組織',
    val: null,
    type: 'select',
    options: [],
    col: 'col',
  },
  group: {
    id: 5,
    label: '接收群組',
    val: null,
    type: 'select',
    options: [],
    col: 'col',
  },
  user: {
    id: 4,
    label: '接收人',
    val: null,
    type: 'select',
    options: [],
    col: 'col',
  },
  status: {
    label: '接收狀態',
    val: null,
    type: 'select',
    options: [
      {
        label: '未接收',
        value: '0',
      },
      {
        label: '已下載',
        value: '1',
      },
      {
        label: '已解密',
        value: '2',
      },
    ],
    col: 'col',
  },
});

const columns = ref([
  {
    name: 'FileName',
    label: '檔名',
    align: 'left',
    field: 'FileName',
    style: 'max-width: 200px;',
    classes: 'ellipsis',
    sortable: false,
  },
  {
    name: 'Version',
    label: '版本',
    align: 'left',
    field: 'Version',
    sortable: false,
  },
  {
    name: 'CreateTime',
    label: '上鏈日期',
    align: 'left',
    field: 'CreateTime',
    sortable: false,
    // format: (val) => {
    //   return val;
    // },
  },
  {
    name: 'ReceiveOrgName',
    label: '保存組織',
    align: 'left',
    field: 'ReceiveOrgName',
    sortable: false,
  },
  {
    name: 'ReceiveName',
    label: '保管人',
    align: 'left',
    field: 'ReceiveName',
    classes: 'ellipsis',
    sortable: false,
  },
  {
    name: 'Blockchain',
    label: '區塊鏈位址',
    align: 'left',
    field: 'Blockchain',
    classes: 'ipfs-box',
    sortable: false,
  },
  {
    name: 'IPFSHash',
    label: 'IPFS位置',
    align: 'left',
    field: 'IPFSHash',
    classes: 'ipfs-box',
    sortable: false,
  },
  {
    name: 'Status',
    label: '保存狀態',
    align: 'left',
    field: 'Status',
    sortable: false,
    format: (val) => {
      switch (val) {
        case '0':
          return { label: '未接收', classes: 'is-unreceived' };
        case '1':
          return { label: '已下載', classes: 'is-download' };
        case '2':
          return { label: '已解密', classes: 'is-decrypted' };
      }
    },
  },
  {
    name: 'actions',
    label: '存取紀錄',
    align: 'content',
    sortable: false,
  },
]);
const tableListData = ref({});
let pagination = ref({
  page: 1,
  // rowsNumber: null, // 看分頁怎麼做
});

const meta = computed(() => {
  return store.getters['app/getAuthMeta'];
});

const rowsPerPageOptions = computed(() => {
  return store.getters['app/getRowsPerPageOptions'];
});
const rowsPerPage = computed(() => {
  return store.getters['app/getRowsPerPage'];
});

const changePagination = (requestProp) => {
  if (requestProp?.pagination) {
    pagination.value = requestProp.pagination;
    store.commit('app/setRowsPerPage', requestProp.pagination);
  }
  fetchList();
};

const fetchList = () => {
  const { file_name, get_group, status, user, group } = fileSearchBar.value;

  const formData = new FormData();
  formData.append('type', 'send');
  formData.append('IsAdmin', meta.value?.MemberAcc === 'admin' ? '1' : '0');
  formData.append('FileName', file_name.val || '');
  formData.append('OrgNo', get_group.val?.value || '');
  formData.append('MemberNo', user.val?.value || '');
  formData.append('Status', status.val?.value || '');
  formData.append('GroupNo', group.val?.value || '');

  return store
    .dispatch('sentFile/getFileList', formData)
    .then((res) => {
      tableListData.value = res;
      // pagination.value.rowsNumber = tableListData.value.List.length;
    })
    .catch(() => {});
};

const fetchOrganizeMember = (OrgNo, Exclude) => {
  const formData = new FormData();
  formData.append('OrgNo', OrgNo);
  formData.append('Exclude', Exclude);
  // Exclude: 1 代表不包含自己 0 代表包含自己
  return store
    .dispatch('sentFile/getOrganizationMember', formData)
    .then((res) => {
      if (res.Result) {
        const list = res.List.map((item) => {
          return {
            label: item.MemberName,
            value: item.MemberNo,
          };
        });
        if (Exclude === '0') {
          fileSearchBar.value.user.options = list;
        }
      }
    });
};

const fetchGroupMember = async (groupNo, Exclude) => {
  const formData = new FormData();
  formData.append('GroupNo', groupNo);
  formData.append('Exclude', Exclude);

  // Exclude: 1 代表不包含自己 0 代表包含自己
  const res = await store.dispatch('user/getMemberByGroupNo', formData);
  if (res.Result) {
    const list = res.List.map((item) => ({
      label: item.MemberName,
      value: item.MemberNo,
    }));
    fileSearchBar.value.user.options = list;
  }
};

const fetchOrganizeGroupByOrgNo = async (OrgNo) => {
  const formData = new FormData();
  formData.append('Type', 'all');
  formData.append('Count', '99999');
  formData.append('Desc', '1');
  formData.append('OrgNo', OrgNo);

  const res = await store.dispatch('group/getGroupList', formData);
  if (res.Result) {
    const list = res.Message.map((item) => ({
      label: item.GroupName,
      value: item.GroupNo,
    }));
    fileSearchBar.value.group.options = list;
  }
};

const changeOrg = (data) => {
  if (data.val === null && data.id) {
    handleClearUserList(data.id);
  }

  if (data.val === null) return;

  switch (data.id) {
    case 3:
      fileSearchBar.value.group.val = null;
      fetchOrganizeMember(data.val.value, '0');
      fetchOrganizeGroupByOrgNo(data.val.value);
      break;
    case 5:
      return fetchGroupMember(data.val.value, '0');
  }
};

const handleClearUserList = (id) => {
  switch (id) {
    case 3:
      fileSearchBar.value.user.val = null;
      fileSearchBar.value.user.options = [];
      fileSearchBar.value.group.val = null;
      break;
    case 5:
      fileSearchBar.value.user.val = null;
      fetchOrganizeMember(fileSearchBar.value.get_group.val.value, '0');
      break;
    default:
      break;
  }
};

const handleAction = (val) => {
  store.commit('sentFile/setFileName', val.FileName);
  router.push({
    name: 'SentFilesRecord',
    params: {
      id: val.ID,
      fileNo: val.FileNo,
    },
  });
};

const handleIpfs = async (val, name, row) => {
  if (name === 'Blockchain') {
    IPFSData.value = {};
    await fetchBlockchainInfo(row.BlockchainTrans);
  } else if (name === 'IPFSHash') {
    IPFSData.value = {
      hash: val,
      other: 'other info',
    };
  }
  viewDialog.value = true;
};

const fetchBlockchainInfo = (val) => {
  const formData = new FormData();
  formData.append('Hash', val);

  return store
    .dispatch('sentFile/getBlockchainInfo', formData)
    .then((res) => {
      IPFSData.value = res.Result;
    })
    .catch((err) => {
      if (err?.code === 500) {
        return $_errorNotify('發生錯誤');
      }
      $_errorNotify(err);
    });
};

const fetchOrganizationMappingList = async () => {
  const formData = new FormData();
  formData.append('OrgNo', meta.value?.OrganizationNo);
  formData.append('Type', 'sender');

  const res = await store.dispatch(
    'organizational/getOrganizationMappingList',
    formData,
  );
  return res.Message.map((x) => ({
    value: x.receiverOrgNo,
    label: x.receiverOrgName,
  }));
};

const fetchAllOrganizeList = async () => {
  const formData = new FormData();
  formData.append('Type', 'all');
  formData.append('Count', '99999');
  formData.append('Desc', 1);

  const res = await store.dispatch(
    'organizational/getOrganizationList',
    formData,
  );
  return res.Message.map((x) => ({
    value: x.OrganizationNo,
    label: x.OrganizationName,
  }));
};

const fetchOrganizeList = async () => {
  let orgList = [];
  if (meta.value?.MemberAcc === 'admin') {
    orgList = await fetchAllOrganizeList();
  } else {
    orgList = await fetchOrganizationMappingList();
  }
  fileSearchBar.value.get_group.options = orgList;
  fileData.value.organize.options = orgList;
};

onMounted(async () => {
  pagination.value.rowsPerPage = rowsPerPage.value;
  await fetchOrganizeList();
  await fetchList();
});
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
.other-receive {
  color: rgb(28, 146, 66);
}
.other-receiver-action {
  display: flex;
  justify-content: center;
}
.other-receiver-tr {
  $bg: #f7f7f7;
  background-color: $bg;
  .ipfs-box {
    box-shadow: 0px 0px 0px 10px $bg inset;
  }
}
</style>
