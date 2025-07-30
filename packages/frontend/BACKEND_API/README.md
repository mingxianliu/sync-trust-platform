# 🔧 後端 API 服務

## 架構原則

- **單一職責**：後端只負責數據 API 服務
- **RESTful 設計**：提供標準的 HTTP API
- **JSON 響應**：所有響應都是 JSON 格式
- **錯誤處理**：統一的錯誤響應格式

## 檔案結構

```
BACKEND_API/
├── ethereum_api.php          # 主要的 Ethereum API 服務
├── data_records_api.php      # 數據記錄查詢 API
├── contract_info.json        # 合約配置資訊
├── ethereum_php_client.php   # Ethereum 客戶端庫
├── test_api.php             # API 測試腳本
└── README.md                # 本文檔
```

## API 端點

### 1. Ethereum API (`ethereum_api.php`)

**基礎功能**：

- `POST /ethereum_api.php` - 區塊鏈互動

**支援的操作**：

```json
{
  "action": "getXml" | "setXml" | "getAllRecords" | "getDataRecord",
  "params": {...}
}
```

**響應格式**：

```json
{
  "success": true,
  "data": {...},
  "error": null
}
```

### 2. 數據記錄 API (`data_records_api.php`)

**端點**：

- `GET /data_records_api.php?action=getAllRecords` - 獲取所有記錄
- `GET /data_records_api.php?action=getRecord&txHash=0x...` - 獲取單筆記錄

**響應格式**：

```json
{
  "success": true,
  "records": [...],
  "total": 5
}
```

## 部署說明

1. **檔案部署**：將所有 PHP 檔案上傳到伺服器
2. **權限設定**：確保 PHP 檔案有執行權限
3. **配置檢查**：確認 `contract_info.json` 配置正確
4. **測試驗證**：執行 `test_api.php` 驗證功能

## 前端呼叫範例

```javascript
// 獲取所有數據記錄
const response = await fetch('/ethereum_api.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ action: 'getAllRecords' }),
});

const data = await response.json();
if (data.success) {
  console.log('記錄:', data.data);
}
```

## 錯誤處理

所有 API 都遵循統一的錯誤響應格式：

```json
{
  "success": false,
  "error": "錯誤描述",
  "data": null
}
```

## 測試

執行測試腳本驗證 API 功能：

```bash
php test_api.php
```

---

**維護者**：後端開發團隊
**更新日期**：2025-01-07
