<template>
  <div class="data-records-page">
    <!-- 測試區域移除，以下為正式內容 -->
    <div
      class="page-header bg-indigo-1 text-blue6 q-pa-md q-mb-md rounded-borders shadow-2 flex items-center"
    >
      <q-icon name="eva-bar-chart-outline" size="32px" class="q-mr-md" />
      <div>
        <h2 class="page-title text-h5 q-mb-xs">數據資料查詢</h2>
        <p class="page-subtitle text-subtitle2">查看所有上鏈的數據記錄</p>
      </div>
    </div>

    <q-card class="data-records-card q-mb-md">
      <q-card-section class="row items-center q-gutter-md">
        <q-input
          v-model="searchKeyword"
          label="關鍵字搜尋（描述/完整內容）"
          dense
          clearable
          debounce="300"
          class="col-4"
        />
        <q-btn
          color="primary"
          icon="refresh"
          label="重新整理"
          :loading="loading"
          @click="fetchDataRecords"
        />
        <div class="col text-right text-grey-7">
          共 {{ filteredRecords.length }} 筆記錄
        </div>
      </q-card-section>
      <q-card-section>
        <q-table
          v-model:pagination="pagination"
          :rows="sortedRecords"
          :columns="columns"
          :loading="loading"
          row-key="txHash"
          flat
          bordered
          class="data-table"
          table-layout="fixed"
          width="100%"
          @request="onTableRequest"
        >
          <!-- 內容欄位（只保留 description，並用 getContentPreview 處理） -->
          <template #body-cell-description="props">
            <q-td :props="props">
              <div class="content-cell-wrapper">
                <span class="ellipsis-cell large-text content-preview">
                  <q-tooltip
                    class="large-tooltip"
                    :style="'font-size: 1.2rem;'"
                  >
                    {{ stripTags(props.row.description) }}
                  </q-tooltip>
                  {{ getContentPreview(props.row.description) }}
                </span>
              </div>
            </q-td>
          </template>

          <!-- 時間欄位 -->
          <template #body-cell-timestamp="props">
            <q-td :props="props">
              {{ formatTimestamp(props.value) }}
            </q-td>
          </template>

          <!-- 交易哈希欄位 -->
          <template #body-cell-txHash="props">
            <q-td :props="props">
              <span class="ellipsis-cell large-text">
                <q-tooltip
                  class="large-tooltip"
                  :style="'font-size: 1.2rem;'"
                  >{{ props.value }}</q-tooltip
                >
                {{ props.value.slice(0, 6) }}...{{ props.value.slice(-4) }}
              </span>
            </q-td>
          </template>

          <!-- 上傳者欄位 -->
          <template #body-cell-uploader="props">
            <q-td :props="props">
              <span class="ellipsis-cell large-text">
                <q-tooltip
                  class="large-tooltip"
                  :style="'font-size: 1.2rem;'"
                  >{{ props.value }}</q-tooltip
                >
                {{ props.value.slice(0, 6) }}...{{ props.value.slice(-4) }}
              </span>
            </q-td>
          </template>

          <!-- 狀態欄位 -->
          <template #body-cell-status="props">
            <q-td :props="props">
              <q-chip
                class="status-chip"
                :color="
                  props.value === '成功' || props.value === 'success'
                    ? 'positive'
                    : 'negative'
                "
                text-color="white"
                :label="
                  props.value === 'success'
                    ? '成功'
                    : props.value === 'failed'
                    ? '失敗'
                    : props.value
                "
                size="md"
              />
            </q-td>
          </template>

          <!-- 完整內容欄位 -->
          <template #body-cell-content="props">
            <q-td :props="props">
              <div class="content-cell-wrapper">
                <span class="ellipsis-cell large-text content-preview">
                  <q-tooltip
                    class="large-tooltip"
                    :style="'font-size: 1.2rem;'"
                  >
                    {{ stripTags(props.row.content) }}
                  </q-tooltip>
                  {{ getContentPreview(props.row.content) }}
                </span>
              </div>
            </q-td>
          </template>

          <!-- 操作欄位 -->
          <template #body-cell-actions="props">
            <q-td :props="props">
              <q-btn
                flat
                dense
                color="primary"
                icon="visibility"
                @click="viewRecordDetails(props.row)"
              >
                <q-tooltip>查看詳情</q-tooltip>
              </q-btn>
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <!-- 完整內容對話框 -->
    <q-dialog v-model="showContentDialog">
      <q-card
        class="full-content-dialog"
        style="max-width: 600px; min-width: 320px"
      >
        <q-card-section class="dialog-header full-content-header">
          <div class="row items-center justify-between">
            <div class="dialog-title-section">
              <h4 class="full-content-title">完整內容</h4>
              <p class="full-content-subtitle">查看完整的數據記錄內容</p>
            </div>
            <q-btn
              flat
              round
              icon="close"
              color="grey-6"
              size="lg"
              @click="showContentDialog = false"
            />
          </div>
        </q-card-section>
        <q-card-section class="dialog-content full-content-body">
          <div class="content-wrapper">
            <div class="content-header">
              <q-chip
                color="primary"
                text-color="white"
                icon="info"
                label="內容預覽"
              />
              <span class="content-length"
                >字元數: {{ selectedContent.length }}</span
              >
            </div>
            <pre class="content-display full-content-text">{{
              stripTags(selectedContent).trim()
            }}</pre>
            <div class="content-actions">
              <q-btn
                color="primary"
                icon="content_copy"
                label="複製內容"
                unelevated
                @click="copyContent"
              />
              <q-btn
                color="grey-6"
                icon="close"
                label="關閉"
                outline
                @click="showContentDialog = false"
              />
            </div>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- 記錄詳情對話框 -->
    <q-dialog
      v-model="showDetailsDialog"
      persistent
      transition-show="fade"
      transition-hide="fade"
    >
      <q-card
        ref="detailsDialogCard"
        class="details-dialog-card"
        style="
          max-width: 1600px;
          min-width: 900px;
          padding: 64px 64px 48px 64px;
        "
      >
        <q-card-section class="dialog-header details-dialog-header">
          <div class="row items-center justify-between">
            <h4 class="details-dialog-title">記錄詳情</h4>
            <div class="row items-center q-gutter-sm">
              <q-btn
                icon="image"
                color="primary"
                flat
                round
                :disable="downloadingDetailsPng"
                :loading="downloadingDetailsPng"
                title="下載完整截圖 (包含所有內容)"
                @click.stop="downloadDetailsPng"
              />
              <q-btn
                icon="content_copy"
                color="secondary"
                flat
                round
                title="複製完整內容到剪貼簿"
                @click.stop="copyFullContent"
              />
              <q-btn
                flat
                round
                icon="close"
                @click="showDetailsDialog = false"
              />
            </div>
          </div>
        </q-card-section>
        <q-card-section class="dialog-content details-dialog-content">
          <div
            v-if="selectedRecord"
            class="record-details details-dialog-details"
          >
            <div class="detail-item">
              <label>交易哈希:</label>
              <code>{{ selectedRecord.txHash }}</code>
            </div>
            <div class="detail-item">
              <label>區塊號:</label>
              <span>{{ selectedRecord.blockNumber }}</span>
            </div>
            <div class="detail-item">
              <label>上鏈時間:</label>
              <span>{{ formatTimestamp(selectedRecord.timestamp) }}</span>
            </div>
            <div class="detail-item">
              <label>上傳者:</label>
              <span>{{ selectedRecord.uploader }}</span>
            </div>
            <div class="detail-item">
              <label>描述:</label>
              <span>{{ stripTags(selectedRecord.description).trim() }}</span>
            </div>
            <div class="detail-item">
              <label>狀態:</label>
              <q-chip
                :color="
                  selectedRecord.status === 'success' ? 'positive' : 'negative'
                "
                text-color="white"
                :label="selectedRecord.status === 'success' ? '成功' : '失敗'"
              />
            </div>
            <div class="detail-item full-width">
              <span class="record-detail-label">完整內容：</span>
              <div class="record-detail-content">
                <div
                  v-for="(value, key) in parsedContent"
                  :key="key"
                  class="content-item"
                >
                  <div class="content-key">{{ key }}：</div>
                  <div class="content-value">
                    <pre v-if="typeof value === 'object' && value !== null">{{
                      formatValue(value)
                    }}</pre>
                    <span v-else>{{ value }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useQuasar } from 'quasar';
