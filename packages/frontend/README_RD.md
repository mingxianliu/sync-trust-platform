# 🚀 區塊鏈和 IPFS 整合系統 - RD 快速開始指南

## 📋 系統概述

這是一個完整的區塊鏈和 IPFS 整合系統，支持：

- 文件上傳到 IPFS 分散式存儲
- 文件信息記錄到 Ethereum 區塊鏈
- Web 界面和命令行兩種操作方式
- 完整的數據完整性驗證

## ⚡ 快速測試

### 1. 一鍵測試所有功能

```bash
# 連接到伺服器並執行快速測試
ssh root@syncadmin.winshare.tw "cd /www/nspo_ipfs_backend/public && php quick_test.php"
```

**預期結果**:

```
🚀 區塊鏈和 IPFS 快速測試
========================

1️⃣ 測試 Ethereum 連接...
   ✅ 連接成功
   💰 餘額: 1846 ETH
   📦 區塊: 923

2️⃣ 測試 IPFS 連接...
   ✅ 連接成功
   🆔 節點 ID: 12D3KooWEai56aPttAPi...

3️⃣ 測試 IPFS 文件上傳...
   ✅ 上傳成功
   🔗 Hash: QmcooQ75xAx6chfdXtYdPeTCDc7PkxDjcsKHWpxrutWoAT

4️⃣ 測試 Ethereum 數據記錄...
   ✅ 記錄成功
   🔗 交易: 0x0ca52eaa359dae6450...

5️⃣ 測試 IPFS 文件下載...
   ✅ 下載成功

📈 結果: 5/5 測試通過
🎉 所有測試通過！系統運行正常。
```

### 2. Web 界面測試

**訪問地址**: https://syncadmin.winshare.tw/upload_test.php

**功能**:

- 實時系統狀態監控
- 文件上傳表單
- 上傳結果展示
- Ethereum 記錄查詢

## 📁 可用文件

### 伺服器位置: `/www/nspo_ipfs_backend/public/`

| 文件名                    | 功能              | 使用方式                  |
| ------------------------- | ----------------- | ------------------------- |
| `quick_test.php`          | 快速測試所有功能  | `php quick_test.php`      |
| `ipfs_file_test.php`      | 完整文件上傳測試  | `php ipfs_file_test.php`  |
| `upload_test.php`         | Web 上傳界面      | 瀏覽器訪問                |
| `ethereum_php_client.php` | Ethereum 客戶端   | 被其他文件引用            |
| `test_ethereum.php`       | Ethereum 功能測試 | `php test_ethereum.php`   |
| `simple_web_test.php`     | Web 功能核心測試  | `php simple_web_test.php` |

## 🔧 開發環境

### 系統組件

- **Ethereum 私有鏈**: Geth 1.10.25 (端口 8545)
- **IPFS 節點**: Kubo 0.22.0 (端口 5001)
- **智能合約**: `0x0b069012e44D4eA8Cc122045d649A202891E09FA`
- **Web 服務器**: Nginx + PHP-FPM

### 連接信息

```bash
# Ethereum RPC
http://syncadmin.winshare.tw:8545

# IPFS API
http://syncadmin.winshare.tw:5001/api/v0

# Web 界面
https://syncadmin.winshare.tw/upload_test.php
```

## 🧪 詳細測試步驟

### 1. 基礎功能測試

```bash
# 測試 Ethereum 客戶端
ssh root@syncadmin.winshare.tw "cd /www/nspo_ipfs_backend/public && php test_ethereum.php"

# 測試 IPFS 連接
ssh root@syncadmin.winshare.tw "cd /www/nspo_ipfs_backend/public && php -r 'require_once \"ipfs_file_test.php\"; \$test = new IPFSFileTest(); \$test->testIPFSConnection();'"
```

### 2. 完整文件上傳測試

```bash
# 執行完整測試流程
ssh root@syncadmin.winshare.tw "cd /www/nspo_ipfs_backend/public && php ipfs_file_test.php"
```

### 3. Web 功能測試

```bash
# 測試 Web 功能核心邏輯
ssh root@syncadmin.winshare.tw "cd /www/nspo_ipfs_backend/public && php simple_web_test.php"
```

## 📚 API 使用

### Ethereum 客戶端

```php
require_once 'ethereum_php_client.php';

$client = new EthereumClient();

// 獲取餘額
$balance = $client->getBalance();

// 讀取數據
$data = $client->getXml();

// 寫入數據
$txHash = $client->setXml($jsonData);
```

### IPFS API

```php
// 上傳文件
$url = 'http://localhost:5001/api/v0/add';
$postData = ['file' => new CURLFile($filepath)];

// 下載文件
$url = 'http://localhost:5001/api/v0/cat?arg=' . $hash;
```

## 🔍 故障排除

### 常見問題

1. **Ethereum 連接失敗**

   ```bash
   ssh root@syncadmin.winshare.tw "ps aux | grep geth"
   ```

2. **IPFS 連接失敗**

   ```bash
   ssh root@syncadmin.winshare.tw "ps aux | grep ipfs"
   ```

3. **Web 服務問題**
   ```bash
   ssh root@syncadmin.winshare.tw "systemctl status nginx php-fpm"
   ```

### 重啟服務

```bash
# 重啟 Ethereum
ssh root@syncadmin.winshare.tw "pkill geth && nohup geth --datadir /root/ethereum --networkid 12345 --http --http.addr 0.0.0.0 --http.port 8545 --http.corsdomain '*' --http.api eth,net,web3,personal --allow-insecure-unlock --unlock 0x5D640Ba7Ed0755f44331544598e8F6EA3662baD0 --password /root/password.txt --mine --miner.etherbase 0x5D640Ba7Ed0755f44331544598e8F6EA3662baD0 > geth.log 2>&1 &"

# 重啟 IPFS
ssh root@syncadmin.winshare.tw "pkill ipfs && nohup ipfs daemon > ipfs.log 2>&1 &"

# 重啟 Web 服務
ssh root@syncadmin.winshare.tw "systemctl restart nginx php-fpm"
```

## 📖 詳細文檔

完整測試指南請參考: `BLOCKCHAIN_IPFS_TEST_GUIDE.md`

## 🎯 下一步開發

1. **API 開發**: 創建 RESTful API
2. **前端整合**: 整合到 Vue.js 應用
3. **批量上傳**: 實現多文件上傳
4. **權限控制**: 添加用戶認證
5. **監控儀表板**: 創建管理界面

---

**快速開始**: 運行 `php quick_test.php` 即可驗證所有功能！
**Web 測試**: 訪問 https://syncadmin.winshare.tw/upload_test.php
**文檔**: 查看 `BLOCKCHAIN_IPFS_TEST_GUIDE.md` 獲取詳細信息
