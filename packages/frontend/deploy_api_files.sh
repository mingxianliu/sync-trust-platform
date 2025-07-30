#!/bin/bash

# 部署 PHP API 文件到远程服务器
# 使用方法: ./deploy_api_files.sh

echo "🚀 开始部署 PHP API 文件到远程服务器"
echo "=================================="

# 服务器信息
SERVER="synckeytech.winshare.tw"
REMOTE_DIR="/var/www/html"

# 需要部署的文件列表
FILES=(
    "data_records_api.php"
    "test_data_records_api.php"
    "ethereum_php_client.php"
    "contract_info.json"
)

echo "📋 准备部署以下文件:"
for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "   ✅ $file"
    else
        echo "   ❌ $file (文件不存在)"
        exit 1
    fi
done

echo ""
echo "🔧 开始上传文件..."

# 使用 scp 上传文件
for file in "${FILES[@]}"; do
    echo "📤 上传 $file..."
    scp "$file" "root@$SERVER:$REMOTE_DIR/"

    if [ $? -eq 0 ]; then
        echo "   ✅ $file 上传成功"
    else
        echo "   ❌ $file 上传失败"
        exit 1
    fi
done

echo ""
echo "🔐 设置文件权限..."

# 设置文件权限
ssh "root@$SERVER" "chmod 644 $REMOTE_DIR/data_records_api.php"
ssh "root@$SERVER" "chmod 644 $REMOTE_DIR/test_data_records_api.php"
ssh "root@$SERVER" "chmod 644 $REMOTE_DIR/ethereum_php_client.php"
ssh "root@$SERVER" "chmod 644 $REMOTE_DIR/contract_info.json"

echo "✅ 文件权限设置完成"

echo ""
echo "🧪 测试 API 连接..."

# 测试 API 是否正常工作
TEST_URL="https://$SERVER/data_records_api.php"
echo "测试 URL: $TEST_URL"

# 使用 curl 测试 API
RESPONSE=$(curl -s "$TEST_URL" 2>/dev/null)

if [ $? -eq 0 ]; then
    echo "✅ API 响应正常"
    echo "📄 响应内容预览:"
    echo "$RESPONSE" | head -20
else
    echo "❌ API 测试失败"
fi

echo ""
echo "🎉 部署完成！"
echo ""
echo "📊 可用的 API 端点:"
echo "   • 数据记录查询: https://$SERVER/data_records_api.php"
echo "   • API 测试页面: https://$SERVER/test_data_records_api.php"
echo "   • 获取所有记录: https://$SERVER/data_records_api.php?action=getAllRecords"
echo ""
echo "🔗 前端测试页面:"
echo "   • 本地测试: https://localhost:8080/#/dataRecordsTest"
echo "   • 生产测试: https://$SERVER/#/dataRecordsTest"
