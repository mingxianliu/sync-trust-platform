#!/bin/bash

# 测试远程 API 连接
echo "🧪 测试远程 API 连接"
echo "=================="

SERVER="synckeytech.winshare.tw"

# 测试不同的 API 端点
ENDPOINTS=(
    "https://$SERVER/data_records_api.php"
    "https://$SERVER/data_records_api.php?action=getAllRecords"
    "https://$SERVER/test_data_records_api.php"
    "https://$SERVER/ethereum_php_client.php"
)

for endpoint in "${ENDPOINTS[@]}"; do
    echo ""
    echo "🔗 测试: $endpoint"
    echo "----------------------------------------"

    # 使用 curl 测试
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$endpoint")
    RESPONSE=$(curl -s "$endpoint" | head -5)

    if [ "$HTTP_CODE" = "200" ]; then
        echo "✅ HTTP 状态: $HTTP_CODE"
        echo "📄 响应预览:"
        echo "$RESPONSE"
    else
        echo "❌ HTTP 状态: $HTTP_CODE"
        echo "📄 响应内容:"
        echo "$RESPONSE"
    fi
done

echo ""
echo "🎯 测试完成！"
