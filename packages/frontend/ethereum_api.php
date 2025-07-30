<?php
/**
 * Ethereum API Client for PHP
 * 用於與智能合約互動的 PHP 客戶端
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

class EthereumAPI {
    private $rpcUrl;
    private $contractAddress;
    private $contractABI;
    private $accountAddress;

    public function __construct($rpcUrl = 'http://localhost:8545', $contractAddress = null, $accountAddress = null) {
        $this->rpcUrl = $rpcUrl;
        $this->contractAddress = $contractAddress;
        $this->accountAddress = $accountAddress;

        // 載入合約信息
        if (file_exists('/root/contract_info.json')) {
            $contractInfo = json_decode(file_get_contents('/root/contract_info.json'), true);
            $this->contractAddress = $contractAddress ?: $contractInfo['address'];
            $this->contractABI = $contractInfo['abi'];
        }
    }

    /**
     * 發送 JSON-RPC 請求
     */
    private function sendRequest($method, $params = []) {
        $data = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
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
            throw new Exception('無法連接到 Ethereum 節點');
        }

        $response = json_decode($result, true);

        if (isset($response['error'])) {
            throw new Exception('RPC 錯誤: ' . $response['error']['message']);
        }

        return $response['result'];
    }

    /**
     * 獲取帳戶餘額
     */
    public function getBalance($address = null) {
        $address = $address ?: $this->accountAddress;
        $result = $this->sendRequest('eth_getBalance', [$address, 'latest']);
        return hexdec($result) / pow(10, 18); // 轉換為 ETH
    }

    /**
     * 獲取區塊數量
     */
    public function getBlockNumber() {
        $result = $this->sendRequest('eth_blockNumber');
        return hexdec($result);
    }

    /**
     * 獲取帳戶列表
     */
    public function getAccounts() {
        return $this->sendRequest('eth_accounts');
    }

    /**
     * 編碼函數調用
     */
    private function encodeFunctionCall($functionName, $params = []) {
        // 簡化的 ABI 編碼（實際應用中應使用完整的 ABI 編碼庫）
        $functionSignature = $functionName . '(' . str_repeat('string,', count($params) - 1) . 'string)';
        $functionSelector = substr(hash('sha3-256', $functionSignature), 0, 10);

        $encodedParams = '';
        foreach ($params as $param) {
            $encodedParams .= str_pad(dechex(strlen($param)), 64, '0', STR_PAD_LEFT);
            $encodedParams .= str_pad(bin2hex($param), 64, '0', STR_PAD_RIGHT);
        }

        return '0x' . $functionSelector . $encodedParams;
    }

    /**
     * 調用合約函數（讀取）
     */
    public function callContract($functionName, $params = []) {
        $data = $this->encodeFunctionCall($functionName, $params);

        $result = $this->sendRequest('eth_call', [[
            'to' => $this->contractAddress,
            'data' => $data
        ], 'latest']);

        // 簡化的解碼（實際應用中應使用完整的 ABI 解碼庫）
        return $this->decodeString($result);
    }

    /**
     * 發送合約交易（寫入）
     */
    public function sendTransaction($functionName, $params = []) {
        $data = $this->encodeFunctionCall($functionName, $params);

        $result = $this->sendRequest('eth_sendTransaction', [[
            'from' => $this->accountAddress,
            'to' => $this->contractAddress,
            'data' => $data,
            'gas' => '0x' . dechex(200000),
            'gasPrice' => '0x' . dechex(20000000000) // 20 Gwei
        ]]);

        return $result;
    }

    /**
     * 獲取 XML 內容
     */
    public function getXml() {
        return $this->callContract('getXml', []);
    }

    /**
     * 設置 XML 內容
     */
    public function setXml($xml) {
        return $this->sendTransaction('setXml', [$xml]);
    }

    /**
     * 簽名 XML
     */
    public function signXml($xml) {
        return $this->sendTransaction('signXml', [$xml]);
    }

    /**
     * 簡化的字符串解碼
     */
    private function decodeString($hexData) {
        if (strlen($hexData) < 66) return '';

        $length = hexdec(substr($hexData, 2, 64));
        $data = substr($hexData, 66, $length * 2);

        return hex2bin($data);
    }

    /**
     * 獲取交易收據
     */
    public function getTransactionReceipt($txHash) {
        return $this->sendRequest('eth_getTransactionReceipt', [$txHash]);
    }

    /**
     * 等待交易確認
     */
    public function waitForTransaction($txHash, $maxAttempts = 30) {
        for ($i = 0; $i < $maxAttempts; $i++) {
            $receipt = $this->getTransactionReceipt($txHash);
            if ($receipt && $receipt['blockNumber'] !== null) {
                return $receipt;
            }
            sleep(2);
        }
        throw new Exception('交易確認超時');
    }

    /**
     * 獲取所有數據記錄
     */
    public function getAllDataRecords() {
        try {
            // 獲取當前區塊號
            $currentBlock = $this->getBlockNumber();

            // 從最近的 1000 個區塊開始查詢（可以調整）
            $startBlock = max(0, $currentBlock - 1000);

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

            return $records;

        } catch (Exception $e) {
            throw new Exception('獲取數據記錄失敗: ' . $e->getMessage());
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
            $receipt = $this->getTransactionReceipt($txHash);

            if ($receipt && isset($receipt['blockNumber'])) {
                $blockNumber = hexdec($receipt['blockNumber']);
                $timestamp = $this->getBlockTimestamp($blockNumber);

                // 獲取交易詳情
                $tx = $this->getTransactionByHash($txHash);

                if ($tx && $this->isContractTransaction($tx)) {
                    $record = $this->parseTransaction($tx, $blockNumber);
                    if ($record) {
                        return $record;
                    }
                }
            }

            throw new Exception('記錄不存在');

        } catch (Exception $e) {
            throw new Exception('獲取記錄失敗: ' . $e->getMessage());
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        $api = new EthereumAPI();

        switch ($action) {
            case 'getBalance':
                $address = $input['address'] ?? null;
                $balance = $api->getBalance($address);
                echo json_encode(['success' => true, 'balance' => $balance]);
                break;

            case 'getBlockNumber':
                $blockNumber = $api->getBlockNumber();
                echo json_encode(['success' => true, 'blockNumber' => $blockNumber]);
                break;

            case 'getXml':
                $xml = $api->getXml();
                echo json_encode(['success' => true, 'xml' => $xml]);
                break;

            case 'setXml':
                $xml = $input['xml'] ?? '';
                $txHash = $api->setXml($xml);
                echo json_encode(['success' => true, 'txHash' => $txHash]);
                break;

            case 'signXml':
                $xml = $input['xml'] ?? '';
                $txHash = $api->signXml($xml);
                echo json_encode(['success' => true, 'txHash' => $txHash]);
                break;

            case 'getTransactionReceipt':
                $txHash = $input['txHash'] ?? '';
                $receipt = $api->getTransactionReceipt($txHash);
                echo json_encode(['success' => true, 'receipt' => $receipt]);
                break;

            case 'getAllRecords':
                // 獲取所有數據記錄
                $records = $api->getAllDataRecords();
                echo json_encode(['success' => true, 'data' => $records]);
                break;

            case 'getDataRecord':
                $txHash = $input['txHash'] ?? '';
                $record = $api->getDataRecord($txHash);
                echo json_encode(['success' => true, 'record' => $record]);
                break;

            default:
                echo json_encode(['success' => false, 'error' => '未知的操作']);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    // 顯示 API 使用說明
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Ethereum API</title>
        <meta charset="utf-8">
    </head>
    <body>
        <h1>Ethereum API 使用說明</h1>
        <p>合約地址: <?php echo (new EthereumAPI())->contractAddress ?? '未設置'; ?></p>

        <h2>API 端點</h2>
        <p>POST /ethereum_api.php</p>

        <h3>可用操作：</h3>
        <ul>
            <li><strong>getBalance</strong> - 獲取帳戶餘額</li>
            <li><strong>getBlockNumber</strong> - 獲取當前區塊號</li>
            <li><strong>getXml</strong> - 從合約讀取 XML</li>
            <li><strong>setXml</strong> - 設置 XML 到合約</li>
            <li><strong>signXml</strong> - 簽名 XML</li>
            <li><strong>getTransactionReceipt</strong> - 獲取交易收據</li>
            <li><strong>getAllRecords</strong> - 獲取所有數據記錄</li>
            <li><strong>getDataRecord</strong> - 獲取單筆數據記錄</li>
        </ul>

        <h3>使用範例：</h3>
        <pre>
// 獲取 XML
curl -X POST http://localhost/ethereum_api.php \
  -H "Content-Type: application/json" \
  -d '{"action": "getXml"}'

// 設置 XML
curl -X POST http://localhost/ethereum_api.php \
  -H "Content-Type: application/json" \
  -d '{"action": "setXml", "xml": "&lt;test&gt;Hello World&lt;/test&gt;"}'
        </pre>
    </body>
    </html>
    <?php
}
?>
