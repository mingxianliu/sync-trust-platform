# 🏗️ 系統架構設計

## 架構原則

- **前後端分離**：前端負責介面，後端負責數據
- **單一職責**：每個組件只負責自己的功能
- **介面標準化**：統一的 API 響應格式
- **錯誤處理**：完善的錯誤處理機制

## 系統架構圖

```
┌─────────────────┐    HTTP/JSON    ┌─────────────────┐
│   前端 (Vue.js)  │ ◄─────────────► │   後端 (PHP)    │
│                 │                 │                 │
│ • 使用者介面    │                 │ • API 服務      │
│ • 數據展示      │                 │ • 業務邏輯      │
│ • 使用者互動    │                 │ • 數據驗證      │
│ • 狀態管理      │                 │ • 錯誤處理      │
└─────────────────┘                 └─────────────────┘
                                              │
                                              ▼
                                    ┌─────────────────┐
                                    │  Ethereum 節點  │
                                    │                 │
                                    │ • 區塊鏈互動    │
                                    │ • 智慧合約呼叫  │
                                    │ • 交易處理      │
                                    └─────────────────┘
```

## 前端架構 (Vue.js + Quasar)

### 檔案結構

```
src/
├── pages/
│   ├── DataRecords/
│   │   └── index.vue          # 主要數據記錄頁面
│   └── DataRecordsTest.vue    # 測試頁面
├── services/
│   └── api.js                 # API 服務層
└── router/
    └── routes.js              # 路由配置
```

### 職責分工

#### 1. 頁面組件 (`pages/`)

- **職責**：使用者介面和互動
- **功能**：
  - 數據展示
  - 使用者操作
  - 狀態管理
  - 錯誤提示

#### 2. API 服務 (`services/api.js`)

- **職責**：與後端 API 通信
- **功能**：
  - HTTP 請求封裝
  - 錯誤處理
  - 數據轉換
  - 環境配置

#### 3. 路由配置 (`router/`)

- **職責**：頁面路由管理
- **功能**：
  - 路由定義
  - 權限控制
  - 頁面導航

## 後端架構 (PHP)

### 檔案結構

```
BACKEND_API/
├── ethereum_api.php          # 主要的 Ethereum API
├── data_records_api.php      # 數據記錄查詢 API
├── ethereum_php_client.php   # Ethereum 客戶端庫
├── contract_info.json        # 合約配置
└── test_api.php             # API 測試腳本
```

### 職責分工

#### 1. API 端點 (`ethereum_api.php`, `data_records_api.php`)

- **職責**：提供 HTTP API 服務
- **功能**：
  - 接收 HTTP 請求
  - 參數驗證
  - 呼叫業務邏輯
  - 返回 JSON 響應

#### 2. 客戶端庫 (`ethereum_php_client.php`)

- **職責**：區塊鏈互動
- **功能**：
  - JSON-RPC 通信
  - 智慧合約呼叫
  - 交易處理
  - 數據解析

#### 3. 配置管理 (`contract_info.json`)

- **職責**：系統配置
- **功能**：
  - 合約地址
  - ABI 定義
  - 網路配置

## API 設計規範

### 請求格式

#### POST 請求 (Ethereum API)

```json
{
  "action": "getAllRecords",
  "params": {
    "optional": "parameters"
  }
}
```

#### GET 請求 (Data Records API)

```
GET /data_records_api.php?action=getAllRecords
GET /data_records_api.php?action=getRecord&txHash=0x...
```

### 響應格式

#### 成功響應

```json
{
  "success": true,
  "data": {...},
  "error": null
}
```

#### 錯誤響應

```json
{
  "success": false,
  "error": "錯誤描述",
  "data": null
}
```

## 數據流

### 1. 獲取數據記錄流程

```
使用者點擊重新整理 → 前端呼叫 API → 後端查詢區塊鏈 → 返回數據 → 前端展示
```

### 2. 錯誤處理流程

```
API 呼叫失敗 → 捕獲錯誤 → 顯示錯誤資訊 → 使用者重試
```

## 部署架構

### 開發環境

```
前端: https://localhost:8080
後端: http://localhost/ethereum_api.php
```

### 生產環境

```
前端: https://synckeytech.winshare.tw
後端: https://synckeytech.winshare.tw/ethereum_api.php
```

## 安全考量

### 前端安全

- 輸入驗證
- XSS 防護
- CSRF 防護

### 後端安全

- 參數驗證
- SQL 注入防護
- 權限控制

## 效能優化

### 前端優化

- 組件懶載入
- 數據快取
- 請求防抖

### 後端優化

- 資料庫連線池
- 快取機制
- 非同步處理

## 測試策略

### 前端測試

- 單元測試
- 整合測試
- E2E 測試

### 後端測試

- API 測試
- 功能測試
- 效能測試

---

**維護者**：開發團隊
**更新日期**：2025-01-07
**版本**：1.0.0
