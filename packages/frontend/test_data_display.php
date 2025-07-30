<?php
/**
 * 測試數據顯示問題
 */

// 模擬您發送的JSON數據
$testData = [
    "building_id" => "TW-BLDG-001",
    "timestamp" => "2025-07-07 12:00:00",
    "sensor_data" => [
        "environment" => [
            "temperature" => 25.3,
            "humidity" => 65.2,
            "co2" => 420,
            "pm25" => 10.5
        ],
        "energy" => [
            "total_power_consumption" => 1250.5,
            "solar_panel_output" => 320.8,
            "battery_level" => 85.2,
            "peak_demand" => 1800.0
        ],
        "access_control" => [
            [
                "card_number" => "AC001234",
                "user_name" => "張小明",
                "access_time" => "2025-07-07 08:30:15",
                "direction" => "entry",
                "location" => "主入口"
            ],
            [
                "card_number" => "AC005678",
                "user_name" => "李小華",
                "access_time" => "2025-07-07 09:15:42",
                "direction" => "exit",
                "location" => "側門"
            ]
        ]
    ],
    "uploader" => "synckeyadmin",
    "description" => "智慧建築環境監控數據上鏈測試"
];

echo "<h1>測試數據顯示問題</h1>";

echo "<h2>原始JSON數據：</h2>";
echo "<pre>" . json_encode($testData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

// 模擬後端API的extractDescription函數
function extractDescription($content) {
    $data = json_decode($content, true);
    if ($data && isset($data['description'])) {
        return $data['description'];
    }

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

    if (strlen($content) > 50) {
        return substr($content, 0, 50) . "...";
    }

    return $content;
}

$jsonContent = json_encode($testData, JSON_UNESCAPED_UNICODE);
$description = extractDescription($jsonContent);

echo "<h2>提取的描述：</h2>";
echo "<p><strong>描述：</strong> " . htmlspecialchars($description) . "</p>";

echo "<h2>模擬API響應：</h2>";
$apiResponse = [
    'success' => true,
    'records' => [
        [
            'txHash' => '0x1234567890abcdef',
            'blockNumber' => 12345,
            'timestamp' => time(),
            'content' => $jsonContent,
            'uploader' => '0x5D640Ba7Ed0755f44331544598e8F6EA3662baD0',
            'description' => $description,
            'status' => 'success'
        ]
    ],
    'total' => 1
];

echo "<pre>" . json_encode($apiResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "<h2>前端解析後的內容：</h2>";
$parsedContent = json_decode($jsonContent, true);
echo "<pre>" . json_encode($parsedContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "<h2>問題分析：</h2>";
echo "<ul>";
echo "<li>原始JSON包含完整的sensor_data結構</li>";
echo "<li>後端API正確提取了description</li>";
echo "<li>前端應該能夠正確解析和顯示完整的JSON結構</li>";
echo "<li>修改後的代碼應該能夠更好地格式化複雜的JSON對象</li>";
echo "</ul>";
?>
