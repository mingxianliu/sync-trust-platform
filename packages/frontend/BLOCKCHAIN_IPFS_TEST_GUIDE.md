# 🔗 區塊鏈和 IPFS 整合測試指南

## 📋 目錄

1. [系統概述](#系統概述)
2. [環境準備](#環境準備)
3. [基礎功能測試](#基礎功能測試)
4. [文件上傳測試](#文件上傳測試)
5. [Web 界面測試](#web-界面測試)
6. [故障排除](#故障排除)
7. [API 參考](#api-參考)

---

## 🏗️ 系統概述

### 架構組件

- **Ethereum 私有鏈**: Geth 1.10.25 (端口 8545)
- **IPFS 節點**: Kubo 0.22.0 (端口 5001)
- **智能合約**: SimpleStorage.sol (地址: `0x0b069012e44D4eA8Cc122045d649A202891E09FA`)
- **Web 服務器**: Nginx + PHP-FPM
- **PHP 客戶端**: 自定義 Ethereum 和 IPFS 客戶端

### 數據流程

```
文件上傳 → IPFS 存儲 → 獲取 Hash → 記錄到 Ethereum → 驗證完整性
```

---

## 🔧 環境準備

### 1. 檢查服務狀態

```bash
# 檢查 Ethereum 節點
ssh root@syncadmin.winshare.tw "ps aux | grep geth"

# 檢查 IPFS 節點
ssh root@syncadmin.winshare.tw "ps aux | grep ipfs"

# 檢查 Web 服務
ssh root@syncadmin.winshare.tw "systemctl status nginx php-fpm"
```

### 2. 驗證端口連接

```bash
# 檢查 Ethereum RPC
ssh root@syncadmin.winshare.tw "curl -X POST -H 'Content-Type: application/json' --data '{\"jsonrpc\":\"2.0\",\"method\":\"eth_blockNumber\",\"params\":[],\"id\":1}' http://localhost:8545"

# 檢查 IPFS API
ssh root@syncadmin.winshare.tw "curl -X POST http://localhost:5001/api/v0/id"
```

### 3. 確認文件位置

```bash
# 檢查測試文件
ssh root@syncadmin.winshare.tw "ls -la /www/nspo_ipfs_backend/public/"
```

---

## 🧪 基礎功能測試

### 1. Ethereum 客戶端測試

**文件**: `test_ethereum.php`

```bash
# 執行測試
ssh root@syncadmin.winshare.tw "cd /www/nspo_ipfs_backend/public && php test_ethereum.php"
```

**預期結果**:

```
✅ Ethereum 連接成功
✅ 合約地址: 0x0b069012e44D4eA8Cc122045d649A202891E09FA
✅ 賬戶餘額: 1686 ETH
✅ 區塊高度: 843
✅ getXml 測試成功
✅ setXml 測試成功
```

### 2. IPFS 連接測試

**文件**: `ipfs_file_test.php` (部分功能)

```bash
# 測試 IPFS 連接
ssh root@syncadmin.winshare.tw "cd /www/nspo_ipfs_backend/public && php -r '
require_once \"ipfs_file_test.php\";
\$test = new IPFSFileTest();
\$test->testIPFSConnection();
'"
```

**預期結果**:

```
=== 測試 IPFS 連接 ===
✅ IPFS 節點 ID: 12D3KooWEai56aPttAPi7b7XnbK9sfWTdNfBkzvWA59tFk9i5BzD
✅ IPFS 版本: kubo/0.22.0/
```

---

## 📁 文件上傳測試

### 1. 完整命令行測試

**文件**: `ipfs_file_test.php`

```bash
# 執行完整測試
ssh root@syncadmin.winshare.tw "cd /www/nspo_ipfs_backend/public && php ipfs_file_test.php"
```

**測試步驟**:

1. 創建測試文件
2. 上傳到 IPFS
3. 記錄到 Ethereum
4. 下載並驗證
5. 清理臨時文件

**預期結果**:

```
🚀 開始完整的文件上傳和 IPFS 整合測試

=== 測試 IPFS 連接 ===
✅ IPFS 節點 ID: 12D3KooWEai56aPttAPi7b7XnbK9sfWTdNfBkzvWA59tFk9i5BzD
✅ IPFS 版本: kubo/0.22.0/
✅ 創建測試文件: test_file_1751729228.txt
   文件大小: 104 字節

=== 上傳文件到 IPFS ===
✅ 文件上傳成功！
   IPFS Hash: Qmcg9B7zioiUzYq2R4nu6fgiSpSoqcu649nmFNXdvYuBjr
   文件大小: 112 字節
   文件名: test_file_1751729228.txt

=== 記錄文件信息到 Ethereum ===
   文件信息: {"filename":"test_file_1751729228.txt","ipfs_hash":"Qmcg9B7zioiUzYq2R4nu6fgiSpSoqcu649nmFNXdvYuBjr","file_size":104,"upload_time":"2025-07-05 15:27:08","uploader":"0x5D640Ba7Ed0755f44331544598e8F6EA3662baD0"}
✅ 交易提交成功！
   交易哈希: 0xbaf8be6b4dd80a44bdcf8b36782610b39144aacba079d94bc6fb31834f2428ca
   記錄驗證: {"filename":"test_file_1751729228.txt","ipfs_hash":"Qmcg9B7zioiUzYq2R4nu6fgiSpSoqcu649nmFNXdvYuBjr","file_size":104,"upload_time":"2025-07-05 15:27:08","uploader":"0x5D640Ba7Ed0755f44331544598e8F6EA3662baD0"}

=== 從 IPFS 下載文件 ===
✅ 文件下載成功！
   保存路徑: downloaded_1751729231.txt
   文件大小: 104 字節
   文件內容:
   這是一個測試文件
   創建時間: 2025-07-05 15:27:08
   內容: 用於測試 IPFS 文件上傳功能

🎉 完整測試成功完成！
✅ IPFS 連接: 正常
✅ 文件上傳: 成功
✅ Ethereum 記錄: 成功
✅ 文件下載: 成功
✅ 數據完整性: 驗證通過
```

### 2. Web 功能核心測試

**文件**: `simple_web_test.php`

```bash
# 執行 Web 功能測試
ssh root@syncadmin.winshare.tw "cd /www/nspo_ipfs_backend/public && php simple_web_test.php"
```

**預期結果**:

```
=== 簡單 Web 上傳功能測試 ===
✅ 創建測試文件: web_simple_test_1751729351.txt
   文件大小: 116 字節

1. 測試 IPFS 上傳...
   ✅ IPFS 上傳成功！
   Hash: Qmbm6iZxcV98gid19pLSsvim73e3w1JBHWDFnoi9HxC2TU
   大小: 124 字節

2. 測試 Ethereum 記錄...
   記錄信息: {"filename":"web_simple_test_1751729351.txt","ipfs_hash":"Qmbm6iZxcV98gid19pLSsvim73e3w1JBHWDFnoi9HxC2TU","file_size":116,"upload_time":"2025-07-05 15:29:11","uploader":"0x5D640Ba7Ed0755f44331544598e8F6EA3662baD0","test_type":"web_upload"}
   ✅ Ethereum 記錄成功！
   交易哈希: 0xfbefc71c584a8b39f257f3e6952ddf446f7c4d4b142f8782d4d94b6574c5bd91
   記錄驗證: {"filename":"web_simple_test_1751729351.txt","ipfs_hash":"Qmbm6iZxcV98gid19pLSsvim73e3w1JBHWDFnoi9HxC2TU","file_size":116,"upload_time":"2025-07-05 15:29:11","uploader":"0x5D640Ba7Ed0755f44331544598e8F6EA3662baD0","test_type":"web_upload"}

🎉 Web 上傳功能測試成功完成！
✅ IPFS 上傳: 正常
✅ Ethereum 記錄: 正常
✅ 數據完整性: 驗證通過
```

---

## 🌐 Web 界面測試

### 1. 訪問 Web 界面

**URL**: https://syncadmin.winshare.tw/upload_test.php

### 2. 界面功能驗證

**檢查項目**:

- ✅ Ethereum 狀態顯示 (綠色卡片)
- ✅ IPFS 狀態顯示 (藍色卡片)
- ✅ 文件上傳表單
- ✅ 當前記錄顯示

### 3. 實際文件上傳測試

**步驟**:

1. 點擊 "選擇文件" 按鈕
2. 選擇任意文本文件
3. 點擊 "🚀 上傳到 IPFS 並記錄到 Ethereum" 按鈕
4. 等待上傳完成
5. 檢查結果顯示

**預期結果**:

```
✅ 上傳成功！
文件名: test.txt
文件大小: 1,234 字節
IPFS Hash: Qm...
Ethereum 交易: 0x...
IPFS 訪問鏈接: [查看文件]
```

---

## 🔍 故障排除

### 1. Ethereum 連接問題

**症狀**: "Ethereum 連接失敗"

**解決方案**:

```bash
# 檢查 Geth 進程
ssh root@syncadmin.winshare.tw "ps aux | grep geth"

# 檢查端口
ssh root@syncadmin.winshare.tw "netstat -tlnp | grep 8545"

# 重啟 Geth
ssh root@syncadmin.winshare.tw "pkill geth && nohup geth --datadir /root/ethereum --networkid 12345 --http --http.addr 0.0.0.0 --http.port 8545 --http.corsdomain '*' --http.api eth,net,web3,personal --allow-insecure-unlock --unlock 0x5D640Ba7Ed0755f44331544598e8F6EA3662baD0 --password /root/password.txt --mine --miner.etherbase 0x5D640Ba7Ed0755f44331544598e8F6EA3662baD0 > geth.log 2>&1 &"
```

### 2. IPFS 連接問題

**症狀**: "IPFS 連接失敗"

**解決方案**:

```bash
# 檢查 IPFS 進程
ssh root@syncadmin.winshare.tw "ps aux | grep ipfs"

# 檢查端口
ssh root@syncadmin.winshare.tw "netstat -tlnp | grep 5001"

# 重啟 IPFS
ssh root@syncadmin.winshare.tw "pkill ipfs && nohup ipfs daemon > ipfs.log 2>&1 &"
```

### 3. Web 服務問題

**症狀**: 404 錯誤或 PHP 錯誤

**解決方案**:

```bash
# 檢查 Nginx 狀態
ssh root@syncadmin.winshare.tw "systemctl status nginx"

# 檢查 PHP-FPM 狀態
ssh root@syncadmin.winshare.tw "systemctl status php-fpm"

# 重啟服務
ssh root@syncadmin.winshare.tw "systemctl restart nginx php-fpm"
```

### 4. 權限問題

**症狀**: "權限被拒絕"

**解決方案**:

```bash
# 修復文件權限
ssh root@syncadmin.winshare.tw "chown -R nginx:nginx /www/nspo_ipfs_backend/public/"

# 修復 SELinux 上下文
ssh root@syncadmin.winshare.tw "chcon -R -t httpd_exec_t /www/nspo_ipfs_backend/public/"
```

---

## 📚 API 參考

### Ethereum 客戶端 API

**文件**: `ethereum_php_client.php`

```php
// 初始化客戶端
$client = new EthereumClient();

// 獲取賬戶地址
$address = $client->getAccountAddress();

// 獲取餘額
$balance = $client->getBalance();

// 獲取合約地址
$contractAddress = $client->getContractAddress();

// 讀取數據
$data = $client->getXml();

// 寫入數據
$txHash = $client->setXml($data);
```

### IPFS API

**端點**: `http://localhost:5001/api/v0`

```php
// 上傳文件
$url = 'http://localhost:5001/api/v0/add';
$postData = ['file' => new CURLFile($filepath)];

// 下載文件
$url = 'http://localhost:5001/api/v0/cat?arg=' . $hash;

// 獲取節點信息
$url = 'http://localhost:5001/api/v0/id';
```

### 文件信息格式

```json
{
  "filename": "test.txt",
  "ipfs_hash": "Qm...",
  "file_size": 1234,
  "upload_time": "2025-07-05 15:30:00",
  "uploader": "0x5D640Ba7Ed0755f44331544598e8F6EA3662baD0",
  "mime_type": "text/plain"
}
```

---

## 📋 測試檢查清單

### 基礎環境

- [ ] Ethereum 節點運行正常
- [ ] IPFS 節點運行正常
- [ ] Web 服務器運行正常
- [ ] 所有端口可訪問

### 功能測試

- [ ] Ethereum 客戶端連接測試通過
- [ ] IPFS 連接測試通過
- [ ] 文件上傳到 IPFS 成功
- [ ] 數據記錄到 Ethereum 成功
- [ ] 文件下載和驗證成功
- [ ] Web 界面正常顯示
- [ ] Web 上傳功能正常

### 性能測試

- [ ] 文件上傳速度 < 3 秒
- [ ] IPFS 響應時間 < 1 秒
- [ ] Ethereum 交易確認 < 10 秒
- [ ] Web 界面響應時間 < 2 秒

---

## 🎯 下一步開發建議

1. **API 開發**: 創建 RESTful API 供前端調用
2. **前端整合**: 將功能整合到 Vue.js 應用
3. **批量處理**: 實現多文件批量上傳
4. **權限控制**: 添加用戶認證和權限管理
5. **監控儀表板**: 創建系統監控界面
6. **錯誤處理**: 完善錯誤處理和日誌記錄

---

**文檔版本**: 1.0
**最後更新**: 2025-07-05
**維護者**: 開發團隊
