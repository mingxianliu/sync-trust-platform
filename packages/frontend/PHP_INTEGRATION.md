# PHP 後端與以太坊智能合約整合指南

## 概述

你的 PHP 後端可以通過 HTTP JSON-RPC 與部署的以太坊私鏈智能合約進行互動。

## 連接信息

- **RPC 端點**: `http://108.160.133.172:8545`
- **網路 ID**: 1337
- **合約地址**: `0xcd1dCACcDd7839BB572a4AE302f5BE00d441D4cf`
- **帳戶地址**: `0x6cEB3D2a22D4ef4ab450C192B28085cf6C92fD0F`

## 合約功能

Coursetro 合約提供以下功能：

- `getXml()` - 獲取存儲的 XML 數據
- `setXml(string _xml)` - 設置 XML 數據
- `signXml(string _xml)` - 簽名 XML 數據

## 使用方法

### 1. 基本整合

將 `ethereum_api.php` 包含到你的 PHP 項目中：

```php
require_once 'ethereum_api.php';

$api = new EthereumAPI();

// 獲取區塊鏈狀態
$blockNumber = $api->getBlockNumber();
$balance = $api->getBalance();

// 獲取合約中的 XML
$xml = $api->getXml();

// 設置新的 XML
$txHash = $api->setXml('<data>Hello World</data>');
$receipt = $api->waitForTransaction($txHash);
```

### 2. REST API 方式

使用 `ethereum_api.php` 作為 REST API：

```bash
# 獲取狀態
GET /ethereum_api.php?action=status

# 獲取 XML
GET /ethereum_api.php?action=getXml

# 設置 XML
POST /ethereum_api.php?action=setXml
Content-Type: application/x-www-form-urlencoded
xml=<data>Hello World</data>

# 簽名 XML
POST /ethereum_api.php?action=signXml
Content-Type: application/x-www-form-urlencoded
xml=<data>Hello World</data>
```

### 3. 在你的控制器中使用

```php
class YourController {
    private $ethereum;

    public function __construct() {
        $this->ethereum = new EthereumAPI();
    }

    public function storeXmlData($xmlData) {
        try {
            // 存儲到區塊鏈
            $txHash = $this->ethereum->setXml($xmlData);
            $receipt = $this->ethereum->waitForTransaction($txHash);

            // 同時存儲到數據庫
            $this->saveToDatabase($xmlData, $txHash, $receipt);

            return [
                'success' => true,
                'txHash' => $txHash,
                'blockNumber' => $receipt['blockNumber']
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getXmlData() {
        try {
            $xml = $this->ethereum->getXml();
            return ['success' => true, 'xml' => $xml];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
```

### 4. 前端 JavaScript 調用

```javascript
// 獲取 XML 數據
async function getXml() {
  const response = await fetch('/ethereum_api.php?action=getXml');
  const data = await response.json();
  return data.xml;
}

// 設置 XML 數據
async function setXml(xmlData) {
  const formData = new FormData();
  formData.append('xml', xmlData);

  const response = await fetch('/ethereum_api.php?action=setXml', {
    method: 'POST',
    body: formData,
  });

  const result = await response.json();
  return result;
}

// 使用範例
setXml('<user>John Doe</user>').then((result) => {
  console.log('Transaction Hash:', result.txHash);
  console.log('Block Number:', result.receipt.blockNumber);
});
```

## 錯誤處理

```php
try {
    $api = new EthereumAPI();
    $result = $api->setXml($xmlData);
} catch (Exception $e) {
    // 處理錯誤
    error_log("Ethereum API Error: " . $e->getMessage());

    // 可以選擇降級到傳統數據庫存儲
    $this->fallbackToDatabase($xmlData);
}
```

## 性能考慮

1. **交易確認時間**: 私鏈出塊較快，通常 1-2 秒內確認
2. **Gas 費用**: 私鏈 gas 費用為 0，無需擔心費用
3. **並發處理**: 建議使用隊列處理大量交易
4. **錯誤重試**: 實現自動重試機制

## 安全注意事項

1. **RPC 端點**: 確保 RPC 端點安全，不要暴露在公網
2. **帳戶私鑰**: 私鑰已解鎖在 Geth 中，確保服務器安全
3. **輸入驗證**: 驗證所有輸入的 XML 數據
4. **錯誤信息**: 不要向用戶暴露詳細的錯誤信息

## 監控和日誌

```php
// 添加日誌記錄
class EthereumAPI {
    private function log($message, $level = 'INFO') {
        $log = date('Y-m-d H:i:s') . " [$level] $message\n";
        file_put_contents('/var/log/ethereum_api.log', $log, FILE_APPEND);
    }

    public function setXml($xml) {
        $this->log("Setting XML: " . substr($xml, 0, 100) . "...");

        try {
            $result = $this->sendTransaction('setXml', [$xml]);
            $this->log("Transaction sent: $result");
            return $result;
        } catch (Exception $e) {
            $this->log("Error setting XML: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
}
```

## 測試

```php
// 測試腳本
$api = new EthereumAPI();

echo "Block Number: " . $api->getBlockNumber() . "\n";
echo "Balance: " . $api->getBalance() . " wei\n";
echo "Current XML: " . $api->getXml() . "\n";

$testXml = '<test>PHP Integration Test</test>';
$txHash = $api->setXml($testXml);
echo "Transaction Hash: $txHash\n";

$receipt = $api->waitForTransaction($txHash);
echo "Confirmed in block: " . $receipt['blockNumber'] . "\n";

$updatedXml = $api->getXml();
echo "Updated XML: $updatedXml\n";
```

這樣你就可以輕鬆地將以太坊智能合約整合到你的 PHP 後端中了！
