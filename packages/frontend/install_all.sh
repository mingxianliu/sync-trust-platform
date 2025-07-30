#!/bin/bash
set -e

echo "=============================="
echo "📦 Installing Dependencies..."
echo "=============================="
sudo dnf -y update
sudo dnf -y install epel-release
sudo dnf -y install dnf-plugins-core curl wget tar python3 python3-pip jq git

echo "=============================="
echo "🚀 Installing Geth (Ethereum)..."
echo "=============================="
GETH_VERSION="1.16.1"
GETH_BUILD="12b4131f"
wget https://gethstore.blob.core.windows.net/builds/geth-linux-amd64-${GETH_VERSION}-${GETH_BUILD}.tar.gz
tar -xvzf geth-linux-amd64-${GETH_VERSION}-${GETH_BUILD}.tar.gz
sudo mv geth-linux-amd64-${GETH_VERSION}-${GETH_BUILD}/geth /usr/local/bin/
rm -rf geth-linux-amd64-${GETH_VERSION}-${GETH_BUILD}*

echo "=============================="
echo "🚀 Installing solc..."
echo "=============================="
SOLC_VERSION="0.8.26"
wget https://github.com/ethereum/solidity/releases/download/v${SOLC_VERSION}/solc-static-linux
sudo mv solc-static-linux /usr/local/bin/solc
sudo chmod +x /usr/local/bin/solc

echo "=============================="
echo "🚀 Installing IPFS (Kubo)..."
echo "=============================="
IPFS_VERSION="v0.22.0"
wget https://dist.ipfs.io/go-ipfs/${IPFS_VERSION}/go-ipfs_${IPFS_VERSION}_linux-amd64.tar.gz
tar -xvzf go-ipfs_${IPFS_VERSION}_linux-amd64.tar.gz
cd go-ipfs && sudo bash install.sh && cd ..
rm -rf go-ipfs*

ipfs init
nohup ipfs daemon > ipfs.log 2>&1 &

echo "=============================="
echo "🐍 Installing Python Web3..."
echo "=============================="
pip3 install web3

echo "=============================="
echo "🧱 Compiling Smart Contract..."
echo "=============================="
solc --optimize --combined-json abi,bin contracts/SimpleStorage.sol -o . --overwrite > SimpleStorage.json

echo "=============================="
echo "🚀 Deploying Smart Contract..."
echo "=============================="
python3 deploy_contract.py

echo "=============================="
echo "🧱 Compiling Smart Contract..."
echo "=============================="
solc --optimize --combined-json abi,bin contracts/smartContract.sol -o . --overwrite > smartContract.json

echo "=============================="
echo "🚀 Deploying Smart Contract..."
echo "=============================="
python3 deploy_smart_contract.py

echo "=============================="
echo "🚀 Deploying Frontend (Quasar SPA)..."
echo "=============================="

# build 前端
npm run build

# 上傳前端檔案（請將 user@your.server.ip 和 /var/www/html 替換為你的實際帳號與目錄）
scp -r dist/spa/* user@your.server.ip:/var/www/html/

# 重啟 nginx
ssh user@your.server.ip "sudo systemctl restart nginx"

# 線上驗證 TEST-MENULIST 是否出現
ssh user@your.server.ip "curl -s https://synckeytech.winshare.tw | grep TEST-MENULIST || echo 'TEST-MENULIST not found'"

echo "=============================="
echo "🚦 Starting Geth node..."
echo "=============================="
cat > genesis.json << 'EOF'
{
  "config": {
    "chainId": 1337,
    "homesteadBlock": 0,
    "eip150Block": 0,
    "eip155Block": 0,
    "eip158Block": 0,
    "byzantiumBlock": 0,
    "constantinopleBlock": 0,
    "petersburgBlock": 0,
    "istanbulBlock": 0,
    "berlinBlock": 0,
    "londonBlock": 0
  },
  "difficulty": "1",
  "gasLimit": "8000000",
  "alloc": {
    "0x0000000000000000000000000000000000000000": {
      "balance": "0"
    }
  }
}
EOF

geth init genesis.json

nohup geth --http --http.addr "0.0.0.0" --http.port 8545 --http.corsdomain "*" --http.api "eth,net,web3,personal" --allow-insecure-unlock --unlock "0" --password <(echo "") --mine --miner.threads 1 > geth.log 2>&1 &

echo "⏳ Waiting for Geth to start..."
sleep 10

echo "=============================="
echo "✅ Installation Complete!"
echo "=============================="
echo "📊 Services Status:"
echo "  - Geth: http://localhost:8545"
echo "  - IPFS: http://localhost:5001"
echo "  - IPFS Gateway: http://localhost:8080"
echo ""
echo "📝 Logs:"
echo "  - Geth: tail -f geth.log"
echo "  - IPFS: tail -f ipfs.log"