import { dataRecordsApi, apiConfig } from 'src/services/api.js';
import { useStore } from 'vuex';
import { LocalStorage, Cookies } from 'quasar';
import html2canvas from 'html2canvas';

const $q = useQuasar();
const store = useStore();

// 響應式數據
const dataRecords = ref([]);
const loading = ref(false);
const totalRecords = ref(0);
const showContentDialog = ref(false);
const showDetailsDialog = ref(false);
const selectedContent = ref('');
const selectedRecord = ref(null);
const currentTime = ref(new Date().toLocaleString('zh-TW'));
const isDevelopment = ref(process.env.NODE_ENV === 'development');
const apiConfigStr = ref(JSON.stringify(apiConfig, null, 2));

const searchKeyword = ref('');

// 關鍵字過濾
const filteredRecords = computed(() => {
  if (!searchKeyword.value) return dataRecords.value;
  const kw = searchKeyword.value.toLowerCase();
  return dataRecords.value.filter(
    (row) =>
      (row.description && row.description.toLowerCase().includes(kw)) ||
      (row.content && row.content.toLowerCase().includes(kw)),
  );
});

// 計算登入狀態和權限
const isLoggedIn = computed(() => {
  const authToken = Cookies.get('_nspoid');
  const authMeta = LocalStorage.getItem('_nspometa');
  return !!(authToken && authMeta);
});

