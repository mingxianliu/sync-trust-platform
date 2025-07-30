<?php
echo "=== 簡單 RPC 測試 ===\n";

// 測試基本連接
$data = [
    'jsonrpc' => '2.0',
    'method' => 'eth_blockNumber',
    'params' => [],
    'id' => 1
];

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents('http://localhost:8545', false, $context);

echo "區塊號測試: " . $result . "\n";

// 測試合約調用
$contractAddress = '0x0b069012e44D4eA8Cc122045d649A202891E09FA';
$data = [
    'jsonrpc' => '2.0',
    'method' => 'eth_call',
    'params' => [[
        'to' => $contractAddress,
        'data' => '0x1ad4bb0d'
    ], 'latest'],
    'id' => 2
];

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents('http://localhost:8545', false, $context);

echo "合約調用測試: " . $result . "\n";

$response = json_decode($result, true);
if (isset($response['result'])) {
    echo "結果長度: " . strlen($response['result']) . "\n";
    echo "結果內容: " . $response['result'] . "\n";

    // 嘗試解碼
    if (strlen($response['result']) > 66) {
        $offset = hexdec(substr($response['result'], 2, 64));
        $length = hexdec(substr($response['result'], 66, 64));
        echo "偏移量: " . $offset . "\n";
        echo "長度: " . $length . "\n";

        if ($length > 0) {
            $stringHex = substr($response['result'], 130, $length * 2);
            $decoded = hex2bin($stringHex);
            echo "解碼結果: " . $decoded . "\n";
        }
    }
}
?>
