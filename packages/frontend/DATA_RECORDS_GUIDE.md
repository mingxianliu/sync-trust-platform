# 📊 數據資料查詢功能使用指南

## 概述

本功能允許用戶查詢所有上鏈的數據記錄，包括手動上鏈的數據和檔案上鏈記錄。通過查詢區塊鏈交易歷史，可以獲取完整的數據上鏈記錄。

## 功能特點

- ✅ **多筆記錄查詢** - 支援查詢所有歷史記錄
- ✅ **詳細資訊顯示** - 包含交易哈希、區塊號、時間戳等
- ✅ **內容預覽** - 長內容自動截斷，支援完整查看
- ✅ **複製功能** - 一鍵複製交易哈希
- ✅ **詳情查看** - 點擊查看完整記錄詳情
- ✅ **即時更新** - 支援手動重新整理

## 技術架構

### 後端 API (PHP)

- **檔案**: `data_records_api.php`
- **功能**: 查詢區塊鏈交易歷史，解析交易數據
- **支援**: 獲取所有記錄、單筆記錄查詢

### 前端頁面 (Vue.js + Quasar)

- **檔案**: `src/pages/DataRecords/index.vue`
- **路由**: `/management/dataRecords`
- **權限**: 需要 `isOrgAdmin` 權限

## 安裝與配置

### 1. 後端配置

確保以下檔案存在且配置正確：

```bash
# 合約資訊檔案
contract_info.json

# PHP 客戶端
ethereum_php_client.php

# 數據記錄 API
data_records_api.php
```

### 2. 前端配置

路由已自動配置，頁面位於：

- 路徑: `/management/dataRecords`
- 菜單: 系統管理 → 數據資料查詢

### 3. 權限設定

確保用戶具有 `isOrgAdmin` 權限才能訪問此功能。

## API 使用說明

### 獲取所有記錄

```http
GET /data_records_api.php?action=getAllRecords
```

**回應格式**:

```json
{
  "success": true,
  "records": [
    {
      "txHash": "0x...",
      "blockNumber": 1234,
      "timestamp": 1640995200,
      "content": "JSON 或字串內容",
      "uploader": "0x...",
      "description": "描述",
      "status": "success"
    }
  ],
  "total": 5
}
```

### 獲取單筆記錄

```http
GET /data_records_api.php?action=getRecord&txHash=0x...
```

**回應格式**:

```json
{
  "success": true,
  "record": {
    "txHash": "0x...",
    "blockNumber": 1234,
    "timestamp": 1640995200,
    "content": "完整內容",
    "uploader": "0x...",
    "description": "描述",
    "status": "success"
  }
}
```

## 前端使用說明

### 1. 訪問頁面

1. 登入系統
2. 進入「系統管理」菜單
3. 點擊「數據資料查詢」

### 2. 查看記錄

- **表格顯示**: 所有記錄以表格形式顯示
- **排序功能**: 支援按時間、區塊號排序
- **分頁功能**: 支援分頁瀏覽
- **搜尋功能**: 可搜尋特定記錄

### 3. 記錄詳情

點擊「查看詳情」按鈕可查看完整記錄資訊：

- 交易哈希
- 區塊號
- 上鏈時間
- 上傳者地址
- 描述
- 完整內容

### 4. 操作功能

- **重新整理**: 點擊「重新整理」按鈕更新數據
- **複製哈希**: 點擊複製按鈕複製交易哈希
- **查看完整內容**: 點擊「查看完整」查看長內容

## 數據格式說明

### 記錄結構

```json
{
  "txHash": "交易哈希",
  "blockNumber": "區塊號",
  "timestamp": "時間戳",
  "content": "上鏈內容",
  "uploader": "上傳者地址",
  "description": "描述",
  "status": "狀態"
}
```

### 內容類型

1. **JSON 格式** - 智慧建築數據
2. **字串格式** - 一般文字內容
3. **檔案資訊** - IPFS 檔案記錄

### 描述提取規則

- 優先使用 JSON 中的 `description` 欄位
- 其次使用 `filename` 欄位
- 最後截斷顯示內容前 50 字元

## 測試與驗證

### 1. API 測試

訪問測試頁面：

```
http://your-domain/test_data_records_api.php
```

### 2. 功能測試

1. 手動上鏈一些測試數據
2. 訪問數據查詢頁面
3. 驗證記錄是否正確顯示
4. 測試各種操作功能

### 3. 常見問題

**Q: 沒有顯示任何記錄？**
A: 檢查是否有上鏈交易，確認合約地址正確

**Q: API 返回錯誤？**
A: 檢查 Ethereum 節點連接，確認 RPC 端點可用

**Q: 前端無法載入？**
A: 檢查用戶權限，確認路由配置正確

## 開發說明

### 擴充功能

如需新增功能，可修改以下檔案：

1. **後端 API**: `data_records_api.php`
2. **前端頁面**: `src/pages/DataRecords/index.vue`
3. **路由配置**: `src/router/routes.js`

### 自訂欄位

可在 `columns` 陣列中新增或修改欄位：

```javascript
{
  name: 'customField',
  label: '自訂欄位',
  field: 'customField',
  align: 'left',
  sortable: true
}
```

### 樣式自訂

修改 `src/pages/DataRecords/index.vue` 中的 `<style>` 區塊來自訂樣式。

## 部署注意事項

1. **檔案權限**: 確保 PHP 檔案有執行權限
2. **網路連接**: 確認可訪問 Ethereum 節點
3. **記憶體限制**: 大量記錄查詢可能需要調整 PHP 記憶體限制
4. **快取策略**: 考慮實作快取機制提升效能

## 聯絡支援

如有問題或建議，請聯絡開發團隊。

---

**版本**: 1.0.0
**更新日期**: 2025-01-07
**維護者**: SyncTrust 開發團隊
