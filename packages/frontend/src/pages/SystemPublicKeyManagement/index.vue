<template>
  <q-card>
    <q-card-section class="flex justify-between q-pt-md bg-indigo-1 box-border">
      <p class="q-mt-sm q-mb-none text-blue6 text-bold">系統公鑰列表</p>
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
          <template v-if="props.col.name === 'actions'">
            <div class="action-box">
              <q-btn
                flat
                rounded
                color="main-color"
                icon="eva-sync-outline"
                label="重建公鑰"
                :disable="
                  props.row.DisableTime !== '' || selfPrivateKey === '未設定'
                "
                @click="onCreateSystemKeyClick(props.row)"
              />
              <q-btn
                dense
                flat
                round
                color="blue7"
                icon="eva-arrow-forward-outline"
                class="q-mr-md"
                @click="onRecordButtonClick(props.row)"
              />
            </div>
          </template>
          <template v-if="props.col.name === 'isVerify'">
            {{
              props.row.DisableTime !== ''
                ? '已作廢'
                : props.value
                ? '已驗證'
                : '未驗證'
            }}
          </template>
          <template v-else-if="props.col.name === 'FileCount'">
            <a
              href="#"
              class="text-main-color"
              @click.prevent="onRecordButtonClick(props.row)"
            >
              {{ props.value }}
            </a>
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
import { ref, computed, onMounted } from 'vue';
import { useStore } from 'vuex';
import { useRouter } from 'vue-router';
import { $_errorNotify, $_successNotify } from 'src/mixin/common';
import { $_sha512 } from 'src/mixin/key';

const router = useRouter();
const store = useStore();

const columns = ref([
  {
    name: 'SerialNum',
    label: '序號',
    align: 'left',
    field: 'SerialNum',
    style: 'width: 25px;',
    sortable: false,
  },
  {
    name: 'CreateTime',
    label: '創建日期',
    align: 'left',
    field: 'CreateTime',
    style: 'width: 25px;',
    sortable: false,
  },
  {
    name: 'FileCount',
    label: '檔案數',
    align: 'center',
    field: 'FileCount',
    sortable: false,
  },
  {
    name: 'IPFSHash',
    label: 'IPFS區塊鏈Hash',
    align: 'left',
    field: 'IPFSHash',
    sortable: false,
    style: 'max-width: 500px;',
    classes: 'ipfs-box',
  },
  {
    name: 'isVerify',
    label: '加密公鑰驗證',
    align: 'center',
    field: 'isVerify',
    sortable: false,
  },
  {
    name: 'DisableTime',
    label: '作廢日期',
    align: 'left',
    field: 'DisableTime',
    sortable: false,
  },
  {
    name: 'actions',
    label: '操作',
    align: 'left',
    classes: 'action-box-container',
    sortable: false,
  },
]);
const tableListData = ref({});
let pagination = ref({
  page: 1,
  // rowsNumber: null,
});

const rowsPerPageOptions = computed(() => {
  return store.getters['app/getRowsPerPageOptions'];
});
const rowsPerPage = computed(() => {
  return store.getters['app/getRowsPerPage'];
});

const selfPrivateKey = computed(() => {
  return store.getters['app/getPrivateKey'] || '未設定';
});

const changePagination = (requestProp) => {
  if (requestProp?.pagination) {
    pagination.value = requestProp.pagination;
    store.commit('app/setRowsPerPage', requestProp.pagination);
  }
  fetchList();
};

const onRecordButtonClick = (row) => {
  router.push({
    name: 'systemPublicKeyFileList',
    params: {
      memberNo: row.MemberNo,
    },
  });
};

const onCreateSystemKeyClick = async (row) => {
  try {
    const isMemberEncryptingFiles = await checkMemberEncryptingFiles(
      row.MemberNo,
    );
    if (isMemberEncryptingFiles) {
      return $_errorNotify('目前有檔案正在加密中，請稍後再試');
    }
    const res = await createNewSystemKeyClick(row);
    if (res.Result) {
      $_successNotify('重建私鑰成功');
      fetchList();
      return;
    }
  } catch (error) {}
  $_errorNotify('重建私鑰失敗');
};

const checkMemberEncryptingFiles = async (memberNo) => {
  const formData = new FormData();
  formData.append('MemberNo', memberNo);

  const res = await store.dispatch(
    'systemPublicKey/checkMemberFileCount',
    formData,
  );
  return res?.Count > 0;
};

const createNewSystemKeyClick = (row) => {
  const formData = new FormData();
  formData.append('MemberNo', row.MemberNo);

  return store.dispatch('systemPublicKey/createNewSystemKey', formData);
};

const fetchList = async () => {
  const formData = new FormData();
  formData.append('Page', pagination.value.page - 1);
  formData.append('Count', rowsPerPage.value);

  const res = await store.dispatch(
    'systemPublicKey/getSystemKeyInfoList',
    formData,
  );
  if (res.Result) {
    tableListData.value.List = res.List.map((x) => {
      return {
        ...x,
        SerialNum: x.MemberNo.slice(-5),
      };
    });

    for (let i = 0; i < tableListData.value.List.length; i++) {
      const member = tableListData.value.List[i];
      const isVerify = (await $_sha512(member.PublicKey)) === member.Hash;
      tableListData.value.List[i].isVerify = isVerify;
    }
  }
};

onMounted(() => {
  pagination.value.rowsPerPage = rowsPerPage.value;
  fetchList();
});
</script>

<style lang="scss" scoped>
.box-border {
  border-bottom: 1px solid $blue7;
}
.ipfs-box {
  max-width: 12vw;
  word-break: break-all;
  white-space: normal;
  padding: 20px;
  background: $blue4;
  color: $blue2;
  box-shadow: 0px 0px 0px 10px white inset;
}
.action-box-container {
  padding-left: 16px;
}
.q-td.action-box-container {
  padding-left: 0;
}
</style>
