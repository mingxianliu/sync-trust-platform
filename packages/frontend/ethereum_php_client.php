<?php
/**
 * 簡化的 PHP Ethereum 客戶端
 * 用於與智能合約互動
 */

class EthereumClient {
    private $rpcUrl;
    private $contractAddress;
    private $accountAddress;

    public function __construct($rpcUrl = 'http://localhost:8545') {
        $this->rpcUrl = $rpcUrl;

        // 載入合約信息
        $contractInfoPath = __DIR__ . '/contract_info.json';
        if (file_exists($contractInfoPath)) {
            $contractInfo = json_decode(file_get_contents($contractInfoPath), true);
            $this->contractAddress = $contractInfo['address'];
            $this->accountAddress = '0x5D640Ba7Ed0755f44331544598e8F6EA3662baD0';
        }
    }

    /**
     * 獲取合約地址
     */
    public function getContractAddress() {
        return $this->contractAddress;
    }

    /**
     * 獲取帳戶地址
     */
    public function getAccountAddress() {
        return $this->accountAddress;
    }

    /**
     * 發送 JSON-RPC 請求
     */
    private function sendRequest($method, $params = []) {
        // 強制將所有地址參數轉為字串
        if (in_array($method, ['eth_getBalance', 'eth_getTransactionCount', 'eth_call', 'eth_sendTransaction'])) {
            foreach ($params as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $kk => $vv) {
                        if (in_array($kk, ['from', 'to']) && !is_string($vv)) {
                            $params[$k][$kk] = (string)$vv;
                        }
                    }
                } else if ($k === 0 && is_string($v) && strpos($v, '0x') !== 0) {
                    $params[$k] = '0x' . ltrim($v, '0x');
                } else if ($k === 0 && !is_string($v)) {
                    $params[$k] = (string)$v;
                }
            }
        }
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
        $address = (string)$address; // 強制轉字串
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
     * 獲取 XML 內容（修復版本）
     */
    public function getXml() {
        // 使用簡單的 eth_call 來讀取合約狀態
        $data = '0x7b6faad1'; // getXml() 的函數選擇器

        $result = $this->sendRequest('eth_call', [[
            'to' => $this->contractAddress,
            'data' => $data
        ], 'latest']);

        // 修復解碼邏輯 - 根據調試結果調整
        if (strlen($result) > 66) {
            // 跳過前32字節的偏移量
            $offset = hexdec(substr($result, 2, 64));
            $length = hexdec(substr($result, 66, 64));

            if ($length > 0) {
                // 計算字符串數據的起始位置
                $stringStart = 130; // 2 + 64 + 64
                $stringHex = substr($result, $stringStart, $length * 2);
                return hex2bin($stringHex);
            }
        }

        return '';
    }

    /**
     * 設置 XML 內容（修復版本）
     */
    public function setXml($xml) {
        // 使用簡單的 eth_sendTransaction
        $data = '0x83409caa'; // setXml(string) 的函數選擇器

        // 修復參數編碼 - 與 Python web3.py 一致
        $length = strlen($xml);

        // 添加字符串偏移量（32字節）
        $data .= str_pad(dechex(32), 64, '0', STR_PAD_LEFT);

        // 添加字符串長度
        $data .= str_pad(dechex($length), 64, '0', STR_PAD_LEFT);

        // 添加字符串內容（32字節對齊）
        $xmlHex = bin2hex($xml);
        $data .= str_pad($xmlHex, 64, '0', STR_PAD_RIGHT);

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
     * 獲取交易收據
     */
    public function getTransactionReceipt($txHash) {
        return $this->sendRequest('eth_getTransactionReceipt', [$txHash]);
    }
}

// 測試 API
if (isset($_GET['test'])) {
    try {
        $client = new EthereumClient();

        echo "<h2>Ethereum 客戶端測試</h2>";
        echo "<p><strong>合約地址:</strong> " . $client->getContractAddress() . "</p>";
        echo "<p><strong>帳戶地址:</strong> " . $client->getAccountAddress() . "</p>";
        echo "<p><strong>帳戶餘額:</strong> " . $client->getBalance() . " ETH</p>";
        echo "<p><strong>當前區塊:</strong> " . $client->getBlockNumber() . "</p>";
        echo "<p><strong>當前 XML:</strong> " . htmlspecialchars($client->getXml()) . "</p>";

        if (isset($_POST['set_xml'])) {
            $xml = $_POST['xml'] ?? '';
            $txHash = $client->setXml($xml);
            echo "<p><strong>設置 XML 交易哈希:</strong> " . $txHash . "</p>";
        }

    } catch (Exception $e) {
        echo "<p><strong>錯誤:</strong> " . $e->getMessage() . "</p>";
    }

    // 顯示測試表單
    ?>
    <form method="post">
        <h3>設置 XML</h3>
        <textarea name="xml" rows="4" cols="50" placeholder="輸入 XML 內容"></textarea><br>
        <input type="submit" name="set_xml" value="設置 XML">
    </form>
    <?php
    exit;
}
?>
