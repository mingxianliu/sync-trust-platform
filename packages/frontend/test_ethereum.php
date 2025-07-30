<?php
require_once 'ethereum_php_client.php';

try {
    echo "=== Ethereum PHP 客戶端完整測試 ===\n";

    $client = new EthereumClient();

    echo "合約地址: " . $client->getContractAddress() . "\n";
    echo "帳戶地址: " . $client->getAccountAddress() . "\n";
    echo "帳戶餘額: " . $client->getBalance() . " ETH\n";
    echo "當前區塊: " . $client->getBlockNumber() . "\n";

    echo "\n=== 合約互動測試 ===\n";

    // 測試讀取 XML
    echo "1. 讀取當前 XML...\n";
    $currentXml = $client->getXml();
    echo "   當前 XML: " . ($currentXml ?: '空') . "\n";

    // 測試設置 XML
    echo "\n2. 設置新 XML...\n";
    $newXml = "<php_test>Hello from PHP Client</php_test>";
    echo "   新 XML: " . $newXml . "\n";

    try {
        $txHash = $client->setXml($newXml);
        echo "   交易哈希: " . $txHash . "\n";
        echo "   等待交易確認...\n";

        // 等待幾個區塊
        sleep(5);

        // 再次讀取 XML
        echo "\n3. 讀取更新後的 XML...\n";
        $updatedXml = $client->getXml();
        echo "   更新後的 XML: " . ($updatedXml ?: '空') . "\n";

    } catch (Exception $e) {
        echo "   設置 XML 錯誤: " . $e->getMessage() . "\n";
    }

    echo "\n=== 測試完成 ===\n";

} catch (Exception $e) {
    echo "錯誤: " . $e->getMessage() . "\n";
}
?>
