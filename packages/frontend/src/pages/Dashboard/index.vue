<template>
  <div class="dashboard-page">
    <!-- 資訊卡片區 -->
    <div class="dashboard-cards-group">
      <q-card class="dashboard-big-card enterprise-style">
        <q-card-section>
          <div class="big-card-title text-indigo-7">數據上鏈數</div>
          <div class="big-card-content row items-center no-wrap">
            <q-icon
              name="eva-bar-chart-outline"
              color="indigo-7"
              size="32px"
              class="q-mr-md"
            />
            <div class="info-value text-grey-8">{{ stats.totalRecords }}</div>
          </div>
        </q-card-section>
      </q-card>
      <q-card class="dashboard-big-card enterprise-style">
        <q-card-section>
          <div class="big-card-title text-indigo-7">數據檔上鏈數</div>
          <div class="big-card-content row items-center no-wrap">
            <q-icon
              name="eva-file-outline"
              color="indigo-7"
              size="32px"
              class="q-mr-md"
            />
            <div class="info-value text-grey-8">{{ stats.fileRecords }}</div>
          </div>
        </q-card-section>
      </q-card>
      <q-card class="dashboard-big-card enterprise-style">
        <q-card-section>
          <div class="big-card-title text-indigo-7">IPFS 狀態</div>
          <div class="big-card-content row items-center no-wrap">
            <q-icon name="cloud" color="indigo-7" size="32px" class="q-mr-md" />
            <div
              class="info-value"
              :class="{
                'text-positive': stats.ipfsStatus === '正常',
                'text-grey-6': stats.ipfsStatus !== '正常',
              }"
            >
              {{ stats.ipfsStatus }}
            </div>
          </div>
        </q-card-section>
      </q-card>
      <q-card class="dashboard-big-card enterprise-style">
        <q-card-section>
          <div class="big-card-title text-indigo-7">區塊鏈狀態</div>
          <div class="big-card-content row items-center no-wrap">
            <q-icon name="link" color="indigo-7" size="32px" class="q-mr-md" />
            <div
              class="info-value"
              :class="{
                'text-positive': stats.blockchainStatus === '正常',
                'text-grey-6': stats.blockchainStatus !== '正常',
              }"
            >
              {{ stats.blockchainStatus }}
            </div>
          </div>
        </q-card-section>
      </q-card>
      <q-card class="dashboard-big-card enterprise-style">
        <q-card-section>
          <div class="big-card-title text-indigo-7">區塊鏈區塊數</div>
          <div class="big-card-content row items-center no-wrap">
            <q-icon
              name="timeline"
              color="indigo-7"
              size="32px"
              class="q-mr-md"
            />
            <div class="info-value text-grey-8">
              {{ stats.chainBlockCount }}
            </div>
            <span class="q-ml-sm text-grey-6">區塊</span>
          </div>
        </q-card-section>
      </q-card>
      <q-card class="dashboard-big-card enterprise-style">
        <q-card-section>
          <div class="big-card-title text-indigo-7">IPFS 使用容量</div>
          <div class="big-card-content row items-center no-wrap">
            <q-icon
              name="cloud_done"
              color="indigo-7"
              size="32px"
              class="q-mr-md"
            />
            <div class="info-value text-grey-8">
              {{ stats.ipfsUsage }}
            </div>
            <span class="q-ml-sm text-grey-6">GB</span>
          </div>
        </q-card-section>
      </q-card>
      <q-card class="dashboard-big-card enterprise-style">
        <q-card-section>
          <div class="big-card-title text-indigo-7">成功/失敗比率</div>
          <div class="big-card-content row items-center no-wrap">
            <div class="big-info-item">
              <q-icon
                name="check_circle"
                color="indigo-5"
                size="32px"
                class="q-mr-md"
              />
              <div class="info-value text-grey-8">{{ stats.successCount }}</div>
              <span class="q-ml-sm text-grey-6">成功</span>
            </div>
            <div class="big-info-item">
              <q-icon
                name="cancel"
                color="grey-6"
                size="32px"
                class="q-mr-md"
              />
              <div class="info-value text-grey-6">{{ stats.failCount }}</div>
              <span class="q-ml-sm text-grey-6">失敗</span>
            </div>
          </div>
        </q-card-section>
      </q-card>
      <q-card class="dashboard-big-card enterprise-style">
        <q-card-section>
          <div class="big-card-title text-indigo-7">異常事件分佈</div>
          <div class="big-card-content row items-center no-wrap">
            <q-icon name="warning" color="grey-6" size="32px" class="q-mr-md" />
            <div class="info-value text-grey-6">{{ stats.abnormalCount }}</div>
            <span class="q-ml-sm text-grey-6">異常事件</span>
          </div>
        </q-card-section>
      </q-card>
      <q-card
        v-if="stats.abnormalCount > 0"
        class="dashboard-big-card enterprise-style dashboard-abnormal-notify-card"
      >
        <q-card-section>
          <div class="big-card-title text-negative">異常事件通知</div>
          <div class="big-card-content row items-center no-wrap">
            <q-icon
              name="warning"
              color="negative"
              size="32px"
              class="q-mr-md"
            />
            <div
              class="info-value text-negative"
              style="font-size: 1.1rem; font-weight: 500"
            >
              目前偵測到 {{ stats.abnormalCount }} 筆異常事件，請盡速處理！
            </div>
          </div>
        </q-card-section>
      </q-card>
    </div>

    <!-- 圖表區 -->
    <div class="dashboard-charts charts-group dashboard-chart-bg">
      <div class="chart-title text-indigo-7" style="margin-bottom: 8px">
        上鏈資料趨勢圖
      </div>
      <line-chart :chart-data="trendData" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import LineChart from 'src/components/LineChart.vue';
