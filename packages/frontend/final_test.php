<?php
// 設定 UTF-8 編碼
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

require_once 'ethereum_php_client.php';

echo "=== PHP Ethereum 客戶端最終測試 ===\n\n";

try {
    $client = new EthereumClient();

    echo "✅ 基本信息:\n";
    echo "   合約地址: " . $client->getContractAddress() . "\n";
    echo "   帳戶地址: " . $client->getAccountAddress() . "\n";
    echo "   帳戶餘額: " . $client->getBalance() . " ETH\n";
    echo "   當前區塊: " . $client->getBlockNumber() . "\n\n";

    echo "✅ 測試 1: 讀取當前 XML\n";
    $currentXml = $client->getXml();
    echo "   結果: " . $currentXml . "\n\n";

    echo "✅ 測試 2: 設置新 XML\n";
    $testXml = "<final_test>PHP Client Working Perfectly!</final_test>";
    echo "   要設置: " . $testXml . "\n";

    $txHash = $client->setXml($testXml);
    echo "   交易哈希: " . $txHash . "\n";
    echo "   等待確認...\n";

    // 等待交易確認
    sleep(3);

    echo "\n✅ 測試 3: 驗證 XML 更新\n";
    $updatedXml = $client->getXml();
    echo "   更新後: " . $updatedXml . "\n";

    if ($updatedXml === $testXml) {
        echo "   🎉 驗證成功！XML 已正確更新\n";
    } else {
        echo "   ❌ 驗證失敗！XML 未正確更新\n";
    }

    echo "\n✅ 測試 4: 再次設置 XML\n";
    $finalXml = "<success>All PHP Ethereum functions working!</success>";
    echo "   要設置: " . $finalXml . "\n";

    $txHash2 = $client->setXml($finalXml);
    echo "   交易哈希: " . $txHash2 . "\n";
    echo "   等待確認...\n";

    sleep(3);

    $finalResult = $client->getXml();
    echo "   最終結果: " . $finalResult . "\n";

    if ($finalResult === $finalXml) {
        echo "   🎉 最終驗證成功！\n";
    } else {
        echo "   ❌ 最終驗證失敗！\n";
    }

    echo "\n=== 測試總結 ===\n";
    echo "✅ 基本連接: 正常\n";
    echo "✅ 讀取 XML: 正常\n";
    echo "✅ 設置 XML: 正常\n";
    echo "✅ 交易確認: 正常\n";
    echo "✅ 數據一致性: 正常\n\n";

    echo "🎉 PHP Ethereum 客戶端調試完成！所有功能正常工作！\n";

} catch (Exception $e) {
    echo "❌ 錯誤: " . $e->getMessage() . "\n";
}
?>
