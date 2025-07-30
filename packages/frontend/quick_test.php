<?php
/**
 * 快速測試腳本 - 供 RD 快速驗證所有功能
 * 使用方法: php quick_test.php
 */

echo "🚀 區塊鏈和 IPFS 快速測試\n";
echo "========================\n\n";

// 包含必要的文件
require_once 'ethereum_php_client.php';

class QuickTest {
    private $ipfsApiUrl = 'http://localhost:5001/api/v0';
    private $ethereumClient;

    public function __construct() {
        $this->ethereumClient = new EthereumClient();
    }

    /**
     * 測試 1: Ethereum 連接
     */
    public function testEthereum() {
        echo "1️⃣ 測試 Ethereum 連接...\n";

        try {
            $balance = $this->ethereumClient->getBalance();
            $blockNumber = $this->ethereumClient->getBlockNumber();
            $contractAddress = $this->ethereumClient->getContractAddress();

            echo "   ✅ 連接成功\n";
            echo "   💰 餘額: " . $balance . " ETH\n";
            echo "   📦 區塊: " . $blockNumber . "\n";
            echo "   📄 合約: " . substr($contractAddress, 0, 20) . "...\n";
            return true;
        } catch (Exception $e) {
            echo "   ❌ 連接失敗: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * 測試 2: IPFS 連接
     */
    public function testIPFS() {
        echo "\n2️⃣ 測試 IPFS 連接...\n";

        $url = $this->ipfsApiUrl . '/id';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200 && $result) {
            $data = json_decode($result, true);
            echo "   ✅ 連接成功\n";
            echo "   🆔 節點 ID: " . substr($data['ID'], 0, 20) . "...\n";
            echo "   📋 版本: " . $data['AgentVersion'] . "\n";
            return true;
        } else {
            echo "   ❌ 連接失敗 (HTTP: " . $httpCode . ")\n";
            return false;
        }
    }

    /**
     * 測試 3: 文件上傳到 IPFS
     */
    public function testIPFSUpload() {
        echo "\n3️⃣ 測試 IPFS 文件上傳...\n";

        // 創建測試文件
        $content = "快速測試文件 - " . date('Y-m-d H:i:s');
        $filename = 'quick_test_' . time() . '.txt';
        file_put_contents($filename, $content);

        // 上傳到 IPFS
        $url = $this->ipfsApiUrl . '/add';
        $postData = ['file' => new CURLFile($filename)];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200 && $result) {
            $data = json_decode($result, true);
            echo "   ✅ 上傳成功\n";
            echo "   🔗 Hash: " . $data['Hash'] . "\n";
            echo "   📏 大小: " . $data['Size'] . " 字節\n";

            // 清理文件
            unlink($filename);
            return $data['Hash'];
        } else {
            echo "   ❌ 上傳失敗 (HTTP: " . $httpCode . ")\n";
            unlink($filename);
            return false;
        }
    }

    /**
     * 測試 4: Ethereum 數據記錄
     */
    public function testEthereumRecord($ipfsHash) {
        echo "\n4️⃣ 測試 Ethereum 數據記錄...\n";

        $testData = json_encode([
            'test_type' => 'quick_test',
            'ipfs_hash' => $ipfsHash,
            'timestamp' => date('Y-m-d H:i:s'),
            'description' => '快速測試數據'
        ], JSON_UNESCAPED_UNICODE);

        try {
            $txHash = $this->ethereumClient->setXml($testData);
            echo "   ✅ 記錄成功\n";
            echo "   🔗 交易: " . substr($txHash, 0, 20) . "...\n";

            // 等待確認
            sleep(2);

            // 驗證記錄
            $recordedData = $this->ethereumClient->getXml();
            echo "   ✅ 驗證成功\n";
            echo "   📝 記錄: " . substr($recordedData, 0, 50) . "...\n";

            return true;
        } catch (Exception $e) {
            echo "   ❌ 記錄失敗: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * 測試 5: 文件下載驗證
     */
    public function testIPFSDownload($ipfsHash) {
        echo "\n5️⃣ 測試 IPFS 文件下載...\n";

        $url = $this->ipfsApiUrl . '/cat?arg=' . $ipfsHash;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200 && $result) {
            echo "   ✅ 下載成功\n";
            echo "   📏 大小: " . strlen($result) . " 字節\n";
            echo "   📄 內容: " . substr($result, 0, 30) . "...\n";
            return true;
        } else {
            echo "   ❌ 下載失敗 (HTTP: " . $httpCode . ")\n";
            return false;
        }
    }

    /**
     * 運行所有測試
     */
    public function runAllTests() {
        $results = [];

        // 測試 1: Ethereum
        $results['ethereum'] = $this->testEthereum();

        // 測試 2: IPFS
        $results['ipfs'] = $this->testIPFS();

        // 測試 3: IPFS 上傳
        $ipfsHash = $this->testIPFSUpload();
        $results['ipfs_upload'] = ($ipfsHash !== false);

        // 測試 4: Ethereum 記錄
        if ($ipfsHash) {
            $results['ethereum_record'] = $this->testEthereumRecord($ipfsHash);

            // 測試 5: IPFS 下載
            $results['ipfs_download'] = $this->testIPFSDownload($ipfsHash);
        } else {
            $results['ethereum_record'] = false;
            $results['ipfs_download'] = false;
        }

        // 顯示總結
        $this->showSummary($results);

        return $results;
    }

    /**
     * 顯示測試總結
     */
    private function showSummary($results) {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "📊 測試總結\n";
        echo str_repeat("=", 50) . "\n";

        $passed = 0;
        $total = count($results);

        foreach ($results as $test => $result) {
            $status = $result ? "✅" : "❌";
            $testName = str_replace('_', ' ', ucfirst($test));
            echo "   $status $testName\n";
            if ($result) $passed++;
        }

        echo "\n📈 結果: $passed/$total 測試通過\n";

        if ($passed == $total) {
            echo "🎉 所有測試通過！系統運行正常。\n";
        } else {
            echo "⚠️  部分測試失敗，請檢查系統狀態。\n";
        }

        echo str_repeat("=", 50) . "\n";
    }
}

// 執行測試
if (php_sapi_name() === 'cli') {
    $test = new QuickTest();
    $test->runAllTests();
} else {
    echo "此腳本需要在命令行中運行。\n";
    echo "使用方法: php quick_test.php\n";
}
?>
