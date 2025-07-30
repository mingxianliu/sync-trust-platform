<template>
  <div>
    <p class="text-blue6 text-bold">解碼檔案</p>
    <div class="row">
      <q-card class="col-5">
        <q-card-section class="row">
          <FormGroup :all-data="fileData" class="row col-9" />
          <q-btn
            color="main-color"
            label="執行解碼"
            class="col btn-height self-center q-ml-md q-mt-sm"
            unelevated
            @click="handleDecrypt(null)"
          />
        </q-card-section>
      </q-card>
    </div>

    <p class="text-blue6 text-bold q-mt-lg">可數據檔檢視清單</p>
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
        :rows="tableListDataFilter.List"
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
                    v-if="isAdmin && props.row.receiveList.length > 0"
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
              <template v-if="col.name === 'download'">
                <div
                  v-if="
                    props.row.Flag === '0' &&
                    props.row.MemberReceiveNo === meta?.MemberNo
                  "
                  class="text-center"
                >
                  私鑰已變更
                </div>
                <div
                  v-else-if="
                    props.row.Flag === '1' &&
                    isFileEncryptComplete(props.row) &&
                    props.row.MemberReceiveNo === meta?.MemberNo
                  "
                  class="text-center"
                >
                  <q-btn
                    dense
                    round
                    unelevated
                    color="indigo-1"
                    text-color="main-color"
                    icon="eva-download-outline"
                    class="q-mr-md"
                    @click="fetchDownload(props.row)"
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

              <template v-else-if="col.name === 'ReceiveName'">
                {{ receive[col.name] }}
              </template>
            </q-td>
          </q-tr>
        </template>
      </q-table>
    </q-card>
  </div>
  <Progress
    v-model="dialogShow"
    :value="dialogShow"
    :progress-info="progress"
    :status="progressStatus"
    @close="dialogShow = false"
    @update-progress-info="
      () => {
        progress = 0;
      }
    "
    @upload-stop="
      () => {
        isStop = true;
      }
    "
  />
  <ViewDialog
    v-model="viewDialog"
    :value="viewDialog"
    :title="'IPFS區塊鏈詳細資訊'"
    :data="IPFSData"
    @close="viewDialog = false"
  />
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import FormGroup from 'src/components/FormGroup';
import Progress from 'src/components/Progress';
import ViewDialog from 'src/components/ViewDialog';
import DownloadDialog from 'src/pages/TakeFile/DownloadDialog';

import { useStore } from 'vuex';
import {
  $_successNotify,
  $_errorNotify,
  $_permission,
  $_handleDownload,
} from 'src/mixin/common';
import { $_handleFile } from 'src/mixin/key';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';

const router = useRouter();
const store = useStore();
const $q = useQuasar();

const dialogShow = ref(false);
const progress = ref(0);
const progressStatus = ref(2);
const isStop = ref(false);
const viewDialog = ref(false);
const IPFSData = ref({});

const fileData = ref({
  file: {
    label: '檔名',
    type: 'file',
    val: null,
    col: 'col',
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
    id: 1,
    label: '發送組織',
    val: null,
    type: 'select',
    options: [],
    col: 'col',
  },
  group: {
    id: 3,
    label: '發送群組',
    val: null,
    type: 'select',
    options: [],
    col: 'col',
  },
  user: {
    id: 2,
    label: '發送人',
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
        label: '已解碼',
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
    name: 'SenderOrgName',
    label: '發送組織',
    align: 'left',
    field: 'SenderOrgName',
    sortable: false,
  },
  {
    name: 'SenderName',
    label: '發送人',
    align: 'left',
    field: 'SenderName',
    classes: 'ellipsis',
    sortable: false,
  },
  {
    name: 'ReceiveName',
    label: '接收人',
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
          return { label: '已解碼', classes: 'is-decrypted' };
      }
    },
  },
  {
    name: 'download',
    label: '下載解碼',
    align: 'content',
    sortable: false,
  },
  {
    name: 'actions',
    label: '存取紀錄',
    align: 'content',
    sortable: false,
  },
]);
const tableListData = ref({
  List: [],
});
let pagination = ref({
  page: 1,
  // rowsNumber: null, // 看分頁怎麼做
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

const meta = computed(() => {
  return store.getters['app/getAuthMeta'];
});

const isAdmin = computed(() => {
  return $_permission(meta) === 'admin';
});

const tableListDataFilter = computed(() => {
  return {
    List: tableListData.value.List.filter((row) => isFileEncryptComplete(row)),
  };
});

const isFileEncryptComplete = (row) => {
  return !row.Files.match(/^encrypt:/) && row.EncodeStatus === '1';
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
        Exclude === '0'
          ? (fileSearchBar.value.user.options = list)
          : (fileData.value.user.options = list);
      }
    });
};
const changeOrg = (data) => {
  if (data.val === null && data.id) {
    handleClearUserList(data.id);
  }

  if (data.val === null) return;

  switch (data.id) {
    case 1:
      fileSearchBar.value.group.val = null;
      fetchOrganizeGroupByOrgNo(data.val.value);
      fetchOrganizeMember(data.val.value, '0');
      break;
    case 3:
      fetchGroupMember(data.val.value, '0');
      break;
    default:
      break;
  }
};
const handleClearUserList = (id) => {
  switch (id) {
    case 1:
      fileSearchBar.value.user.val = null;
      fileSearchBar.value.user.options = [];
      break;
    case 5:
      fileSearchBar.value.user.val = null;
      fetchOrganizeMember(fileSearchBar.value.get_group.val.value, '0');
    default:
      break;
  }
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

const fetchList = () => {
  const { file_name, get_group, status, user, group } = fileSearchBar.value;

  const formData = new FormData();
  formData.append('type', 'receive');
  formData.append('IsAdmin', meta.value?.MemberAcc === 'admin' ? '1' : '0');
  formData.append('FileName', file_name.val || '');
  formData.append('OrgNo', get_group.val?.value || '');
  formData.append('MemberNo', user.val?.value || '');
  formData.append('GroupNo', group.val?.value || '');

  formData.append('Status', status.val?.value || '');
  return store
    .dispatch('sentFile/getFileList', formData)
    .then((res) => {
      tableListData.value = res;
      // pagination.value.rowsNumber = tableListData.value.List.length;
    })
    .catch(() => {});
};

const handleAction = (val) => {
  store.commit('sentFile/setFileName', val.FileName);
  router.push({
    name: 'TakeFileRecord',
    params: {
      id: val.ID,
      fileNo: val.FileNo,
    },
  });
};

const handleIpfs = async (val, name, row) => {
  if (name === 'Blockchain') {
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
      $_errorNotify(err);
    });
};