const userPermissions = computed(() => {
  const permissions = LocalStorage.getItem('_per');
  if (!permissions) return [];
  if (Array.isArray(permissions)) return permissions;
  try {
    const parsed = JSON.parse(permissions);
    if (Array.isArray(parsed)) return parsed;
    if (typeof parsed === 'string') return parsed.split(',');
    return [];
  } catch (e) {
    if (typeof permissions === 'string') return permissions.split(',');
    return [];
  }
});

// 分頁設定
const pagination = ref({
  sortBy: 'timestamp',
  descending: true,
  page: 1,
  rowsPerPage: 10,
  rowsNumber: 0,
});

// 表格欄位定義
const columns = [
  {
    name: 'description',
    label: '描述',
    field: 'description',
    align: 'left',
    sortable: false,
    style:
      'min-width: 160px; max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;',
  },
  {
    name: 'content',
    label: '完整內容',
    field: 'content',
    align: 'left',
    sortable: false,
    style:
      'min-width: 160px; max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;',
  },
  {
    name: 'timestamp',
    label: '上鏈時間',
    field: 'timestamp',
    align: 'left',
    sortable: true,
    style:
      'width: 160px; min-width: 120px; max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;',
  },
  {
    name: 'txHash',
    label: '交易哈希',
    field: 'txHash',
    align: 'left',
    sortable: false,
    style:
      'width: 160px; min-width: 120px; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;',
  },
  {
    name: 'blockNumber',
    label: '區塊號',
    field: 'blockNumber',
    align: 'center',
    sortable: true,
    style:
      'width: 100px; min-width: 80px; max-width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;',
  },
  {
    name: 'uploader',
    label: '上傳者',
    field: 'uploader',
    align: 'left',
    sortable: false,
    style:
      'width: 140px; min-width: 100px; max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;',
  },
  {
    name: 'status',
    label: '狀態',
    field: 'status',
    align: 'center',
    sortable: false,
    style:
      'width: 80px; min-width: 60px; max-width: 100px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;',
  },
  {
    name: 'actions',
    label: '操作',
    field: 'actions',
    align: 'center',
    sortable: false,
    style:
      'width: 80px; min-width: 60px; max-width: 100px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;',
  },
];

const sortedRecords = computed(() => {
  let rows = filteredRecords.value.slice();
  const { sortBy, descending } = pagination.value;
  if (sortBy) {
    rows.sort((a, b) => {
      if (a[sortBy] === b[sortBy]) return 0;
      if (descending) {
        return a[sortBy] < b[sortBy] ? 1 : -1;
      } else {
        return a[sortBy] > b[sortBy] ? 1 : -1;
      }
    });
  }
  return rows;
});

// 方法
const fetchDataRecords = async () => {
  loading.value = true;
  try {
    // 直接使用 dataRecordsApi
    const result = await dataRecordsApi.getAllRecords();
    const records = result.records || [];
    const total = result.total || 0;

    dataRecords.value = records;
    totalRecords.value = total;
    pagination.value.rowsNumber = total;

    $q.notify({
      type: 'positive',
      message: `成功獲取 ${total} 筆記錄`,
    });
  } catch (error) {
    console.error('獲取數據記錄失敗:', error);
    $q.notify({
      type: 'negative',
      message: '獲取數據記錄失敗: ' + error.message,
    });
  } finally {
    loading.value = false;
  }
};

