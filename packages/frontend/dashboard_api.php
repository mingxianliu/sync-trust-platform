<?php
/**
 * Dashboard API
 * 提供 Dashboard 頁面所需的統計數據和趨勢圖資料
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

/**
 * 區塊鏈數據快取管理器
 */
class BlockchainCache {
    private $dbFile;

    public function __construct() {
        $this->dbFile = __DIR__ . '/blockchain_cache.json';
    }

    /**
     * 儲存區塊鏈數據到快取
     */
    public function saveData($data) {
        $cacheData = [
            'timestamp' => time(),
            'data' => $data
        ];
        file_put_contents($this->dbFile, json_encode($cacheData, JSON_PRETTY_PRINT));
    }

    /**
     * 從快取讀取數據
     */
    public function loadData() {
        if (!file_exists($this->dbFile)) {
            return null;
        }

        $content = file_get_contents($this->dbFile);
        $cacheData = json_decode($content, true);

        // 檢查快取是否過期（1小時）
        if (time() - $cacheData['timestamp'] > 3600) {
            return null;
        }

        return $cacheData['data'];
    }

    /**
     * 儲存最後查詢的區塊號
     */
    public function saveLastBlock($blockNumber) {
        $lastBlockFile = __DIR__ . '/last_block.txt';
        file_put_contents($lastBlockFile, $blockNumber);
    }

    /**
     * 獲取最後查詢的區塊號
     */
    public function getLastBlock() {
        $lastBlockFile = __DIR__ . '/last_block.txt';
        if (file_exists($lastBlockFile)) {
            return (int)file_get_contents($lastBlockFile);
        }
        return 0;
    }
}

require_once 'ethereum_php_client.php';

// 直接包含 DataRecordsAPI 類，而不是整個文件
class DataRecordsAPI {
    private $ethereumClient;
    private $contractAddress;
    private $rpcUrl;
    private $cache;

    public function __construct() {
        $this->ethereumClient = new EthereumClient();
        $this->contractAddress = $this->ethereumClient->getContractAddress();
        $this->rpcUrl = 'http://localhost:8545';
        $this->cache = new BlockchainCache();
    }

    /**
     * 獲取所有數據記錄
     */
    public function getAllDataRecords() {
        try {
            // 先嘗試從快取讀取
            $cachedData = $this->cache->loadData();
            if ($cachedData !== null) {
                return $cachedData;
            }

            // 獲取當前區塊號
            $currentBlock = $this->ethereumClient->getBlockNumber();
            $lastProcessedBlock = $this->cache->getLastBlock();

            // 查詢所有區塊
            $startBlock = 0;

            $records = [];

            // 查詢每個區塊的交易
            for ($blockNumber = $startBlock; $blockNumber <= $currentBlock; $blockNumber++) {
                $blockTransactions = $this->getBlockTransactions($blockNumber);

                foreach ($blockTransactions as $tx) {
                    if ($this->isContractTransaction($tx)) {
                        $record = $this->parseTransaction($tx, $blockNumber);
                        if ($record) {
                            $records[] = $record;
                        }
                    }
                }
            }

            // 按時間倒序排列
            usort($records, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });

            $result = [
                'success' => true,
                'records' => $records,
                'total' => count($records)
            ];

            // 儲存到快取
            $this->cache->saveData($result);
            $this->cache->saveLastBlock($currentBlock);

            return $result;

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 獲取指定區塊的交易
     */
    private function getBlockTransactions($blockNumber) {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'eth_getBlockByNumber',
            'params' => ['0x' . dechex($blockNumber), true],
            'id' => time()
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data)
            ]
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($this->rpcUrl, false, $context);

        if ($result === false) {
            return [];
        }

        $response = json_decode($result, true);

        if (isset($response['result']) && isset($response['result']['transactions'])) {
            return $response['result']['transactions'];
        }