const checkPrivateKey = () => {
  if (!privateKey.value) {
    dialogShow.value = false;
    $_errorNotify('私鑰尚未設定');
    return false;
  }
  return true;
};

const fetchDownload = (val) => {
  if (!checkPrivateKey()) return;

  progress.value = 0;
  progressStatus.value = 3;
  dialogShow.value = true;

  return store
    .dispatch('app/getPrivateFile', {
      data: val.Files,
      onDownloadProgress: (progressEvent) => {
        progress.value = Math.floor(
          (progressEvent.loaded / progressEvent.total) * 100,
        );
      },
    })
    .then((res) => {
      const blob = new Blob([res], { type: res.type });
      const url = URL.createObjectURL(blob);
      const file = new File([blob], val.FileName, {
        type: res.type,
      });

      handleStep(file, url, val.FileName, val.FileNo);
    })
    .catch(() => {
      $_errorNotify('下載失敗');
      dialogShow.value = false;
    });
};

const fetchDownloadStatus = (FileNo, Status) => {
  const formData = new FormData();
  formData.append('FileNo', FileNo);
  formData.append('Status', Status);

  return store.dispatch('sentFile/putDownloadStatus', formData);
};

const handleStep = (file, url, FileName, FileNo) => {
  $q.dialog({
    component: DownloadDialog,
    componentProps: {
      title: 'file download',
      message: '是否要下載並解碼',
      download: {
        label: '僅下載',
      },
      downloadWithDecrypt: {
        label: '下載並解碼',
      },
      cancel: {
        label: '取消',
      },
    },
    persistent: false,
  })
    .onOk(async ({ type }) => {
      if (type === 'download') {
        fetchDownloadStatus(FileNo, '1');
        $_handleDownload(url, FileName);
        $_successNotify('Successful Operation');
        window.URL.revokeObjectURL(url);
        dialogShow.value = false;
      } else if (type === 'downloadWithDecrypt') {
        dialogShow.value = false;
        await nextTick();
        fetchDownloadStatus(FileNo, '2');
        await handleDecrypt(file);
      }
    })
    .onCancel(() => {
      dialogShow.value = false;
    })
    .onDismiss(() => {
      fetchList();
    });
};

const privateKey = computed(() => {
  return store.getters['app/getPrivateKey'] || null;
});

const handleDecrypt = (targetFile) => {
  const { file } = fileData.value;
  const files = targetFile ?? file.val;

  // console.log('file test', file.val); // 查檔案細節
  if (!files) return $_errorNotify('尚未選擇上傳檔案');
  if (targetFile === null) {
    if (!checkPrivateKey()) return;
  }

  progressStatus.value = 2;
  dialogShow.value = true;
  progress.value = 0;

  $_handleFile(files, 3, null, (current, total) => {
    progress.value = Math.floor((current / total) * 100);
  })
    .then(() => {
      $_successNotify('Successful Operation');
    })
    .catch(() => {
      store.commit('app/setPrivateKey', '');
      $_errorNotify('解碼＆下載失敗,請確認私鑰是否正確＆重新上傳');
    })
    .finally(() => {
      progressStatus.value = 2;
      dialogShow.value = false;
    });
};

const fetchOrganizationMappingList = async () => {
  const formData = new FormData();
  formData.append('OrgNo', meta.value?.OrganizationNo);
  formData.append('Type', 'receiver');

  const res = await store.dispatch(
    'organizational/getOrganizationMappingList',
    formData,
  );
  return res.Message.map((x) => ({
    value: x.senderOrgNo,
    label: x.senderOrgName,
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
};

onMounted(async () => {
  pagination.value.rowsPerPage = rowsPerPage.value;
  await fetchOrganizeList();
  await fetchList();
});
</script>

<style lang="scss" scoped>
.ipfs-box {
  max-width: 13vw;
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
