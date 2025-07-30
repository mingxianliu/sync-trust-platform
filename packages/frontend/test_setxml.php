<?php
echo "=== 測試 setXml 函數 ===\n";

$contractAddress = '0x0b069012e44D4eA8Cc122045d649A202891E09FA';
$accountAddress = '0x5D640Ba7Ed0755f44331544598e8F6EA3662baD0';

// 測試 setXml 函數
echo "1. 測試 setXml 函數...\n";
$testXml = "<php_test>Hello from PHP setXml</php_test>";

// 構建 setXml 調用數據
$data = '0x83409caa'; // setXml(string) 的函數選擇器

// 簡化的參數編碼
$length = strlen($testXml);
$data .= str_pad(dechex($length), 64, '0', STR_PAD_LEFT);
$data .= str_pad(bin2hex($testXml), 64, '0', STR_PAD_RIGHT);

echo "   要設置的 XML: " . $testXml . "\n";
echo "   編碼後的數據: " . $data . "\n";

// 發送交易
$txData = [
    'jsonrpc' => '2.0',
    'method' => 'eth_sendTransaction',
    'params' => [[
        'from' => $accountAddress,
        'to' => $contractAddress,
        'data' => $data,
        'gas' => '0x' . dechex(200000),
        'gasPrice' => '0x' . dechex(20000000000) // 20 Gwei
    ]],
    'id' => 1
];

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($txData)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents('http://localhost:8545', false, $context);

echo "   交易結果: " . $result . "\n";

$response = json_decode($result, true);
if (isset($response['result'])) {
    echo "   交易哈希: " . $response['result'] . "\n";
    echo "   等待交易確認...\n";

    // 等待幾個區塊
    sleep(5);

    // 測試讀取更新後的 XML
    echo "\n2. 讀取更新後的 XML...\n";
    $readData = [
        'jsonrpc' => '2.0',
        'method' => 'eth_call',
        'params' => [[
            'to' => $contractAddress,
            'data' => '0x7b6faad1'
        ], 'latest'],
        'id' => 2
    ];

    $options = [
        'http' => [
            'header' => "Content-type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($readData)
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents('http://localhost:8545', false, $context);

    echo "   讀取結果: " . $result . "\n";

    $response = json_decode($result, true);
    if (isset($response['result'])) {
        $result = $response['result'];
        if (strlen($result) > 66) {
            $offset = hexdec(substr($result, 2, 64));
            $length = hexdec(substr($result, 66, 64));

            if ($length > 0) {
                $stringStart = 130;
                $stringHex = substr($result, $stringStart, $length * 2);
                $decoded = hex2bin($stringHex);
                echo "   解碼結果: " . $decoded . "\n";
            }
        }
    }
}

echo "\n=== 測試完成 ===\n";
?>
