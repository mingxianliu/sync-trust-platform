<?php
require_once 'ethereum_php_client.php';

try {
    echo "=== Ethereum 調試測試 ===\n";

    $client = new EthereumClient();

    // 直接調用 RPC 並查看原始返回數據
    $data = '0x1ad4bb0d'; // getXml() 的函數選擇器

    $data_array = [
        'jsonrpc' => '2.0',
        'method' => 'eth_call',
        'params' => [[
            'to' => $client->getContractAddress(),
            'data' => $data
        ], 'latest'],
        'id' => time()
    ];

    $options = [
        'http' => [
            'header' => "Content-type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($data_array)
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents('http://localhost:8545', false, $context);

    echo "原始 RPC 返回: " . $result . "\n";

    $response = json_decode($result, true);

    if (isset($response['result'])) {
        echo "結果長度: " . strlen($response['result']) . "\n";
        echo "結果內容: " . $response['result'] . "\n";
    }

} catch (Exception $e) {
    echo "錯誤: " . $e->getMessage() . "\n";
}
?>
