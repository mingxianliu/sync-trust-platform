<?php
// 本地测试 ethereum_api.php 的功能
require_once 'ethereum_api.php';

echo "🧪 测试 Ethereum API 功能\n";
echo "========================\n\n";

try {
    $api = new EthereumAPI();

    echo "1️⃣ 测试基本连接...\n";
    $blockNumber = $api->getBlockNumber();
    echo "   ✅ 当前区块号: $blockNumber\n";

    echo "\n2️⃣ 测试获取 XML...\n";
    $xml = $api->getXml();
    if ($xml) {
        echo "   ✅ 获取到 XML 数据\n";
        echo "   📄 内容预览: " . substr($xml, 0, 100) . "...\n";
    } else {
        echo "   ℹ️  暂无 XML 数据\n";
    }

    echo "\n3️⃣ 测试获取所有记录...\n";
    $records = $api->getAllDataRecords();
    echo "   ✅ 获取到 " . count($records) . " 条记录\n";

    if (count($records) > 0) {
        echo "   📋 最新记录:\n";
        $latest = $records[0];
        echo "      - 交易哈希: " . substr($latest['txHash'], 0, 20) . "...\n";
        echo "      - 区块号: " . $latest['blockNumber'] . "\n";
        echo "      - 描述: " . $latest['description'] . "\n";
        echo "      - 时间: " . date('Y-m-d H:i:s', $latest['timestamp']) . "\n";
    }

    echo "\n🎉 所有测试通过！\n";

} catch (Exception $e) {
    echo "❌ 测试失败: " . $e->getMessage() . "\n";
}
?>
