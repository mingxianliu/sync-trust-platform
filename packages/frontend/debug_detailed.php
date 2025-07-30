<?php
echo "=== 詳細調試測試 ===\n";

$contractAddress = '0x0b069012e44D4eA8Cc122045d649A202891E09FA';

// 測試 1: 基本連接
echo "1. 測試基本連接...\n";
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
echo "   結果: " . $result . "\n";

// 測試 2: 合約代碼檢查
echo "\n2. 檢查合約代碼...\n";
$data = [
    'jsonrpc' => '2.0',
    'method' => 'eth_getCode',
    'params' => [$contractAddress, 'latest'],
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
$response = json_decode($result, true);
echo "   結果: " . $result . "\n";
if (isset($response['result'])) {
    echo "   代碼長度: " . (strlen($response['result']) - 2) / 2 . " 字節\n";
}

// 測試 3: 使用正確的選擇器調用 getXml
echo "\n3. 調用 getXml (選擇器: 0x7b6faad1)...\n";
$data = [
    'jsonrpc' => '2.0',
    'method' => 'eth_call',
    'params' => [[
        'to' => $contractAddress,
        'data' => '0x7b6faad1'
    ], 'latest'],
    'id' => 3
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
echo "   結果: " . $result . "\n";

$response = json_decode($result, true);
if (isset($response['result'])) {
    echo "   結果長度: " . strlen($response['result']) . "\n";
    echo "   結果內容: " . $response['result'] . "\n";

    // 嘗試解碼
    if (strlen($response['result']) > 66) {
        $offset = hexdec(substr($response['result'], 2, 64));
        $length = hexdec(substr($response['result'], 66, 64));
        echo "   偏移量: " . $offset . "\n";
        echo "   長度: " . $length . "\n";

        if ($length > 0) {
            $stringHex = substr($response['result'], 130, $length * 2);
            $decoded = hex2bin($stringHex);
            echo "   解碼結果: " . $decoded . "\n";
        }
    }
}

echo "\n=== 調試完成 ===\n";
?>
