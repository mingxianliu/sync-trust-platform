#!/bin/bash

# 部署前端 (Quasar SPA) 到遠端伺服器
# 使用方法: ./deploy_frontend.sh

echo "🚀 開始部署前端 (Quasar SPA) 到遠端伺服器"
echo "=========================================="

# 伺服器資訊 (使用與 deploy_api_files.sh 相同的配置)
SERVER="synckeytech.winshare.tw"
REMOTE_DIR="/www/nspo_ipfs_frontend2"  # 根據 nginx 配置

echo "📋 伺服器資訊:"
echo "   • 伺服器: $SERVER"
echo "   • 遠端目錄: $REMOTE_DIR"

# 檢查是否已經構建
if [ ! -d "dist/spa" ]; then
    echo "❌ 錯誤: dist/spa 目錄不存在"
    echo "請先執行: npm run build"
    exit 1
fi

echo ""
echo "🔧 開始構建前端..."

# 構建前端
npm run build

if [ $? -ne 0 ]; then
    echo "❌ 構建失敗"
    exit 1
fi

echo "✅ 構建完成"

echo ""
echo "📤 開始上傳檔案..."

# 備份現有檔案
echo "📦 備份現有檔案..."
ssh "root@$SERVER" "if [ -d '$REMOTE_DIR' ]; then cp -r $REMOTE_DIR ${REMOTE_DIR}_backup_$(date +%Y%m%d_%H%M%S); fi"

# 上傳前端檔案
echo "📤 上傳前端檔案..."
scp -r dist/spa/* "root@$SERVER:$REMOTE_DIR/"

if [ $? -eq 0 ]; then
    echo "   ✅ 前端檔案上傳成功"
else
    echo "   ❌ 前端檔案上傳失敗"
    exit 1
fi

echo ""
echo "🔐 設定檔案權限..."

# 設定檔案權限與屬主，避免 403 問題
ssh "root@$SERVER" "chmod -R 644 $REMOTE_DIR/*"
ssh "root@$SERVER" "chmod 755 $REMOTE_DIR"
ssh "root@$SERVER" "find $REMOTE_DIR -type d -exec chmod 755 {} \\;"
ssh "root@$SERVER" "find $REMOTE_DIR -type f -exec chmod 644 {} \\;"
ssh "root@$SERVER" "chown -R nginx:nginx $REMOTE_DIR"

# 檢查 SELinux 狀態並提醒
ssh "root@$SERVER" "getenforce 2>/dev/null | grep -q Enforcing && echo '⚠️  SELinux 啟用中，建議 setenforce 0 或永久關閉以避免靜態檔案 403 問題' || echo 'SELinux 未啟用'"

echo "✅ 檔案權限設定完成"

echo ""
echo "🔄 重啟 Nginx..."

# 重啟 nginx
ssh "root@$SERVER" "systemctl restart nginx"

if [ $? -eq 0 ]; then
    echo "✅ Nginx 重啟成功"
else
    echo "❌ Nginx 重啟失敗"
    exit 1
fi

echo ""
echo "🧪 測試網站連接..."

# 測試網站是否正常運作
TEST_URL="https://$SERVER"
echo "測試 URL: $TEST_URL"

# 使用 curl 測試網站
RESPONSE=$(curl -s -I "$TEST_URL" 2>/dev/null | head -1)

if [[ $RESPONSE == *"200"* ]]; then
    echo "✅ 網站響應正常"
else
    echo "❌ 網站測試失敗"
    echo "響應: $RESPONSE"
fi

echo ""
echo "🎉 前端部署完成！"
echo ""
echo "📊 部署資訊:"
echo "   • 網站 URL: https://$SERVER"
echo "   • 備份位置: ${REMOTE_DIR}_backup_$(date +%Y%m%d_%H%M%S)"
echo ""
echo "🔗 測試頁面:"
echo "   • 主頁: https://$SERVER"
echo "   • 數據記錄測試: https://$SERVER/#/dataRecordsTest"
echo "   • Dashboard: https://$SERVER/#/dashboard"
echo ""
echo "📝 日誌位置:"
echo "   • Nginx 訪問日誌: /var/log/nginx/syncadmin_access.log"
echo "   • Nginx 錯誤日誌: /var/log/nginx/syncadmin_error.log"
