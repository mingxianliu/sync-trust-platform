<template>
  <div class="data-records-test-page">
    <div class="page-header">
      <h2 class="page-title">🧪 數據資料查詢測試頁面</h2>
      <p class="page-subtitle">無需登入的測試版本</p>
    </div>

    <q-card class="test-card">
      <q-card-section>
        <div class="row items-center justify-between q-mb-md">
          <h3>API 測試</h3>
          <q-btn
            color="primary"
            icon="refresh"
            label="測試 API"
            :loading="loading"
            @click="testAPI"
          />
        </div>

        <div v-if="apiResult" class="api-result">
          <h4>API 測試結果：</h4>
          <pre>{{ JSON.stringify(apiResult, null, 2) }}</pre>
        </div>

        <div v-if="error" class="error-message">
          <h4>錯誤：</h4>
          <p>{{ error }}</p>
        </div>
      </q-card-section>
    </q-card>

    <q-card v-if="dataRecords.length > 0" class="data-card q-mt-md">
      <q-card-section>
        <h3>數據記錄列表 ({{ dataRecords.length }} 筆)</h3>

        <q-table
          :rows="dataRecords"
          :columns="columns"
          row-key="txHash"
          flat
          bordered
          class="data-table"
        >
          <!-- 內容欄位 -->
          <template #body-cell-content="props">
            <q-td :props="props">
              <div class="content-cell">
                <div class="content-preview">
                  {{ getContentPreview(props.value) }}
                </div>
                <q-btn
                  v-if="props.value.length > 100"
                  flat
                  dense
                  color="primary"
                  label="查看完整"
                  @click="showFullContent(props.value)"
                />
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
              <div class="hash-cell">
                <code class="hash-text">{{ props.value }}</code>
                <q-btn
                  flat
                  dense
                  color="primary"
                  icon="content_copy"
                  @click="copyToClipboard(props.value)"
                />
              </div>
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <!-- 完整內容對話框 -->
    <q-dialog v-model="showContentDialog" maximized>
      <q-card>
        <q-card-section class="dialog-header">
          <div class="row items-center justify-between">
            <h4>完整內容</h4>
            <q-btn flat round icon="close" @click="showContentDialog = false" />
          </div>
        </q-card-section>
        <q-card-section class="dialog-content">
          <pre class="content-display">{{ selectedContent }}</pre>
        </q-card-section>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useQuasar } from 'quasar';
import {
  ethereumApi,
  dataRecordsApi,
  testApiConnection,
} from 'src/services/api.js';

const $q = useQuasar();

// 響應式數據
const dataRecords = ref([]);
const loading = ref(false);
const apiResult = ref(null);
const error = ref(null);
const showContentDialog = ref(false);
const selectedContent = ref('');

// 表格欄位定義
const columns = [
  {
    name: 'description',
    label: '描述',
    field: 'description',
    align: 'left',
    sortable: true,
    style: 'max-width: 200px;',
  },
  {
    name: 'content',
    label: '內容',
    field: 'content',
    align: 'left',
    sortable: false,
    style: 'max-width: 300px;',
  },
  {
    name: 'timestamp',
    label: '上鏈時間',
    field: 'timestamp',
    align: 'left',
    sortable: true,
    style: 'width: 180px;',
  },
  {
    name: 'txHash',
    label: '交易哈希',
    field: 'txHash',
    align: 'left',
    sortable: false,
    style: 'max-width: 200px;',
  },
  {
    name: 'blockNumber',
    label: '區塊號',
    field: 'blockNumber',
    align: 'center',
    sortable: true,
    style: 'width: 100px;',
  },
  {
    name: 'uploader',
    label: '上傳者',
    field: 'uploader',
    align: 'left',
    sortable: false,
    style: 'max-width: 150px;',
  },
];

// 方法
const testAPI = async () => {
  loading.value = true;
  error.value = null;
  apiResult.value = null;

  try {
    console.log('🧪 開始測試 API 連接...');

    // 使用統一的 API 測試函數
    const results = await testApiConnection();

    apiResult.value = {
      success: results.ethereumApi || results.dataRecordsApi,
      results,
      timestamp: new Date().toISOString(),
    };

    if (results.ethereumApi || results.dataRecordsApi) {
      // 嘗試獲取數據記錄
      try {
        if (results.ethereumApi) {
          const records = await ethereumApi.getAllRecords();
          dataRecords.value = records;
          $q.notify({
            type: 'positive',
            message: `成功獲取 ${records.length} 筆記錄`,
          });
        } else if (results.dataRecordsApi) {
          const result = await dataRecordsApi.getAllRecords();
          dataRecords.value = result.records;
          $q.notify({
            type: 'positive',
            message: `成功獲取 ${result.total} 筆記錄`,
          });
        }
      } catch (dataError) {
        console.error('獲取數據失敗:', dataError);
        $q.notify({
          type: 'warning',
          message: 'API 連接成功，但獲取數據失敗',
        });
      }
    } else {
      error.value = '所有 API 端點都無法連接';
      $q.notify({
        type: 'negative',
        message: 'API 連接失敗',
      });
    }
  } catch (err) {
    console.error('API 測試失敗:', err);
    error.value = err.message;
    $q.notify({
      type: 'negative',
      message: 'API 測試失敗: ' + err.message,
    });
  } finally {
    loading.value = false;
  }
};

const getContentPreview = (content) => {
  if (content.length <= 100) {
    return content;
  }
  return content.substring(0, 100) + '...';
};

const formatTimestamp = (timestamp) => {
  const date = new Date(timestamp * 1000);
  return date.toLocaleString('zh-TW');
};

const copyToClipboard = async (text) => {
  try {
    await navigator.clipboard.writeText(text);
    $q.notify({
      type: 'positive',
      message: '已複製到剪貼簿',
    });
  } catch (error) {
    console.error('複製失敗:', error);
    $q.notify({
      type: 'negative',
      message: '複製失敗',
    });
  }
};

const showFullContent = (content) => {
  selectedContent.value = content;
  showContentDialog.value = true;
};

// 生命週期
onMounted(() => {
  // 自動測試 API
  testAPI();
});
</script>

<style lang="scss" scoped>
.data-records-test-page {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;

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

  .test-card,
  .data-card {
    .api-result {
      background: $grey-1;
      padding: 16px;
      border-radius: 8px;
      margin-top: 16px;

      pre {
        font-family: monospace;
        font-size: 0.9rem;
        white-space: pre-wrap;
        word-break: break-all;
        margin: 0;
      }
    }

    .error-message {
      background: $red-1;
      color: $red-8;
      padding: 16px;
      border-radius: 8px;
      margin-top: 16px;
    }
  }

  .data-table {
    .content-cell {
      .content-preview {
        font-family: monospace;
        font-size: 0.9rem;
        word-break: break-all;
      }
    }

    .hash-cell {
      display: flex;
      align-items: center;
      gap: 8px;

      .hash-text {
        font-family: monospace;
        font-size: 0.8rem;
        word-break: break-all;
      }
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
  }
}
</style>
