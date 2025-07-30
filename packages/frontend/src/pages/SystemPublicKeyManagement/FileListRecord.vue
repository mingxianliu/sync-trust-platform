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
    加密檔案列表
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
              <div :class="col.value.classes">
                {{ col.value.label }}
              </div>
            </template>

            <template
              v-else-if="col.name === 'IPFSHash' || col.name === 'Blockchain'"
            >
              <div class="cursor-pointer">
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
                @click="handleIpfs(receive[col.name], col.name)"
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

            <template
              v-else-if="
                col.name === 'ReceiveOrgName' || col.name === 'ReceiveName'
              "
            >
              {{ receive[col.name] }}
            </template>
          </q-td>
        </q-tr>
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

const memberNo = computed(() => route.params.memberNo);

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
    align: 'center',
    field: 'Version',
    sortable: false,
  },
  {
    name: 'CreateTime',
    label: '上鏈日期',
    align: 'left',
    field: 'CreateTime',
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
    name: 'ReceiveOrgName',
    label: '保存組織',
    align: 'left',
    field: 'ReceiveOrgName',
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
  formData.append('MemberNo', memberNo.value);
  formData.append('Page', pagination.value.page - 1);
  formData.append('Count', rowsPerPage.value);

  store
    .dispatch('systemPublicKey/getSystemKeyFileList', formData)
    .then((res) => {
      tableListData.value.List = res.List;
    });
};

const handleAction = (row) => {
  router.push({
    name: 'systemPublicKeySpecificFile',
    params: {
      fileNo: row.FileNo,
    },
  });
};

const handleBack = () => {
  router.push({
    name: 'systemPublicKey',
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
