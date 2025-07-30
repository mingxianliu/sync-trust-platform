/**
 * 前端 API 服務
 * 負責與後端 API 通信，不包含任何業務邏輯
 */

// API 配置
const API_CONFIG = {
  // 本地開發
  development: {
    ethereumApi: 'http://localhost/ethereum_api.php',
    dataRecordsApi: 'http://localhost/data_records_api.php',
  },
  // 生產環境
  production: {
    ethereumApi: 'https://syncadmin.winshare.tw/ethereum_api.php',
    dataRecordsApi: 'https://syncadmin.winshare.tw/data_records_api.php',
    dashboardApi: 'https://syncadmin.winshare.tw/dashboard_api.php',
  },
};

// 根據環境選擇配置
const isDev = process.env.NODE_ENV === 'development';
const config = isDev ? API_CONFIG.development : API_CONFIG.production;

/**
 * 通用 API 請求函數
 */
async function apiRequest(url, options = {}) {
  const defaultOptions = {
    headers: {
      'Content-Type': 'application/json',
    },
    timeout: 10000, // 10秒超時
  };

  const finalOptions = {
    ...defaultOptions,
    ...options,
    headers: {
      ...defaultOptions.headers,
      ...options.headers,
    },
  };

  try {
    const response = await fetch(url, finalOptions);

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const data = await response.json();

    // 如果你的 API 沒有 success 欄位，這段可以註解掉或改成更彈性的判斷
    // if (!data.success) {
    //   throw new Error(data.error || 'API 請求失敗');
    // }

    return data;
  } catch (error) {
    console.error('API 請求失敗:', error);
    throw error;
  }
}

/**
 * Ethereum API 服務
 */
export const ethereumApi = {
  /**
   * 獲取當前區塊號
   */
  async getBlockNumber() {
    const response = await apiRequest(config.ethereumApi, {
      method: 'POST',
      body: JSON.stringify({ action: 'getBlockNumber' }),
    });
    return response.data;
  },

  /**
   * 獲取 XML 數據
   */
  async getXml() {
    const response = await apiRequest(config.ethereumApi, {
      method: 'POST',
      body: JSON.stringify({ action: 'getXml' }),
    });
    return response.data;
  },

  /**
   * 設置 XML 數據
   */
  async setXml(xml) {
    const response = await apiRequest(config.ethereumApi, {
      method: 'POST',
      body: JSON.stringify({ action: 'setXml', xml }),
    });
    return response.data;
  },

  /**
   * 獲取所有數據記錄
   */
  async getAllRecords() {
    const response = await apiRequest(config.ethereumApi, {
      method: 'POST',
      body: JSON.stringify({ action: 'getAllRecords' }),
    });
    return response.data;
  },

  /**
   * 獲取單筆數據記錄
   */
  async getDataRecord(txHash) {
    const response = await apiRequest(config.ethereumApi, {
      method: 'POST',
      body: JSON.stringify({ action: 'getDataRecord', txHash }),
    });
    return response.data;
  },

  /**
   * 獲取交易收據
   */
  async getTransactionReceipt(txHash) {
    const response = await apiRequest(config.ethereumApi, {
      method: 'POST',
      body: JSON.stringify({ action: 'getTransactionReceipt', txHash }),
    });
    return response.data;
  },
};

/**
 * 數據記錄 API 服務
 */
export const dataRecordsApi = {
  /**
   * 獲取所有數據記錄
   */
  async getAllRecords() {
    const response = await apiRequest(
      `${config.dataRecordsApi}?action=getAllRecords`,
    );
    return {
      records: response.records || [],
      total: response.total || 0,
    };
  },

  /**
   * 獲取單筆數據記錄
   */
  async getRecord(txHash) {
    const response = await apiRequest(
      `${config.dataRecordsApi}?action=getRecord&txHash=${txHash}`,
    );
    return response.record;
  },
};

/**
 * 導出配置（用於調試）
 */
export const apiConfig = {
  ...config,
  isDevelopment: isDev,
};

/**
 * 測試 API 連接
 */
export async function testApiConnection() {
  const results = {
    ethereumApi: false,
    dataRecordsApi: false,
    errors: [],
  };

  try {
    // 測試 Ethereum API
    await ethereumApi.getBlockNumber();
    results.ethereumApi = true;
  } catch (error) {
    results.errors.push(`Ethereum API: ${error.message}`);
  }

  try {
    // 測試數據記錄 API
    await dataRecordsApi.getAllRecords();
    results.dataRecordsApi = true;
  } catch (error) {
    results.errors.push(`Data Records API: ${error.message}`);
  }

  return results;
}

/**
 * Dashboard API 服務
 */
export const dashboardApi = {
  /**
   * 取得 Dashboard 統計數據
   */
  async getStats() {
    return await apiRequest(`${config.dashboardApi}?action=dashboardStats`);
  },
  /**
   * 取得 Dashboard 趨勢圖資料
   */
  async getTrend() {
    return await apiRequest(`${config.dashboardApi}?action=dashboardTrend`);
  },
};
