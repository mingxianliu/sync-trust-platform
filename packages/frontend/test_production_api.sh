#!/bin/bash

echo "🧪 測試生產環境 API 連接"
echo "========================"

# 生產環境 URL
PROD_URL="https://synckeytech.winshare.tw"

echo "📡 測試基本連接..."
curl -I "$PROD_URL" 2>/dev/null | head -5

echo ""
echo "🔍 測試 DataRecords API..."
curl -s "$PROD_URL/data_records_api.php?action=getAllRecords" 2>/dev/null | head -10

echo ""
echo "🔍 測試 Ethereum API..."
curl -s -X POST "$PROD_URL/ethereum_api.php" \
  -H "Content-Type: application/json" \
  -d '{"action":"getBlockNumber"}' 2>/dev/null | head -10

echo ""
echo "✅ 測試完成"