import { dashboardApi } from 'src/services/api';

const stats = ref({});
const trendData = ref({ labels: [], datasets: [] });

onMounted(async () => {
  stats.value = await dashboardApi.getStats();
  trendData.value = await dashboardApi.getTrend();
});
</script>

<style lang="scss" scoped>
.dashboard-page {
  padding: 32px 16px 0 16px;
  background: #f7f8fa;
}
.dashboard-cards-group {
  display: flex;
  flex-direction: row;
  gap: 32px;
  margin-bottom: 32px;
  flex-wrap: wrap;
}
.dashboard-big-card.enterprise-style {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(30, 60, 90, 0.06);
  .big-card-title {
    font-size: 1.15rem;
    font-weight: bold;
    color: #3949ab;
    margin-bottom: 10px;
  }
  .info-value {
    font-size: 2.2rem;
    font-weight: 600;
    color: #263238;
  }
  .q-icon {
    color: #3949ab !important;
  }
}
.big-card-content {
  display: flex;
  flex-direction: row;
  gap: 32px;
  justify-content: flex-start;
}
.big-info-item {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  min-width: 80px;
  margin-right: 8px;
}
.info-label {
  font-size: 0.95rem;
  color: #888;
  margin-bottom: 2px;
}
.chart-title {
  font-size: 1.15rem;
  font-weight: bold;
  color: #3949ab;
  margin-bottom: 10px;
}
.dashboard-chart-bg {
  padding: 16px 0 0 0;
}
.dashboard-alert-full {
  width: 100vw;
  margin-left: 50%;
  transform: translateX(-50%);
  margin-top: 0;
  margin-bottom: 0;
}
.dashboard-alert-full .q-banner {
  width: 100%;
  border-radius: 0;
  padding-left: 32px;
  font-size: 1.1rem;
  font-weight: bold;
  text-align: left;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
@media (max-width: 1200px) {
  .dashboard-big-card {
    min-width: 220px;
    max-width: 100%;
  }
  .dashboard-chart-card,
  .dashboard-chart-card.wide {
    min-width: 180px;
    max-width: 100%;
  }
}
@media (max-width: 700px) {
  .dashboard-cards-group,
  .charts-group {
    flex-direction: column;
    gap: 16px;
  }
  .dashboard-big-card {
    min-width: 0;
    max-width: 100%;
  }
  .dashboard-chart-card,
  .dashboard-chart-card.wide {
    min-width: 0;
    max-width: 100%;
    flex-basis: 100%;
  }
  .big-card-content {
    flex-direction: column;
    gap: 8px;
  }
}
.dashboard-abnormal-notify-card {
  border: 2px solid #e53935;
  .big-card-title {
    color: #e53935 !important;
  }
  .info-value {
    color: #e53935 !important;
  }
}
</style>
