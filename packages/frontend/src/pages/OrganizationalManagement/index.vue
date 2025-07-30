<template>
  <q-card>
    <q-card-section class="flex justify-between q-pt-md bg-indigo-1 box-border">
      <p class="q-mt-sm q-mb-none text-blue6 text-bold">組織管理</p>

      <div>
        <q-btn
          color="grey-4"
          text-color="blue6"
          label="新增組織"
          class="btn-height q-ml-md"
          unelevated
          icon="add"
          @click="handleAdd"
        />
      </div>
    </q-card-section>
    <q-card-section class="row q-pt-lg">
      <FormGroup
        :all-data="orgSearchBar"
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
      <template #body-cell="props">
        <q-td :props="props">
          <template
            v-if="['Send', 'Receive', 'Owner'].includes(props.col.name)"
          >
            <template v-if="props.value === '0'">
              <q-icon name="eva-minus-outline" color="grey-4" size="sm" />
            </template>

            <template v-else-if="props.value === '1'">
              <q-icon name="eva-checkmark-outline" color="green-6" size="sm" />
            </template>

            <template v-else-if="props.value === '2'">
              <q-icon name="eva-slash-outline" color="red-6" size="sm" />
            </template>
          </template>

          <template v-else-if="props.col.name === 'Stop'">
            <template v-if="props.value === '0'">
              <q-icon name="eva-checkmark-outline" color="green-6" size="sm" />
            </template>

            <template v-else-if="props.value === '1'">
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
  <AddDialog
    v-model="dialogShow"
    :value="dialogShow"
    @close="dialogShow = false"
    @update-list="fetchList"
  />
  <EditDialog
    v-model="editShow"
    :value="editShow"
    :data="editData"
    @close="editShow = false"
    @update-list="fetchList"
  />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useStore } from 'vuex';
import AddDialog from './AddDialog';
import EditDialog from './EditDialog';
import FormGroup from 'src/components/FormGroup';

const store = useStore();

const dialogShow = ref(false);
const editShow = ref(false);
const editData = ref({});

const columns = ref([
  {
    name: 'OrganizationNo',
    label: '組織代碼',
    align: 'left',
    field: 'OrganizationNo',
    style: 'max-width: 200px;',
    classes: 'ellipsis',
    sortable: true,
  },
  {
    name: 'OrganizationName',
    label: '組織名稱',
    align: 'left',
    field: 'OrganizationName',
    style: 'width: 400px;',
    sortable: true,
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
    label: '平台擁有者',
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
    name: 'actions',
    align: 'left',
    classes: '',
    sortable: false,
    style: 'width: 130px;',
  },
]);

const orgSearchBar = ref({
  searchOrg: {
    label: '組織名稱',
    type: 'input',
    val: null,
    col: 'col-4',
    isSearch: true,
  },
});

const tableListData = ref({});
let pagination = ref({
  page: 1,
  rowsNumber: null,
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
  formData.append('Page', pagination.value.page - 1);
  formData.append('Count', rowsPerPage.value);
  formData.append('Type', 0);
  if (orgSearchBar.value.searchOrg.val) {
    formData.append('Name', orgSearchBar.value.searchOrg.val);
  }
  if (pagination.value.sortBy) {
    formData.append('Order', pagination.value.sortBy);
  }
  formData.append('Desc', pagination.value.descending ? 1 : 0);

  store.dispatch('organizational/getOrganizationList', formData).then((res) => {
    if (res.Result) {
      tableListData.value.List = res.Message.filter((item) => {
        return item.OrganizationNo !== 'NSPOEncode';
      });
      pagination.value.rowsNumber = res.Total;
      // status 0: 停用 綠 1: 啟用 灰
      // IsSend 0: 無狀態 灰 1: 有權限 綠 2: 無權限 紅
    }
  });
  // 0: 無狀態 false  1: 有權限 true 2: 無權限 disable
};

const handleAdd = () => {
  dialogShow.value = true;
};

const handleEdit = (val) => {
  editData.value = val;
  editShow.value = true;
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
</style>
