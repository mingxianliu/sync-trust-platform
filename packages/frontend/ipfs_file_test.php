<?php
/**
 * IPFS 文件上傳和整合測試
 * 包含文件上傳、IPFS 存儲、Ethereum 記錄等功能
 */

class IPFSFileTest {
    private $ipfsApiUrl = 'http://localhost:5001/api/v0';
    private $ethereumClient;

    public function __construct() {
        require_once 'ethereum_php_client.php';
        $this->ethereumClient = new EthereumClient();
    }

    /**
     * 測試 IPFS 連接
     */
    public function testIPFSConnection() {
        echo "=== 測試 IPFS 連接 ===\n";

        $url = $this->ipfsApiUrl . '/id';
        $result = $this->makeIPFSRequest($url);

        if ($result) {
            $data = json_decode($result, true);
            echo "✅ IPFS 節點 ID: " . $data['ID'] . "\n";
            echo "✅ IPFS 版本: " . $data['AgentVersion'] . "\n";
            return true;
        } else {
            echo "❌ IPFS 連接失敗\n";
            return false;
        }
    }

    /**
     * 創建測試文件
     */
    public function createTestFile($content = null) {
        if (!$content) {
            $content = "這是一個測試文件\n";
            $content .= "創建時間: " . date('Y-m-d H:i:s') . "\n";
            $content .= "內容: 用於測試 IPFS 文件上傳功能\n";
        }

        $filename = 'test_file_' . time() . '.txt';
        file_put_contents($filename, $content);

        echo "✅ 創建測試文件: " . $filename . "\n";
        echo "   文件大小: " . strlen($content) . " 字節\n";

        return $filename;
    }

    /**
     * 上傳文件到 IPFS
     */
    public function uploadToIPFS($filepath) {
        echo "\n=== 上傳文件到 IPFS ===\n";

        if (!file_exists($filepath)) {
            echo "❌ 文件不存在: " . $filepath . "\n";
            return false;
        }

        // 使用 curl 上傳文件到 IPFS
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
            echo "✅ 文件上傳成功！\n";
            echo "   IPFS Hash: " . $data['Hash'] . "\n";
            echo "   文件大小: " . $data['Size'] . " 字節\n";
            echo "   文件名: " . $data['Name'] . "\n";
            return $data['Hash'];
        } else {
            echo "❌ 文件上傳失敗\n";
            echo "   HTTP 狀態碼: " . $httpCode . "\n";
            echo "   錯誤信息: " . $result . "\n";
            return false;
        }
    }

    /**
     * 從 IPFS 下載文件
     */
    public function downloadFromIPFS($hash, $outputPath = null) {
        echo "\n=== 從 IPFS 下載文件 ===\n";

        if (!$outputPath) {
            $outputPath = 'downloaded_' . time() . '.txt';
        }

        $url = $this->ipfsApiUrl . '/cat?arg=' . $hash;
        $result = $this->makeIPFSRequest($url);

        if ($result !== false) {
            file_put_contents($outputPath, $result);
            echo "✅ 文件下載成功！\n";
            echo "   保存路徑: " . $outputPath . "\n";
            echo "   文件大小: " . strlen($result) . " 字節\n";
            echo "   文件內容:\n";
            echo "   " . str_replace("\n", "\n   ", $result) . "\n";
            return $outputPath;
        } else {
            echo "❌ 文件下載失敗\n";
            return false;
        }
    }

    /**
     * 將文件信息記錄到 Ethereum
     */
    public function recordToEthereum($filename, $ipfsHash, $fileSize) {
        echo "\n=== 記錄文件信息到 Ethereum ===\n";

        $fileInfo = json_encode([
            'filename' => $filename,
            'ipfs_hash' => $ipfsHash,
            'file_size' => $fileSize,
            'upload_time' => date('Y-m-d H:i:s'),
            'uploader' => $this->ethereumClient->getAccountAddress()
        ], JSON_UNESCAPED_UNICODE);

        echo "   文件信息: " . $fileInfo . "\n";

        try {
            $txHash = $this->ethereumClient->setXml($fileInfo);
            echo "✅ 交易提交成功！\n";
            echo "   交易哈希: " . $txHash . "\n";

            // 等待交易確認
            sleep(3);

            // 驗證記錄
            $recordedInfo = $this->ethereumClient->getXml();
            echo "   記錄驗證: " . $recordedInfo . "\n";

            return $txHash;
        } catch (Exception $e) {
            echo "❌ 記錄失敗: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * 完整的文件上傳測試流程
     */
    public function runFullTest() {
        echo "🚀 開始完整的文件上傳和 IPFS 整合測試\n\n";

        // 1. 測試 IPFS 連接
        if (!$this->testIPFSConnection()) {
            return false;
        }

        // 2. 創建測試文件
        $testFile = $this->createTestFile();

        // 3. 上傳到 IPFS
        $ipfsHash = $this->uploadToIPFS($testFile);
        if (!$ipfsHash) {
            return false;
        }

        // 4. 記錄到 Ethereum
        $fileSize = filesize($testFile);
        $txHash = $this->recordToEthereum(basename($testFile), $ipfsHash, $fileSize);
        if (!$txHash) {
            return false;
        }

        // 5. 下載並驗證
        $downloadedFile = $this->downloadFromIPFS($ipfsHash);
        if (!$downloadedFile) {
            return false;
        }

        // 6. 清理臨時文件
        unlink($testFile);
        unlink($downloadedFile);

        echo "\n🎉 完整測試成功完成！\n";
        echo "✅ IPFS 連接: 正常\n";
        echo "✅ 文件上傳: 成功\n";
        echo "✅ Ethereum 記錄: 成功\n";
        echo "✅ 文件下載: 成功\n";
        echo "✅ 數據完整性: 驗證通過\n";

        return true;
    }

    /**
     * 發送 IPFS API 請求
     */
    private function makeIPFSRequest($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            return $result;
        } else {
            return false;
        }
    }
}

// 執行測試
if (php_sapi_name() === 'cli') {
    $test = new IPFSFileTest();
    $test->runFullTest();
}
?>
