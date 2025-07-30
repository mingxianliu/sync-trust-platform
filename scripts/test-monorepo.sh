#!/bin/bash

echo "🧪 測試 SyncTrust Platform..."

# 檢查目錄結構
echo "📁 檢查目錄結構..."
if [ -d "packages/backend" ] && [ -d "packages/frontend" ]; then
    echo "✅ 目錄結構正確"
else
    echo "❌ 目錄結構錯誤"
    exit 1
fi

# 檢查後端檔案
echo "🔧 檢查後端檔案..."
if [ -f "packages/backend/composer.json" ] && [ -f "packages/backend/spark" ]; then
    echo "✅ 後端檔案存在"
else
    echo "❌ 後端檔案缺失"
    exit 1
fi

# 檢查前端檔案
echo "🎨 檢查前端檔案..."
if [ -f "packages/frontend/package.json" ]; then
    echo "✅ 前端檔案存在"
else
    echo "❌ 前端檔案缺失"
    exit 1
fi

# 檢查 Docker 檔案
echo "🐳 檢查 Docker 檔案..."
if [ -f "docker-compose.yml" ] && [ -f "docker-compose.dev.yml" ]; then
    echo "✅ Docker 檔案存在"
else
    echo "❌ Docker 檔案缺失"
    exit 1
fi

# 檢查 Makefile
echo "🔨 檢查 Makefile..."
if [ -f "Makefile" ]; then
    echo "✅ Makefile 存在"
else
    echo "❌ Makefile 缺失"
    exit 1
fi

echo "🎉 Platform 測試完成！"
echo ""
echo "�� 可用的命令："
echo "  make help          - 顯示所有可用命令"
echo "  make install       - 安裝所有依賴"
echo "  make dev           - 啟動開發環境"
echo "  make docker-up     - 啟動生產環境 Docker"
echo "  make docker-dev-up - 啟動開發環境 Docker"
