<?php
/**
 * 數據記錄查詢 API
 * 通過查詢交易歷史來獲取所有上鏈的數據記錄
 */

// 設定 CORS 標頭
header('Access-Control-Allow-Origin: https://synckeytech.winshare.tw');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// 處理 OPTIONS 預檢請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'ethereum_php_client.php';

class DataRecordsAPI {
    private $ethereumClient;
    private $contractAddress;
    private $rpcUrl;

    public function __construct() {
        $this->ethereumClient = new EthereumClient();
        $this->contractAddress = $this->ethereumClient->getContractAddress();
        $this->rpcUrl = 'http://localhost:8545';
    }

    /**
     * 獲取所有數據記錄
     */
    public function getAllDataRecords() {
        try {
            // 獲取當前區塊號
            $currentBlock = $this->ethereumClient->getBlockNumber();

            // 查詢所有歷史記錄，從區塊0開始
            $startBlock = 0;

            $records = [];
            $processedBlocks = 0;
            $foundTransactions = 0;

            // 查詢每個區塊的交易
            for ($blockNumber = $startBlock; $blockNumber <= $currentBlock; $blockNumber++) {
                $blockTransactions = $this->getBlockTransactions($blockNumber);
                $processedBlocks++;

                foreach ($blockTransactions as $tx) {
                    if ($this->isContractTransaction($tx)) {
                        $foundTransactions++;
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

            return [
                'success' => true,
                'records' => $records,
                'total' => count($records),
                'debug' => [
                    'currentBlock' => $currentBlock,
                    'startBlock' => $startBlock,
                    'processedBlocks' => $processedBlocks,
                    'foundTransactions' => $foundTransactions,
                    'contractAddress' => $this->contractAddress
                ]
            ];

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
            // 只要是發到合約的交易都列出
            $content = null;
            $method = substr($tx['input'], 0, 10);

            // 嘗試解碼 setXml
            if ($method === '0x83409caa') {
                $content = $this->decodeSetXmlInput($tx['input']);
            } else {
                // 其他方法，僅顯示 input 前幾位
                $content = json_encode([
                    'method' => $method,
                    'input_preview' => substr($tx['input'], 0, 32) . '...'
                ], JSON_UNESCAPED_UNICODE);
            }

            return [
                'txHash' => $tx['hash'],
                'blockNumber' => $blockNumber,
                'timestamp' => $this->getBlockTimestamp($blockNumber),
                'content' => $content,
                'uploader' => $tx['from'],
                'description' => $this->extractDescription($content),
                'status' => 'success'
            ];
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
            if (isset($response['result']['timestamp'])) {
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
            // 如果有 building_id，也加入描述
            if (isset($data['building_id'])) {
                $desc = "建築ID: " . $data['building_id'];
                if (isset($data['description'])) {
                    $desc .= " - " . $data['description'];
                }
                return $desc;
            }
        }

        // 如果內容太長，截斷顯示
        if (strlen($content) > 50) {
            return substr($content, 0, 50) . "...";
        }

        return $content;
    }

    /**
     * 獲取單筆記錄
     */
    public function getDataRecord($txHash) {
        try {
            $receipt = $this->ethereumClient->getTransactionReceipt($txHash);

            if ($receipt && isset($receipt['blockNumber'])) {
                $blockNumber = hexdec($receipt['blockNumber']);
                $timestamp = $this->getBlockTimestamp($blockNumber);

                // 獲取交易詳情
                $tx = $this->getTransactionByHash($txHash);

                if ($tx && $this->isContractTransaction($tx)) {
                    $record = $this->parseTransaction($tx, $blockNumber);
                    if ($record) {
                        return [
                            'success' => true,
                            'record' => $record
                        ];
                    }
                }
            }

            return [
                'success' => false,
                'error' => '記錄不存在'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 根據交易哈希獲取交易詳情
     */
    private function getTransactionByHash($txHash) {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'eth_getTransactionByHash',
            'params' => [$txHash],
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
            if (isset($response['result'])) {
                return $response['result'];
            }
        }

        return null;
    }
}

// API 端點處理
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');

    $api = new DataRecordsAPI();

    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'getAllRecords':
                $result = $api->getAllDataRecords();
                break;

            case 'getRecord':
                $txHash = $_GET['txHash'] ?? '';
                if ($txHash) {
                    $result = $api->getDataRecord($txHash);
                } else {
                    $result = ['success' => false, 'error' => '缺少交易哈希'];
                }
                break;

            default:
                $result = ['success' => false, 'error' => '未知操作'];
        }
    } else {
        // 預設返回所有記錄
        $result = $api->getAllDataRecords();
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} else {
    // 顯示 API 使用說明
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>數據記錄查詢 API</title>
        <meta charset="utf-8">
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .endpoint { background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 5px; }
            code { background: #e0e0e0; padding: 2px 4px; border-radius: 3px; }
        </style>
    </head>
    <body>
        <h1>📊 數據記錄查詢 API</h1>
        <p>合約地址: <?php echo (new DataRecordsAPI())->contractAddress ?? '未設置'; ?></p>

        <h2>API 端點</h2>

        <div class="endpoint">
            <h3>獲取所有數據記錄</h3>
            <p><code>GET /data_records_api.php?action=getAllRecords</code></p>
            <p>返回所有上鏈的數據記錄，按時間倒序排列。</p>
        </div>

        <div class="endpoint">
            <h3>獲取單筆記錄</h3>
            <p><code>GET /data_records_api.php?action=getRecord&txHash=0x...</code></p>
            <p>根據交易哈希獲取特定記錄。</p>
        </div>

        <div class="endpoint">
            <h3>預設查詢</h3>
            <p><code>GET /data_records_api.php</code></p>
            <p>預設返回所有記錄。</p>
        </div>

        <h2>返回格式</h2>
        <pre>
{
  "success": true,
  "records": [
    {
      "txHash": "0x...",
      "blockNumber": 1234,
      "timestamp": 1640995200,
      "content": "JSON 或字串內容",
      "uploader": "0x...",
      "description": "描述",
      "status": "success"
    }
  ],
  "total": 5
}
        </pre>

        <h2>測試連結</h2>
        <p><a href="?action=getAllRecords" target="_blank">查看所有記錄</a></p>
    </body>
    </html>
    <?php
}
?>
