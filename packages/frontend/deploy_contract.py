#!/usr/bin/env python3
import json
import subprocess
import time
from web3 import Web3

# 連接到本地 Geth 節點
w3 = Web3(Web3.HTTPProvider('http://localhost:8545'))

# 檢查連接
if not w3.is_connected():
    print("無法連接到 Geth 節點")
    exit(1)

print("已連接到 Geth 節點")

# 帳戶地址
account = "0x5D640Ba7Ed0755f44331544598e8F6EA3662baD0"

# 檢查帳戶餘額
balance = w3.eth.get_balance(account)
print(f"帳戶餘額: {w3.from_wei(balance, 'ether')} ETH")

# 編譯合約
print("編譯智能合約...")
try:
    result = subprocess.run([
        '/usr/local/bin/solc-0.4.26', '--version'
    ], capture_output=True, text=True)
    print(f"Solc 版本: {result.stdout.strip()}")
except:
    print("警告: 無法檢查 solc-0.4.26 版本")

# 編譯合約
compile_result = subprocess.run([
    '/usr/local/bin/solc-0.4.26', '--optimize', '--combined-json', 'abi,bin', 'smartContract.sol'
], capture_output=True, text=True)

if compile_result.returncode != 0:
    print(f"編譯失敗: {compile_result.stderr}")
    exit(1)

# 解析編譯結果
contracts = json.loads(compile_result.stdout)
contract_data = contracts['contracts']['smartContract.sol:Coursetro']

abi = json.loads(contract_data['abi'])
bytecode = contract_data['bin']

print("合約編譯成功")

# 創建合約實例
contract = w3.eth.contract(abi=abi, bytecode=bytecode)

# 獲取 nonce
nonce = w3.eth.get_transaction_count(account)

# 構建部署交易
transaction = contract.constructor().build_transaction({
    'from': account,
    'nonce': nonce,
    'gas': 2000000,
    'gasPrice': w3.eth.gas_price
})

print("部署合約...")

# 發送交易
tx_hash = w3.eth.send_transaction(transaction)
print(f"交易哈希: {tx_hash.hex()}")

# 等待交易確認
print("等待交易確認...")
tx_receipt = w3.eth.wait_for_transaction_receipt(tx_hash)

if tx_receipt.status == 1:
    # 兼容不同版本的 web3.py
    contract_address = getattr(tx_receipt, 'contractAddress', None) or getattr(tx_receipt, 'contract_address', None)
    print(f"合約部署成功！地址: {contract_address}")

    # 保存合約信息
    contract_info = {
        'address': contract_address,
        'abi': abi,
        'tx_hash': tx_hash.hex(),
        'block_number': tx_receipt.blockNumber
    }

    with open('contract_info.json', 'w') as f:
        json.dump(contract_info, f, indent=2)

    print("合約信息已保存到 contract_info.json")

    # 測試合約
    print("\n測試合約功能...")
    deployed_contract = w3.eth.contract(address=contract_address, abi=abi)

    # 測試 setXml 函數
    test_xml = "<test>Hello World</test>"
    nonce = w3.eth.get_transaction_count(account)
    set_tx = deployed_contract.functions.setXml(test_xml).build_transaction({
        'from': account,
        'nonce': nonce,
        'gas': 200000,
        'gasPrice': w3.eth.gas_price
    })

    set_tx_hash = w3.eth.send_transaction(set_tx)
    print(f"設置 XML 交易哈希: {set_tx_hash.hex()}")

    # 等待確認
    set_receipt = w3.eth.wait_for_transaction_receipt(set_tx_hash)
    if set_receipt.status == 1:
        print("設置 XML 成功")

        # 讀取 XML
        xml_value = deployed_contract.functions.getXml().call()
        print(f"讀取的 XML: {xml_value}")

        # 測試 signXml 函數
        signed_xml = "<signed>Signed Document</signed>"
        nonce = w3.eth.get_transaction_count(account)
        sign_tx = deployed_contract.functions.signXml(signed_xml).build_transaction({
            'from': account,
            'nonce': nonce,
            'gas': 200000,
            'gasPrice': w3.eth.gas_price
        })

        sign_tx_hash = w3.eth.send_transaction(sign_tx)
        print(f"簽名 XML 交易哈希: {sign_tx_hash.hex()}")

        sign_receipt = w3.eth.wait_for_transaction_receipt(sign_tx_hash)
        if sign_receipt.status == 1:
            print("簽名 XML 成功")

            # 再次讀取 XML
            final_xml = deployed_contract.functions.getXml().call()
            print(f"最終 XML: {final_xml}")

else:
    print("合約部署失敗")
