<?php
/**
 * 後端 API 測試腳本
 * 測試所有 API 端點的功能
 */

echo "🧪 後端 API 測試\n";
echo "==============\n\n";

// 測試配置
$baseUrl = 'http://localhost'; // 本地測試
$apiEndpoints = [
    'ethereum_api' => '/ethereum_api.php',
    'data_records_api' => '/data_records_api.php'
];

class APITester {
    private $baseUrl;
    private $results = [];

    public function __construct($baseUrl) {
        $this->baseUrl = $baseUrl;
    }

    /**
     * 測試 Ethereum API
     */
    public function testEthereumAPI() {
        echo "1️⃣ 測試 Ethereum API\n";
        echo "-------------------\n";

        $tests = [
            'getBlockNumber' => [
                'method' => 'POST',
                'data' => ['action' => 'getBlockNumber']
            ],
            'getXml' => [
                'method' => 'POST',
                'data' => ['action' => 'getXml']
            ],
            'getAllRecords' => [
                'method' => 'POST',
                'data' => ['action' => 'getAllRecords']
            ]
        ];

        foreach ($tests as $testName => $testConfig) {
            $this->runTest('ethereum_api', $testName, $testConfig);
        }
    }

    /**
     * 測試數據記錄 API
     */
    public function testDataRecordsAPI() {
        echo "\n2️⃣ 測試數據記錄 API\n";
        echo "-------------------\n";

        $tests = [
            'getAllRecords' => [
                'method' => 'GET',
                'url' => '?action=getAllRecords'
            ]
        ];

        foreach ($tests as $testName => $testConfig) {
            $this->runTest('data_records_api', $testName, $testConfig);
        }
    }

    /**
     * 運行單個測試
     */
    private function runTest($apiName, $testName, $config) {
        echo "   🔍 測試: $testName\n";

        $url = $this->baseUrl . $apiEndpoints[$apiName];
        if (isset($config['url'])) {
            $url .= $config['url'];
        }

        $options = [
            'http' => [
                'method' => $config['method'],
                'header' => 'Content-Type: application/json',
                'timeout' => 10
            ]
        ];

        if ($config['method'] === 'POST' && isset($config['data'])) {
            $options['http']['content'] = json_encode($config['data']);
        }

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === false) {
            echo "      ❌ 請求失敗\n";
            $this->results[$apiName][$testName] = false;
            return;
        }

        $data = json_decode($result, true);

        if ($data && isset($data['success'])) {
            if ($data['success']) {
                echo "      ✅ 成功\n";
                if (isset($data['data'])) {
                    echo "         📊 數據: " . $this->summarizeData($data['data']) . "\n";
                }
                $this->results[$apiName][$testName] = true;
            } else {
                echo "      ❌ 失敗: " . ($data['error'] ?? '未知錯誤') . "\n";
                $this->results[$apiName][$testName] = false;
            }
        } else {
            echo "      ⚠️  響應格式異常\n";
            echo "         📄 響應: " . substr($result, 0, 100) . "...\n";
            $this->results[$apiName][$testName] = false;
        }
    }

    /**
     * 總結數據
     */
    private function summarizeData($data) {
        if (is_array($data)) {
            if (isset($data['records'])) {
                return "記錄數: " . count($data['records']);
            } elseif (isset($data['total'])) {
                return "總數: " . $data['total'];
            } else {
                return "陣列長度: " . count($data);
            }
        } elseif (is_string($data)) {
            return "字串長度: " . strlen($data);
        } else {
            return "類型: " . gettype($data);
        }
    }

    /**
     * 顯示測試結果
     */
    public function showResults() {
        echo "\n📊 測試結果總結\n";
        echo "==============\n";

        $totalTests = 0;
        $passedTests = 0;

        foreach ($this->results as $apiName => $tests) {
            echo "\n$apiName:\n";
            foreach ($tests as $testName => $passed) {
                $status = $passed ? "✅" : "❌";
                echo "   $status $testName\n";
                $totalTests++;
                if ($passed) $passedTests++;
            }
        }

        echo "\n🎯 總體結果: $passedTests/$totalTests 通過\n";

        if ($passedTests === $totalTests) {
            echo "🎉 所有測試通過！API 功能正常。\n";
        } else {
            echo "⚠️  部分測試失敗，請檢查配置。\n";
        }
    }
}

// 運行測試
$tester = new APITester($baseUrl);
$tester->testEthereumAPI();
$tester->testDataRecordsAPI();
$tester->showResults();

echo "\n📝 測試完成\n";
echo "==========\n";
echo "如需在生產環境測試，請修改 \$baseUrl 為實際伺服器地址。\n";
?>