const getContentPreview = (content) => {
  let text = stripTags(content || '').trim();
  if (!text) return '-';
  if (text.length > 50) {
    return text.slice(0, 50) + '...';
  }
  return text;
};

const formatTimestamp = (timestamp) => {
  const date = new Date(timestamp * 1000);
  return date.toLocaleString('zh-TW');
};

const showFullContent = (content) => {
  selectedContent.value = content;
  showContentDialog.value = false;
  setTimeout(() => {
    showContentDialog.value = true;
  }, 10);
};

const viewRecordDetails = (record) => {
  selectedRecord.value = record;
  showDetailsDialog.value = true;
};

// 複製內容到剪貼簿
const copyContent = async () => {
  try {
    await navigator.clipboard.writeText(selectedContent.value);
    $q.notify({
      type: 'positive',
      message: '內容已複製到剪貼簿',
      position: 'top',
      timeout: 2000,
    });
  } catch (error) {
    console.error('複製失敗:', error);
    $q.notify({
      type: 'negative',
      message: '複製失敗，請手動複製',
      position: 'top',
      timeout: 2000,
    });
  }
};

function onTableRequest(props) {
  pagination.value = props.pagination;
}

// 去除標籤的函數
function stripTags(str) {
  if (!str) return '';
  return str.replace(/<[^>]+>/g, '');
}

// 生命週期
onMounted(() => {
  fetchDataRecords();
});

const detailsDialogCard = ref(null);
const downloadingDetailsPng = ref(false);

function formatJson(val) {
  try {
    if (typeof val === 'string') {
      return JSON.stringify(JSON.parse(val), null, 2);
    } else if (typeof val === 'object') {
      return JSON.stringify(val, null, 2);
    }
    return val;
  } catch {
    return val;
  }
}

async function downloadDetailsPng() {
  if (!detailsDialogCard.value) return;
  downloadingDetailsPng.value = true;
  try {
    const el = detailsDialogCard.value.$el || detailsDialogCard.value;

    // 創建一個臨時的完整內容容器用於截圖
    const tempContainer = document.createElement('div');
    tempContainer.style.cssText = `
      position: fixed;
      top: -9999px;
      left: -9999px;
      width: 800px;
      background: white;
      padding: 20px;
      font-family: Arial, sans-serif;
      font-size: 14px;
      line-height: 1.5;
      color: #333;
      border: 1px solid #ddd;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    `;

    // 複製對話框內容
    const content = el.cloneNode(true);

    // 移除滾動限制，確保所有內容可見
    const scrollableElements = content.querySelectorAll(
      '.record-detail-content, .content-value pre',
    );
    scrollableElements.forEach((el) => {
      el.style.maxHeight = 'none';
      el.style.overflow = 'visible';
    });

    tempContainer.appendChild(content);
    document.body.appendChild(tempContainer);

    // 等待內容渲染
    await new Promise((resolve) => setTimeout(resolve, 100));

    // 截圖
    const canvas = await html2canvas(tempContainer, {
      backgroundColor: '#fff',
      width: 800,
      height: tempContainer.scrollHeight,
      scrollX: 0,
      scrollY: 0,
      useCORS: true,
      allowTaint: true,
    });

    // 清理臨時元素
    document.body.removeChild(tempContainer);

    // 下載圖片
    const link = document.createElement('a');
    link.href = canvas.toDataURL('image/png');
    link.download = `record_detail_${Date.now()}.png`;
    link.click();

    $q.notify({
      type: 'positive',
      message: '截圖已下載',
      position: 'top',
      timeout: 2000,
    });
  } catch (error) {
    console.error('截圖失敗:', error);
    $q.notify({
      type: 'negative',
      message: '截圖失敗: ' + error.message,
      position: 'top',
      timeout: 3000,
    });
  } finally {
    downloadingDetailsPng.value = false;
  }
}

