<?php
/**
 * 測試 Web 上傳功能的腳本
 */

echo "=== 測試 Web 上傳功能 ===\n";

// 創建測試文件
$testContent = "這是一個 Web 上傳測試文件\n";
$testContent .= "創建時間: " . date('Y-m-d H:i:s') . "\n";
$testContent .= "內容: 用於測試 Web 界面的文件上傳功能\n";

$testFile = 'web_test_' . time() . '.txt';
file_put_contents($testFile, $testContent);

echo "✅ 創建測試文件: " . $testFile . "\n";
echo "   文件大小: " . strlen($testContent) . " 字節\n";

// 模擬文件上傳
$_FILES['file'] = [
    'name' => $testFile,
    'type' => 'text/plain',
    'tmp_name' => $testFile,
    'error' => UPLOAD_ERR_OK,
    'size' => strlen($testContent)
];

// 包含並測試上傳功能
require_once 'upload_test.php';

$uploadTest = new WebUploadTest();
$result = $uploadTest->handleUpload();

if ($result && isset($result['success'])) {
    echo "\n🎉 Web 上傳測試成功！\n";
    echo "   文件名: " . $result['filename'] . "\n";
    echo "   IPFS Hash: " . $result['ipfs_hash'] . "\n";
    echo "   Ethereum 交易: " . $result['tx_hash'] . "\n";
} else {
    echo "\n❌ Web 上傳測試失敗\n";
    if (isset($result['error'])) {
        echo "   錯誤: " . $result['error'] . "\n";
    }
}

// 清理測試文件
unlink($testFile);

echo "\n=== 測試完成 ===\n";
?>
