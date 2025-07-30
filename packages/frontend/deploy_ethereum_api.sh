#!/bin/bash

# 部署更新后的 ethereum_api.php 文件
echo "🚀 部署更新后的 ethereum_api.php"
echo "================================"

SERVER="synckeytech.winshare.tw"

# 检查文件是否存在
if [ ! -f "ethereum_api.php" ]; then
    echo "❌ ethereum_api.php 文件不存在"
    exit 1
fi

echo "✅ 文件存在，准备上传..."

# 使用 curl 上传文件（如果服务器支持）
echo "📤 尝试上传文件..."

# 由于无法直接通过 SSH 上传，我们创建一个测试脚本来验证功能
echo "🧪 创建本地测试脚本..."

cat > test_ethereum_api_local.php << 'EOF'
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
EOF

echo "✅ 本地测试脚本创建完成"
echo ""
echo "📋 部署说明："
echo "1. 将 ethereum_api.php 文件上传到服务器"
echo "2. 确保文件权限正确 (644)"
echo "3. 测试 API 功能"
echo ""
echo "🔗 测试链接："
echo "   • 本地测试: http://localhost/test_ethereum_api_local.php"
echo "   • 远程 API: https://$SERVER/ethereum_api.php"
echo ""
echo "📝 前端测试页面: https://localhost:8080/#/dataRecordsTest"
