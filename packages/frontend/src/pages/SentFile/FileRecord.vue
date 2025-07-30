<template>
  <div>
    <q-btn
      icon="eva-arrow-back-outline"
      size="md"
      color="blue7"
      flat
      round
      @click="handleBack"
    />
    {{ fileName }} 存取記錄
  </div>
  <q-card class="q-mt-md">
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
          <template v-if="props.col.name === 'Status'">
            <div :class="props.value.classes">
              {{ props.value.label }}
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
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useStore } from 'vuex';

const store = useStore();
const router = useRouter();
const route = useRoute();

const routerName = computed(() => route.name);
const fileNo = computed(() => route.params.fileNo);
const fileName = computed(() => {
  return store.getters['sentFile/getFileName'];
});

const columns = ref([
  {
    name: 'Version',
    label: '版本',
    align: 'left',
    field: 'Version',
    sortable: false,
  },
  {
    name: 'SenderOrgName',
    label: '發送組織',
    align: 'left',
    field: 'SenderOrgName',
    style: 'max-width: 200px;',
    classes: 'ellipsis',
    sortable: false,
  },
  {
    name: 'SenderName',
    label: '發送人',
    align: 'left',
    field: 'SenderName',
    sortable: false,
  },
  {
    name: 'CreateTime',
    label: '上鏈日期',
    align: 'left',
    field: 'CreateTime',
    style: 'width: 250px;',
    sortable: false,
  },
  {
    name: 'ReceiveOrgName',
    label: '保存組織',
    align: 'left',
    field: 'ReceiveOrgName',
    sortable: false,
    style: 'width: 120px;',
  },
  {
    name: 'ReceiveName',
    label: '接收人',
    align: 'left',
    field: 'ReceiveName',
    sortable: false,
    style: 'width: 120px;',
  },
  {
    name: 'Status',
    label: '保存狀態',
    align: 'left',
    field: 'Status',
    classes: 'ellipsis',
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
    name: 'UpdateTime',
    label: '狀態日期',
    align: 'left',
    field: 'UpdateTime',
    sortable: false,
    style: 'width: 120px;',
  },
  // {
  //   name: 'Blockchain',
  //   label: '區塊鏈位置',
  //   align: 'left',
  //   field: 'Blockchain',
  //   classes: 'ipfs-box',
  //   sortable: false,
  // },
  {
    name: 'Blockchain',
    label: 'IPFS區塊鏈Hash',
    align: 'left',
    field: 'Blockchain',
    classes: 'ipfs-box',
    sortable: false,
  },
]);
const tableListData = ref({});
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

const fetchList = () => {
  const formData = new FormData();
  formData.append('FileNo', fileNo.value);

  store.dispatch('sentFile/getRecordList', formData).then((res) => {
    tableListData.value = res;
  });
};

const handleBack = () => {
  router.push({
    name: routerName.value === 'SentFilesRecord' ? 'SentFiles' : 'TakeFile',
  });
};

onMounted(() => {
  pagination.value.rowsPerPage = rowsPerPage.value;
  fetchList();
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
</style>
