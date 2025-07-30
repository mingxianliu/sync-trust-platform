#!/bin/bash

# 寻找正确的 API 端点
echo "🔍 寻找正确的 API 端点"
echo "===================="

SERVER="synckeytech.winshare.tw"

# 测试不同的路径
PATHS=(
    "/api/data-records"
    "/api/blockchain/records"
    "/api/ethereum/records"
    "/backend/data_records_api.php"
    "/backend/api/data_records_api.php"
    "/php/data_records_api.php"
    "/php/api/data_records_api.php"
    "/data_records_api.php"
    "/test_data_records_api.php"
    "/ethereum_php_client.php"
    "/contract_info.json"
)

for path in "${PATHS[@]}"; do
    URL="https://$SERVER$path"
    echo ""
    echo "🔗 测试: $URL"
    echo "----------------------------------------"

    # 获取 HTTP 状态码和响应头
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$URL")
    CONTENT_TYPE=$(curl -s -I "$URL" | grep -i "content-type" | head -1)

    echo "📊 HTTP 状态: $HTTP_CODE"
    echo "📋 Content-Type: $CONTENT_TYPE"

    if [ "$HTTP_CODE" = "200" ]; then
        # 获取响应内容的前几行
        RESPONSE=$(curl -s "$URL" | head -3)
        echo "📄 响应预览:"
        echo "$RESPONSE"

        # 检查是否是 JSON 响应
        if echo "$RESPONSE" | grep -q "^{"; then
            echo "✅ 可能是 JSON API 响应"
        elif echo "$RESPONSE" | grep -q "<?php"; then
            echo "✅ 可能是 PHP 文件"
        elif echo "$RESPONSE" | grep -q "<!DOCTYPE html>"; then
            echo "⚠️  返回 HTML 页面（可能是前端 SPA）"
        else
            echo "📝 其他类型的响应"
        fi
    else
        echo "❌ 无法访问"
    fi
done

echo ""
echo "🎯 搜索完成！"