        return [];
    }

    /**
     * 檢查是否為合約交易
     */
    private function isContractTransaction($tx) {
        return isset($tx['to']) &&
               strtolower($tx['to']) === strtolower($this->contractAddress) &&
               isset($tx['input']) &&
               strlen($tx['input']) > 10;
    }

    /**
     * 解析交易數據
     */
    private function parseTransaction($tx, $blockNumber) {
        try {
            // 檢查是否為 setXml 交易
            if (strpos($tx['input'], '0x83409caa') === 0) {
                $content = $this->decodeSetXmlInput($tx['input']);

                if ($content) {
                    return [
                        'txHash' => $tx['hash'],
                        'blockNumber' => $blockNumber,
                        'timestamp' => $this->getBlockTimestamp($blockNumber),
                        'content' => $content,
                        'uploader' => $tx['from'],
                        'description' => $this->extractDescription($content),
                        'status' => 'success'
                    ];
                }
            }

            return null;

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * 解碼 setXml 交易的輸入數據
     */
    private function decodeSetXmlInput($input) {
        if (strlen($input) < 138) { // 最小長度檢查
            return null;
        }

        try {
            // 跳過函數選擇器 (4 bytes) 和偏移量 (32 bytes)
            $offset = hexdec(substr($input, 10, 64));
            $length = hexdec(substr($input, 74, 64));

            if ($length > 0 && $length < 10000) { // 合理性檢查
                $contentHex = substr($input, 138, $length * 2);
                return hex2bin($contentHex);
            }

            return null;

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * 獲取區塊時間戳
     */
    private function getBlockTimestamp($blockNumber) {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'eth_getBlockByNumber',
            'params' => ['0x' . dechex($blockNumber), false],
            'id' => time()
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data)
            ]
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($this->rpcUrl, false, $context);

        if ($result !== false) {
            $response = json_decode($result, true);
            if (isset($response['result']) && isset($response['result']['timestamp'])) {
                return hexdec($response['result']['timestamp']);
            }
        }

        return time(); // 如果無法獲取，使用當前時間
    }

    /**
     * 從內容中提取描述
     */
    private function extractDescription($content) {
        // 嘗試解析 JSON 內容
        $data = json_decode($content, true);
        if ($data && isset($data['description'])) {
            return $data['description'];
        }

        // 如果是 JSON 格式，嘗試提取其他欄位
        if ($data) {
            if (isset($data['filename'])) {
                return "檔案: " . $data['filename'];
            }
            if (isset($data['test_type'])) {
                return "測試類型: " . $data['test_type'];
            }
        }

        // 如果內容太長，截斷顯示
        if (strlen($content) > 50) {
            return substr($content, 0, 50) . "...";
        }

        return $content;
    }
}

class DashboardAPI {
    private $ethereumClient;
    private $dataRecordsAPI;

    public function __construct() {
        $this->ethereumClient = new EthereumClient();
        $this->dataRecordsAPI = new DataRecordsAPI();
    }

    /**
     * 獲取 Dashboard 統計數據
     */
    public function getStats() {
        try {
            $stats = [];

            // 1. 數據上鏈數 - 從 data_records_api.php 獲取
            $recordsResult = $this->dataRecordsAPI->getAllDataRecords();
            $stats['totalRecords'] = $recordsResult['success'] ? $recordsResult['total'] : 0;

            // 2. 數據檔上鏈數 - 統計有 IPFS hash 的記錄
            $fileRecords = 0;
            if ($recordsResult['success'] && isset($recordsResult['records'])) {
                foreach ($recordsResult['records'] as $record) {
                    $content = $record['content'];
                    if (is_string($content)) {
                        $decoded = json_decode($content, true);
                        if ($decoded && isset($decoded['ipfs_hash'])) {
                            $fileRecords++;
                        }
                    }
                }
            }
            $stats['fileRecords'] = $fileRecords;

            // 3. IPFS 狀態 - 檢查 IPFS 節點連線
            $stats['ipfsStatus'] = $this->checkIPFSStatus();

            // 4. 區塊鏈狀態 - 檢查 Ethereum 節點連線
            $stats['blockchainStatus'] = $this->checkBlockchainStatus();

            // 5. 區塊鏈區塊數 - 獲取當前區塊高度
            $stats['chainBlockCount'] = $this->ethereumClient->getBlockNumber();

            // 6. IPFS 使用容量 - 目前設為 0（需要 IPFS API 支援）
            $stats['ipfsUsage'] = 0;

            // 7. 成功/失敗比率 - 統計交易狀態
            $successCount = 0;
            $failCount = 0;
            if ($recordsResult['success'] && isset($recordsResult['records'])) {
                foreach ($recordsResult['records'] as $record) {
                    if (isset($record['status'])) {
                        if ($record['status'] === 'success') {
                            $successCount++;
                        } else {
                            $failCount++;
                        }
                    } else {
                        // 預設為成功
                        $successCount++;
                    }
                }
            }
            $stats['successCount'] = $successCount;
            $stats['failCount'] = $failCount;

            // 8. 異常事件分佈 - 目前設為 0
            $stats['abnormalCount'] = 0;

            return $stats;

        } catch (Exception $e) {
            // 返回預設值
            return [
                'totalRecords' => 0,
                'fileRecords' => 0,
                'ipfsStatus' => '異常',
                'blockchainStatus' => '異常',
                'chainBlockCount' => 0,
                'ipfsUsage' => 0,
                'successCount' => 0,
                'failCount' => 0,
                'abnormalCount' => 0
            ];
        }
    }

    /**
     * 獲取 Dashboard 趨勢圖資料
     */
    public function getTrend() {
        try {
            $recordsResult = $this->dataRecordsAPI->getAllDataRecords();

            if (!$recordsResult['success'] || !isset($recordsResult['records'])) {
                return [
                    'labels' => [],
                    'datasets' => [
                        [
                            'label' => '上鏈數據',
                            'data' => [],
                            'borderColor' => '#3949ab',
                            'backgroundColor' => 'rgba(57, 73, 171, 0.1)',
                            'tension' => 0.4
                        ]
                    ]
                ];
            }

            // 按日期分組統計
            $dailyStats = [];
            foreach ($recordsResult['records'] as $record) {
                $date = date('Y-m-d', $record['timestamp']);
                if (!isset($dailyStats[$date])) {
                    $dailyStats[$date] = 0;
                }
                $dailyStats[$date]++;
            }

            // 取最近 7 天的數據
            $labels = [];
            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $labels[] = date('m/d', strtotime($date));
                $data[] = $dailyStats[$date] ?? 0;
            }

            return [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => '上鏈數據',
                        'data' => $data,
                        'borderColor' => '#3949ab',
                        'backgroundColor' => 'rgba(57, 73, 171, 0.1)',
                        'tension' => 0.4
                    ]
                ]
            ];

        } catch (Exception $e) {
            return [
                'labels' => [],
                'datasets' => [
                    [
                        'label' => '上鏈數據',
                        'data' => [],
                        'borderColor' => '#3949ab',
                        'backgroundColor' => 'rgba(57, 73, 171, 0.1)',
                        'tension' => 0.4
                    ]
                ]
            ];
        }
    }

    /**
     * 檢查 IPFS 狀態
     */
    private function checkIPFSStatus() {
        try {
            // 嘗試連接到 IPFS 節點
            $ipfsUrl = 'http://localhost:5001/api/v0/version';
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'method' => 'POST'
                ]
            ]);

            $result = @file_get_contents($ipfsUrl, false, $context);
            return $result !== false ? '正常' : '異常';
        } catch (Exception $e) {
            return '異常';
        }
    }

    /**
     * 檢查區塊鏈狀態
     */
    private function checkBlockchainStatus() {
        try {
            $this->ethereumClient->getBlockNumber();
            return '正常';
        } catch (Exception $e) {
            return '異常';
        }
    }
}

