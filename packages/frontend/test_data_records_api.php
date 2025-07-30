<?php
/**
 * 測試數據記錄查詢 API
 * 驗證 API 是否正常工作
 */

echo "🧪 測試數據記錄查詢 API\n";
echo "========================\n\n";

// 包含 API 檔案
require_once 'data_records_api.php';

class DataRecordsAPITest {
    private $api;

    public function __construct() {
        $this->api = new DataRecordsAPI();
    }

    /**
     * 測試 1: 基本連接
     */
    public function testBasicConnection() {
        echo "1️⃣ 測試基本連接...\n";

        try {
            $contractAddress = $this->api->contractAddress;
            echo "   ✅ 合約地址: " . $contractAddress . "\n";
            echo "   ✅ API 初始化成功\n";
            return true;
        } catch (Exception $e) {
            echo "   ❌ 連接失敗: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * 測試 2: 獲取所有記錄
     */
    public function testGetAllRecords() {
        echo "\n2️⃣ 測試獲取所有記錄...\n";

        try {
            $result = $this->api->getAllDataRecords();

            if ($result['success']) {
                echo "   ✅ 獲取記錄成功\n";
                echo "   📊 總記錄數: " . $result['total'] . "\n";

                if ($result['total'] > 0) {
                    echo "   📋 記錄範例:\n";
                    $firstRecord = $result['records'][0];
                    echo "      - 交易哈希: " . substr($firstRecord['txHash'], 0, 20) . "...\n";
                    echo "      - 區塊號: " . $firstRecord['blockNumber'] . "\n";
                    echo "      - 描述: " . $firstRecord['description'] . "\n";
                    echo "      - 時間: " . date('Y-m-d H:i:s', $firstRecord['timestamp']) . "\n";
                } else {
                    echo "   ℹ️  暫無記錄\n";
                }
                return true;
            } else {
                echo "   ❌ 獲取記錄失敗: " . $result['error'] . "\n";
                return false;
            }
        } catch (Exception $e) {
            echo "   ❌ 測試失敗: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * 測試 3: 獲取單筆記錄
     */
    public function testGetSingleRecord() {
        echo "\n3️⃣ 測試獲取單筆記錄...\n";

        try {
            // 先獲取所有記錄
            $allRecords = $this->api->getAllDataRecords();

            if ($allRecords['success'] && $allRecords['total'] > 0) {
                $firstTxHash = $allRecords['records'][0]['txHash'];
                $result = $this->api->getDataRecord($firstTxHash);

                if ($result['success']) {
                    echo "   ✅ 獲取單筆記錄成功\n";
                    echo "   📋 記錄詳情:\n";
                    $record = $result['record'];
                    echo "      - 交易哈希: " . $record['txHash'] . "\n";
                    echo "      - 區塊號: " . $record['blockNumber'] . "\n";
                    echo "      - 描述: " . $record['description'] . "\n";
                    return true;
                } else {
                    echo "   ❌ 獲取單筆記錄失敗: " . $result['error'] . "\n";
                    return false;
                }
            } else {
                echo "   ℹ️  暫無記錄可測試\n";
                return true;
            }
        } catch (Exception $e) {
            echo "   ❌ 測試失敗: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * 測試 4: 模擬 API 端點
     */
    public function testAPIEndpoints() {
        echo "\n4️⃣ 測試 API 端點...\n";

        // 模擬 GET 請求
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // 測試獲取所有記錄
        $_GET['action'] = 'getAllRecords';

        ob_start();
        include 'data_records_api.php';
        $output = ob_get_clean();

        $data = json_decode($output, true);

        if ($data && isset($data['success'])) {
            echo "   ✅ API 端點測試成功\n";
            echo "   📊 返回記錄數: " . ($data['total'] ?? 0) . "\n";
            return true;
        } else {
            echo "   ❌ API 端點測試失敗\n";
            echo "   📄 輸出: " . substr($output, 0, 200) . "...\n";
            return false;
        }
    }

    /**
     * 運行所有測試
     */
    public function runAllTests() {
        echo "🚀 開始測試數據記錄查詢 API\n";
        echo "========================\n\n";

        $tests = [
            'testBasicConnection',
            'testGetAllRecords',
            'testGetSingleRecord',
            'testAPIEndpoints'
        ];

        $passed = 0;
        $total = count($tests);

        foreach ($tests as $test) {
            if ($this->$test()) {
                $passed++;
            }
        }

        echo "\n📊 測試結果\n";
        echo "==========\n";
        echo "✅ 通過: $passed/$total\n";
        echo "❌ 失敗: " . ($total - $passed) . "/$total\n";

        if ($passed === $total) {
            echo "\n🎉 所有測試通過！API 可以正常使用。\n";
        } else {
            echo "\n⚠️  部分測試失敗，請檢查配置。\n";
        }

        return $passed === $total;
    }
}

// 執行測試
if (isset($_GET['test'])) {
    $test = new DataRecordsAPITest();
    $test->runAllTests();
} else {
    // 顯示測試說明
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>數據記錄查詢 API 測試</title>
        <meta charset="utf-8">
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .test-btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 10px 0; }
            .test-btn:hover { background: #0056b3; }
            .api-info { background: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0; }
            pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🧪 數據記錄查詢 API 測試</h1>

            <div class="api-info">
                <h3>API 資訊</h3>
                <p><strong>合約地址:</strong> <?php echo (new DataRecordsAPI())->contractAddress ?? '未設置'; ?></p>
                <p><strong>RPC 端點:</strong> http://localhost:8545</p>
            </div>

            <h3>測試項目</h3>
            <ul>
                <li>基本連接測試</li>
                <li>獲取所有記錄</li>
                <li>獲取單筆記錄</li>
                <li>API 端點測試</li>
            </ul>

            <button class="test-btn" onclick="runTest()">開始測試</button>

            <div id="test-results"></div>

            <h3>API 使用範例</h3>
            <pre>
// 獲取所有記錄
GET /data_records_api.php?action=getAllRecords

// 獲取單筆記錄
GET /data_records_api.php?action=getRecord&txHash=0x...

// 預設查詢
GET /data_records_api.php
            </pre>
        </div>

        <script>
        function runTest() {
            const resultsDiv = document.getElementById('test-results');
            resultsDiv.innerHTML = '<h3>測試中...</h3><pre>正在執行測試...</pre>';

            fetch('?test=1')
                .then(response => response.text())
                .then(data => {
                    resultsDiv.innerHTML = '<h3>測試結果</h3><pre>' + data + '</pre>';
                })
                .catch(error => {
                    resultsDiv.innerHTML = '<h3>測試失敗</h3><pre>錯誤: ' + error.message + '</pre>';
                });
        }
        </script>
    </body>
    </html>
    <?php
}
?>
