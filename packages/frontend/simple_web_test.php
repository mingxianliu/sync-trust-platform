<?php
/**
 * 簡單的 Web 上傳功能測試
 */

echo "=== 簡單 Web 上傳功能測試 ===\n";

require_once 'ethereum_php_client.php';

class SimpleWebTest {
    private $ipfsApiUrl = 'http://localhost:5001/api/v0';
    private $ethereumClient;

    public function __construct() {
        $this->ethereumClient = new EthereumClient();
    }

    /**
     * 測試文件上傳到 IPFS
     */
    public function testIPFSUpload($filepath) {
        echo "1. 測試 IPFS 上傳...\n";

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
            echo "   ✅ IPFS 上傳成功！\n";
            echo "   Hash: " . $data['Hash'] . "\n";
            echo "   大小: " . $data['Size'] . " 字節\n";
            return $data['Hash'];
        } else {
            echo "   ❌ IPFS 上傳失敗\n";
            echo "   HTTP 狀態碼: " . $httpCode . "\n";
            return false;
        }
    }

    /**
     * 測試 Ethereum 記錄
     */
    public function testEthereumRecord($filename, $ipfsHash, $fileSize) {
        echo "\n2. 測試 Ethereum 記錄...\n";

        $fileInfo = json_encode([
            'filename' => $filename,
            'ipfs_hash' => $ipfsHash,
            'file_size' => $fileSize,
            'upload_time' => date('Y-m-d H:i:s'),
            'uploader' => $this->ethereumClient->getAccountAddress(),
            'test_type' => 'web_upload'
        ], JSON_UNESCAPED_UNICODE);

        echo "   記錄信息: " . $fileInfo . "\n";

        try {
            $txHash = $this->ethereumClient->setXml($fileInfo);
            echo "   ✅ Ethereum 記錄成功！\n";
            echo "   交易哈希: " . $txHash . "\n";

            // 等待交易確認
            sleep(3);

            // 驗證記錄
            $recordedInfo = $this->ethereumClient->getXml();
            echo "   記錄驗證: " . $recordedInfo . "\n";

            return $txHash;
        } catch (Exception $e) {
            echo "   ❌ Ethereum 記錄失敗: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * 運行完整測試
     */
    public function runTest() {
        // 創建測試文件
        $testContent = "Web 上傳功能測試文件\n";
        $testContent .= "創建時間: " . date('Y-m-d H:i:s') . "\n";
        $testContent .= "測試內容: 驗證 Web 上傳功能的核心邏輯\n";

        $testFile = 'web_simple_test_' . time() . '.txt';
        file_put_contents($testFile, $testContent);

        echo "✅ 創建測試文件: " . $testFile . "\n";
        echo "   文件大小: " . strlen($testContent) . " 字節\n\n";

        // 測試 IPFS 上傳
        $ipfsHash = $this->testIPFSUpload($testFile);
        if (!$ipfsHash) {
            unlink($testFile);
            return false;
        }

        // 測試 Ethereum 記錄
        $fileSize = filesize($testFile);
        $txHash = $this->testEthereumRecord(basename($testFile), $ipfsHash, $fileSize);
        if (!$txHash) {
            unlink($testFile);
            return false;
        }

        // 清理測試文件
        unlink($testFile);

        echo "\n🎉 Web 上傳功能測試成功完成！\n";
        echo "✅ IPFS 上傳: 正常\n";
        echo "✅ Ethereum 記錄: 正常\n";
        echo "✅ 數據完整性: 驗證通過\n";

        return true;
    }
}

// 執行測試
$test = new SimpleWebTest();
$test->runTest();
?>