// API 路由處理
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');

    $api = new DashboardAPI();

    // 檢查是否有 action 參數
    if (isset($_GET['action'])) {
        $action = $_GET['action'];

        switch ($action) {
            case 'dashboardStats':
                $stats = $api->getStats();
                echo json_encode($stats, JSON_UNESCAPED_UNICODE);
                exit; // 確保不會繼續執行後面的代碼

            case 'dashboardTrend':
                $trend = $api->getTrend();
                echo json_encode($trend, JSON_UNESCAPED_UNICODE);
                exit; // 確保不會繼續執行後面的代碼

            default:
                http_response_code(404);
                echo json_encode(['error' => 'API 端點不存在']);
                exit; // 確保不會繼續執行後面的代碼
        }
    } else {
        // 檢查 URL 路徑
        $path = $_SERVER['REQUEST_URI'];

        if (strpos($path, '/api/dashboardStats') !== false || strpos($path, 'dashboardStats') !== false) {
            $stats = $api->getStats();
            echo json_encode($stats, JSON_UNESCAPED_UNICODE);
            exit; // 確保不會繼續執行後面的代碼
        } elseif (strpos($path, '/api/dashboardTrend') !== false || strpos($path, 'dashboardTrend') !== false) {
            $trend = $api->getTrend();
            echo json_encode($trend, JSON_UNESCAPED_UNICODE);
            exit; // 確保不會繼續執行後面的代碼
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'API 端點不存在']);
            exit; // 確保不會繼續執行後面的代碼
        }
    }
} else {
    // 顯示 API 使用說明
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Dashboard API</title>
        <meta charset="utf-8">
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .endpoint { background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 5px; }
            code { background: #e0e0e0; padding: 2px 4px; border-radius: 3px; }
        </style>
    </head>
    <body>
        <h1>📊 Dashboard API</h1>
        <p>提供 Dashboard 頁面所需的統計數據和趨勢圖資料</p>

        <h2>API 端點</h2>

        <div class="endpoint">
            <h3>獲取統計數據</h3>
            <p><code>GET /api/dashboardStats</code></p>
            <p>返回 Dashboard 頁面所需的統計數據。</p>
        </div>

        <div class="endpoint">
            <h3>獲取趨勢圖資料</h3>
            <p><code>GET /api/dashboardTrend</code></p>
            <p>返回趨勢圖所需的數據。</p>
        </div>

        <h2>測試連結</h2>
        <p><a href="/api/dashboardStats" target="_blank">查看統計數據</a></p>
        <p><a href="/api/dashboardTrend" target="_blank">查看趨勢圖資料</a></p>
    </body>
    </html>
    <?php
}
?>