// 複製完整內容到剪貼簿
async function copyFullContent() {
  if (!selectedRecord.value) return;

  try {
    let content = '';

    // 添加基本信息
    content += `交易哈希: ${selectedRecord.value.txHash}\n`;
    content += `區塊號: ${selectedRecord.value.blockNumber}\n`;
    content += `上鏈時間: ${formatTimestamp(selectedRecord.value.timestamp)}\n`;
    content += `上傳者: ${selectedRecord.value.uploader}\n`;
    content += `描述: ${stripTags(selectedRecord.value.description).trim()}\n`;
    content += `狀態: ${
      selectedRecord.value.status === 'success' ? '成功' : '失敗'
    }\n\n`;

    // 添加完整JSON內容
    content += `完整內容:\n`;
    content += JSON.stringify(parsedContent.value, null, 2);

    await navigator.clipboard.writeText(content);

    $q.notify({
      type: 'positive',
      message: '完整內容已複製到剪貼簿',
      position: 'top',
      timeout: 2000,
    });
  } catch (error) {
    console.error('複製失敗:', error);
    $q.notify({
      type: 'negative',
      message: '複製失敗，請手動複製',
      position: 'top',
      timeout: 2000,
    });
  }
}

const parsedContent = computed(() => {
  try {
    return typeof selectedRecord.value?.content === 'string'
      ? JSON.parse(selectedRecord.value.content)
      : selectedRecord.value?.content || {};
  } catch {
    return {};
  }
});

function formatValue(val) {
  if (Array.isArray(val)) {
    return val
      .map((v) => (typeof v === 'object' ? JSON.stringify(v, null, 2) : v))
      .join('\n');
  }
  if (typeof val === 'object' && val !== null) {
    return JSON.stringify(val, null, 2);
  }
  return val;
}

// 遞歸格式化複雜對象
function formatComplexValue(val, indent = 0) {
  const spaces = '  '.repeat(indent);

  if (Array.isArray(val)) {
    if (val.length === 0) return '[]';
    if (val.length === 1 && typeof val[0] === 'object') {
      return `[\n${spaces}  ${formatComplexValue(
        val[0],
        indent + 1,
      )}\n${spaces}]`;
    }
    return val.map((v) => formatComplexValue(v, indent)).join(', ');
  }

  if (typeof val === 'object' && val !== null) {
    const entries = Object.entries(val);
    if (entries.length === 0) return '{}';

    const formatted = entries
      .map(([key, value]) => {
        const formattedValue = formatComplexValue(value, indent + 1);
        return `${spaces}  ${key}: ${formattedValue}`;
      })
      .join('\n');

    return `{\n${formatted}\n${spaces}}`;
  }

  return val;
}
</script>

<style lang="scss" scoped>
:root {
  --main-font-size: 1.1rem;
  --main-line-height: 1.7;
  --main-color: #222;
  --label-color: #888;
  --header-font-size: 1.5rem;
  --header-font-weight: bold;
  --table-header-bg: #f7f7f7;
  --table-padding: 12px;
  --table-title-size: 2rem;
  --chip-font-size: 1.15rem;
  --chip-font-weight: bold;
}

