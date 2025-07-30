<?php
/**
 * Web 界面的文件上傳和 IPFS 整合測試
 */

require_once 'ethereum_php_client.php';

class WebUploadTest {
    private $ipfsApiUrl = 'http://localhost:5001/api/v0';
    private $ethereumClient;

    public function __construct() {
        $this->ethereumClient = new EthereumClient();
    }

    /**
     * 獲取 Ethereum 客戶端
     */
    public function getEthereumClient() {
        return $this->ethereumClient;
    }

    /**
     * 處理文件上傳
     */
    public function handleUpload() {
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
            $file = $_FILES['file'];

            if ($file['error'] === UPLOAD_ERR_OK) {
                return $this->processFile($file);
            } else {
                return ['error' => '文件上傳失敗: ' . $file['error']];
            }
        }
        return null;
    }

    /**
     * 處理上傳的文件
     */
    private function processFile($file) {
        $result = [];

        try {
            // 1. 上傳到 IPFS
            $ipfsHash = $this->uploadToIPFS($file['tmp_name']);
            if (!$ipfsHash) {
                return ['error' => 'IPFS 上傳失敗'];
            }

            $result['ipfs_hash'] = $ipfsHash;
            $result['filename'] = $file['name'];
            $result['size'] = $file['size'];

            // 2. 記錄到 Ethereum
            $fileInfo = json_encode([
                'filename' => $file['name'],
                'ipfs_hash' => $ipfsHash,
                'file_size' => $file['size'],
                'upload_time' => date('Y-m-d H:i:s'),
                'uploader' => $this->ethereumClient->getAccountAddress(),
                'mime_type' => $file['type']
            ], JSON_UNESCAPED_UNICODE);

            $txHash = $this->ethereumClient->setXml($fileInfo);
            if (!$txHash) {
                return ['error' => 'Ethereum 記錄失敗'];
            }

            $result['tx_hash'] = $txHash;
            $result['success'] = true;

        } catch (Exception $e) {
            $result['error'] = '處理失敗: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * 上傳文件到 IPFS
     */
    private function uploadToIPFS($filepath) {
        $url = $this->ipfsApiUrl . '/add';

        $postData = [
            'file' => new CURLFile($filepath)
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200 && $result) {
            $data = json_decode($result, true);
            return $data['Hash'];
        }

        return false;
    }

    /**
     * 獲取系統狀態
     */
    public function getSystemStatus() {
        $status = [];

        // Ethereum 狀態
        try {
            $status['ethereum'] = [
                'connected' => true,
                'contract_address' => $this->ethereumClient->getContractAddress(),
                'account_balance' => $this->ethereumClient->getBalance(),
                'block_number' => $this->ethereumClient->getBlockNumber()
            ];
        } catch (Exception $e) {
            $status['ethereum'] = ['connected' => false, 'error' => $e->getMessage()];
        }

        // IPFS 狀態
        $url = $this->ipfsApiUrl . '/id';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true); // 使用 POST 方法
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200 && $result) {
            $data = json_decode($result, true);
            $status['ipfs'] = [
                'connected' => true,
                'node_id' => $data['ID'],
                'version' => $data['AgentVersion']
            ];
        } else {
            $status['ipfs'] = ['connected' => false];
        }

        return $status;
    }

    /**
     * 手動上鏈 API - updateBlockchainHash
     */
    public function updateBlockchainHash($hash) {
        // 這裡假設直接寫入合約（可根據實際需求調整）
        try {
            $txHash = $this->ethereumClient->setXml($hash);
            return ['success' => true, 'tx_hash' => $txHash];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

// 處理上傳
$uploadTest = new WebUploadTest();
$uploadResult = $uploadTest->handleUpload();
$systemStatus = $uploadTest->getSystemStatus();

// 手動上鏈表單處理
$updateResult = null;
if (isset($_POST['manual_hash'])) {
    $hash = trim($_POST['manual_hash']);
    if ($hash !== '') {
        $updateResult = $uploadTest->updateBlockchainHash($hash);
    }
}

// AJAX 查詢交易 receipt
if (isset($_GET['tx_receipt']) && isset($_GET['tx'])) {
    header('Content-Type: application/json');
    $tx = $_GET['tx'];
    $result = ["found" => false];
    try {
        $client = new EthereumClient();
        $receipt = $client->getTransactionReceipt($tx);
        if ($receipt && isset($receipt['blockNumber'])) {
            $result['found'] = true;
            $result['blockNumber'] = isset($receipt['blockNumber']) ? hexdec($receipt['blockNumber']) : null;
            $result['status'] = (isset($receipt['status']) && $receipt['status'] === '0x1') ? '成功' : '失敗';
            $result['receipt'] = $receipt;
        }
    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
    }
    echo json_encode($result);
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文件上傳和 IPFS 整合測試</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .status-card {
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid;
        }
        .status-card.ethereum {
            background-color: #e8f5e8;
            border-left-color: #4caf50;
        }
        .status-card.ipfs {
            background-color: #e3f2fd;
            border-left-color: #2196f3;
        }
        .status-card.error {
            background-color: #ffebee;
            border-left-color: #f44336;
        }
        .upload-form {
            border: 2px dashed #ccc;
            padding: 30px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .upload-form:hover {
            border-color: #2196f3;
        }
        .result {
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .result.success {
            background-color: #e8f5e8;
            border: 1px solid #4caf50;
        }
        .result.error {
            background-color: #ffebee;
            border: 1px solid #f44336;
        }
        .btn {
            background-color: #2196f3;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #1976d2;
        }
        .file-input {
            margin: 20px 0;
        }
        .info {
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 文件上傳和 IPFS 整合測試</h1>

        <!-- 系統狀態 -->
        <div class="status">
            <div class="status-card <?php echo $systemStatus['ethereum']['connected'] ? 'ethereum' : 'error'; ?>">
                <h3>Ethereum 狀態</h3>
                <?php if ($systemStatus['ethereum']['connected']): ?>
                    <p>✅ 已連接</p>
                    <p>合約地址: <?php echo substr($systemStatus['ethereum']['contract_address'], 0, 20) . '...'; ?></p>
                    <p>餘額: <?php echo $systemStatus['ethereum']['account_balance']; ?> ETH</p>
                    <p>區塊: <?php echo $systemStatus['ethereum']['block_number']; ?></p>
                <?php else: ?>
                    <p>❌ 連接失敗</p>
                    <p><?php echo $systemStatus['ethereum']['error']; ?></p>
                <?php endif; ?>
            </div>

            <div class="status-card <?php echo $systemStatus['ipfs']['connected'] ? 'ipfs' : 'error'; ?>">
                <h3>IPFS 狀態</h3>
                <?php if ($systemStatus['ipfs']['connected']): ?>
                    <p>✅ 已連接</p>
                    <p>節點 ID: <?php echo substr($systemStatus['ipfs']['node_id'], 0, 20) . '...'; ?></p>
                    <p>版本: <?php echo $systemStatus['ipfs']['version']; ?></p>
                <?php else: ?>
                    <p>❌ 連接失敗</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- 說明 -->
        <div class="info">
            <h3>📋 功能說明</h3>
            <p>此測試系統可以：</p>
            <ul>
                <li>上傳文件到 IPFS 分散式存儲</li>
                <li>將文件信息記錄到 Ethereum 區塊鏈</li>
                <li>驗證文件上傳和記錄的完整性</li>
            </ul>
        </div>

        <!-- 上傳表單 -->
        <form method="POST" enctype="multipart/form-data" class="upload-form">
            <h3>📁 選擇要上傳的文件</h3>
            <div class="file-input">
                <input type="file" name="file" required>
            </div>
            <button type="submit" class="btn">🚀 上傳到 IPFS 並記錄到 Ethereum</button>
        </form>

        <!-- 上傳結果 -->
        <?php if ($uploadResult): ?>
            <div class="result <?php echo isset($uploadResult['success']) ? 'success' : 'error'; ?>">
                <?php if (isset($uploadResult['success'])): ?>
                    <h3>✅ 上傳成功！</h3>
                    <p><strong>文件名:</strong> <?php echo htmlspecialchars($uploadResult['filename']); ?></p>
                    <p><strong>文件大小:</strong> <?php echo number_format($uploadResult['size']); ?> 字節</p>
                    <p><strong>IPFS Hash:</strong> <code><?php echo $uploadResult['ipfs_hash']; ?></code></p>
                    <p><strong>Ethereum 交易:</strong> <code><?php echo $uploadResult['tx_hash']; ?></code></p>
                    <p><strong>IPFS 訪問鏈接:</strong> <a href="https://ipfs.io/ipfs/<?php echo $uploadResult['ipfs_hash']; ?>" target="_blank">查看文件</a></p>
                <?php else: ?>
                    <h3>❌ 上傳失敗</h3>
                    <p><?php echo htmlspecialchars($uploadResult['error']); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- 當前記錄 -->
        <div class="info">
            <h3>📝 當前 Ethereum 記錄</h3>
            <?php
            try {
                $currentRecord = $uploadTest->getEthereumClient()->getXml();
                if ($currentRecord) {
                    $recordData = json_decode($currentRecord, true);
                    if ($recordData) {
                        echo "<p><strong>文件名:</strong> " . (isset($recordData['filename']) ? htmlspecialchars($recordData['filename']) : '') . "</p>";
                        echo "<p><strong>IPFS Hash:</strong> <code>" . (isset($recordData['ipfs_hash']) ? $recordData['ipfs_hash'] : '') . "</code></p>";
                        echo "<p><strong>上傳時間:</strong> " . (isset($recordData['upload_time']) ? $recordData['upload_time'] : '') . "</p>";
                        echo "<p><strong>上傳者:</strong> " . (isset($recordData['uploader']) ? substr($recordData['uploader'], 0, 20) : '') . "...</p>";
                    } else {
                        echo "<p>記錄格式錯誤</p>";
                    }
                } else {
                    echo "<p>暫無記錄</p>";
                }
            } catch (Exception $e) {
                echo "<p>讀取記錄失敗: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ?>
        </div>

        <!-- 手動上鏈測試區塊 -->
        <div class="info">
            <h3>🔗 手動上鏈 API 測試 (updateBlockchainHash)</h3>
            <form method="post" style="margin-bottom:10px;" id="manualHashForm">
                <input type="text" name="manual_hash" id="manual_hash_input" placeholder="請輸入要上鏈的 hash 或內容" style="width:60%;padding:8px;">
                <button type="submit" class="btn">手動上鏈</button>
            </form>
            <div style="background:#f8f8f8;border:1px dashed #aaa;padding:10px 15px;margin-bottom:10px;">
                <strong>智慧建築數據範例：</strong><br>
                <code id="smart-building-example" style="word-break:break-all;">
{
  &quot;building_id&quot;: &quot;TW-BLDG-001&quot;,
  &quot;timestamp&quot;: &quot;2025-07-07 12:00:00&quot;,
  &quot;sensor_data&quot;: {
    &quot;environment&quot;: {
      &quot;temperature&quot;: 25.3,
      &quot;humidity&quot;: 60.2,
      &quot;co2&quot;: 420,
      &quot;pm25&quot;: 8.5
    },
    &quot;energy&quot;: {
      &quot;total_power_consumption&quot;: 1250.5,
      &quot;solar_panel_output&quot;: 320.8,
      &quot;battery_level&quot;: 85.2,
      &quot;peak_demand&quot;: 1800.0
    },
    &quot;access_control&quot;: [
      {
        &quot;card_number&quot;: &quot;AC001234&quot;,
        &quot;user_name&quot;: &quot;張小明&quot;,
        &quot;access_time&quot;: &quot;2025-07-07 08:30:15&quot;,
        &quot;direction&quot;: &quot;entry&quot;,
        &quot;location&quot;: &quot;主入口&quot;
      },
      {
        &quot;card_number&quot;: &quot;AC005678&quot;,
        &quot;user_name&quot;: &quot;李小華&quot;,
        &quot;access_time&quot;: &quot;2025-07-07 09:15:42&quot;,
        &quot;direction&quot;: &quot;exit&quot;,
        &quot;location&quot;: &quot;側門&quot;
      }
    ]
  },
  &quot;uploader&quot;: &quot;synckeyadmin&quot;,
  &quot;description&quot;: &quot;智慧建築環境監控數據上鏈測試&quot;
}
                </code>
                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('smart-building-example').innerText);this.innerText='已複製！';setTimeout(()=>this.innerText='複製範例',1500);" style="margin-left:10px;">複製範例</button>
            </div>
            <?php if ($updateResult): ?>
                <div class="result <?php echo $updateResult['success'] ? 'success' : 'error'; ?>" id="manualTxResult">
                    <?php if ($updateResult['success']): ?>
                        <p>✅ 上鏈成功！</p>
                        <p>交易哈希: <code id="manualTxHash"><?php echo $updateResult['tx_hash']; ?></code></p>
                        <p>上鏈時間: <?php echo date('Y-m-d H:i:s'); ?></p>
                        <?php
                        // 查詢交易 receipt
                        $receipt = null;
                        try {
                            $receipt = $uploadTest->getEthereumClient()->getTransactionReceipt($updateResult['tx_hash']);
                        } catch (Exception $e) {}
                        if ($receipt && isset($receipt['blockNumber'])) {
                            $blockNumber = isset($receipt['blockNumber']) ? hexdec($receipt['blockNumber']) : '未知';
                            $status = (isset($receipt['status']) && $receipt['status'] === '0x1') ? '成功' : '失敗';
                        ?>
                        <p id="manualBlockNumber">區塊號: <?php echo $blockNumber; ?></p>
                        <p id="manualTxStatus">交易狀態: <?php echo $status; ?></p>
                        <details style="margin-top:8px;"><summary>回傳內容 (receipt)</summary><pre style="white-space:pre-wrap;word-break:break-all;background:#f8f8f8;padding:8px;border-radius:6px;" id="manualReceiptJson"><?php echo htmlspecialchars(json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre></details>
                        <?php } else { ?>
                        <p id="manualPending">（交易尚未上鏈，系統將自動查詢狀態...）</p>
                        <script>
                        // 自動查詢交易狀態
                        let manualTxHash = document.getElementById('manualTxHash').innerText;
                        let blockNumberElem = document.getElementById('manualBlockNumber');
                        let txStatusElem = document.getElementById('manualTxStatus');
                        let receiptElem = document.getElementById('manualReceiptJson');
                        let pendingElem = document.getElementById('manualPending');
                        let polling = true;
                        async function pollReceipt() {
                            if (!polling) return;
                            const res = await fetch('?tx_receipt=1&tx=' + manualTxHash);
                            const data = await res.json();
                            if (data.found) {
                                if (blockNumberElem) blockNumberElem.innerText = '區塊號: ' + data.blockNumber;
                                if (txStatusElem) txStatusElem.innerText = '交易狀態: ' + data.status;
                                if (receiptElem) receiptElem.innerText = JSON.stringify(data.receipt, null, 2);
                                if (pendingElem) pendingElem.style.display = 'none';
                                polling = false;
                            } else {
                                setTimeout(pollReceipt, 3000);
                            }
                        }
                        pollReceipt();
                        </script>
                        <?php } ?>
                    <?php else: ?>
                        <p>❌ 上鏈失敗: <?php echo htmlspecialchars($updateResult['error']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