.data-records-page {
  .page-header {
    margin-bottom: 24px;

    .page-title {
      font-size: 2rem;
      font-weight: bold;
      color: $primary;
      margin: 0 0 8px 0;
    }

    .page-subtitle {
      font-size: 1.1rem;
      color: $grey-6;
      margin: 0;
    }
  }

  .data-records-card {
    .card-header {
      border-bottom: 1px solid #eee;
      .card-title {
        font-size: var(--table-title-size);
        font-weight: bold;
        color: var(--main-color);
        margin: 0 0 4px 0;
      }
      .card-subtitle {
        color: #888;
        margin: 0;
        font-size: 1.1rem;
      }
    }
  }

  .data-table {
    table-layout: fixed;
    width: 100%;
    .q-th,
    .q-td {
      font-size: var(--main-font-size);
      line-height: var(--main-line-height);
      color: var(--main-color);
      padding: var(--table-padding);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .q-th {
      background: var(--table-header-bg);
      font-weight: bold;
      color: var(--main-color);
    }
    .ellipsis-cell,
    .content-cell,
    .hash-cell,
    .uploader-cell {
      font-size: var(--main-font-size);
      color: var(--main-color);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .large-text {
      font-size: 1.1rem;
    }
  }

  .dialog-header {
    border-bottom: 1px solid $grey-3;

    h4 {
      margin: 0;
      font-size: 1.2rem;
      font-weight: bold;
    }
  }

  .dialog-content {
    .content-display {
      background: $grey-1;
      padding: 16px;
      border-radius: 8px;
      font-family: monospace;
      font-size: 0.9rem;
      white-space: pre-wrap;
      word-break: break-all;
      max-height: 400px;
      overflow-y: auto;
    }

    .record-details {
      display: grid;
      gap: 16px;

      .detail-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;

        label {
          font-weight: bold;
          min-width: 100px;
          color: $grey-7;
        }

        &.full-width {
          flex-direction: column;
          align-items: stretch;

          label {
            margin-bottom: 8px;
          }
        }
      }
    }
  }
}

.large-tooltip {
  font-size: var(--main-font-size) !important;
  color: var(--main-color);
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  padding: 12px;
}

// 記錄詳情彈窗美化
.details-dialog-card,
.full-content-dialog {
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
  min-width: 320px;
  max-width: 600px;
  margin: 0 auto;
}

.q-dialog__inner--minimized > .q-dialog {
  max-width: 600px !important;
  min-width: 320px !important;
}

.full-content-dialog {
  max-width: 600px;
  min-width: 320px;
}

.details-dialog-card {
  max-width: 600px;
  min-width: 320px;
}

.details-dialog-header {
  border-bottom: 1px solid #eee;
  padding-bottom: 8px;
}

.details-dialog-title {
  font-size: var(--header-font-size);
  font-weight: var(--header-font-weight);
  color: var(--main-color);
  margin-bottom: 12px;
}

.details-dialog-content {
  padding: 24px 24px 8px 24px;
}

.details-dialog-details {
  font-size: var(--main-font-size);
  color: var(--main-color);
  line-height: var(--main-line-height);
}

.details-dialog-details label {
  color: var(--label-color);
  font-weight: normal;
  min-width: 100px;
}

.details-dialog-content-text {
  font-size: var(--main-font-size);
  background: #f7f7f7;
  border-radius: 8px;
  padding: 12px;
  margin-top: 8px;
  word-break: break-all;
}

.status-chip {
  font-size: var(--chip-font-size) !important;
  font-weight: var(--chip-font-weight) !important;
  padding: 0 18px !important;
  height: 2.2em !important;
  min-width: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.full-content-header {
  border-bottom: 1px solid #eee;
  padding-bottom: 8px;
}

.full-content-title {
  font-size: var(--header-font-size);
  font-weight: var(--header-font-weight);
  color: var(--main-color);
  margin-bottom: 12px;
}

.full-content-subtitle {
  font-size: 1.1rem;
  color: $grey-6;
  margin: 0;
}

.full-content-body {
  padding: 0;
}

.content-wrapper {
  padding: 24px;
}

.content-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.content-length {
  font-size: 1.1rem;
  color: $grey-6;
}

.full-content-text {
  background: $grey-1;
  padding: 16px;
  border-radius: 8px;
  font-family: monospace;
  font-size: 0.9rem;
  white-space: pre-wrap;
  word-break: break-all;
  max-height: 400px;
  overflow-y: auto;
  border: 1px solid $grey-3;
}

.content-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 16px;
}

.dialog-title-section {
  display: flex;
  flex-direction: column;
}

.content-cell-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
}

.content-preview {
  flex: 1;
  min-width: 0;
}

.view-full-btn {
  flex-shrink: 0;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 500;
  padding: 4px 8px;
  transition: all 0.2s ease;
  border: 1px solid transparent;

  &:hover {
    background: rgba(25, 118, 210, 0.1);
    border-color: rgba(25, 118, 210, 0.3);
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(25, 118, 210, 0.2);
  }

  &:active {
    transform: translateY(0);
    box-shadow: 0 1px 4px rgba(25, 118, 210, 0.2);
  }
}

.record-detail-label {
  font-size: 13px;
  font-weight: bold;
  margin-bottom: 8px;
  display: inline-block;
}

.record-detail-content {
  font-size: 12px;
  line-height: 1.5;
  margin: 0;
  max-height: 600px;
  overflow-y: auto;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 12px;
  background: #f9f9f9;

  /* 截圖時的樣式 */
  &.screenshot-mode {
    max-height: none !important;
    overflow: visible !important;
  }
}

.content-item {
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid #e0e0e0;
}

.content-item:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.content-key {
  font-weight: bold;
  color: #1976d2;
  margin-bottom: 4px;
  font-size: 13px;
}

.content-value {
  margin-left: 8px;
}

.content-value pre {
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 8px;
  margin: 4px 0;
  font-size: 11px;
  line-height: 1.4;
  white-space: pre-wrap;
  word-break: break-word;
  max-height: 300px;
  overflow-y: auto;
}

.content-value span {
  color: #333;
  font-size: 12px;
}
</style>
