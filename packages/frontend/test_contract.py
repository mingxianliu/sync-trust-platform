#!/usr/bin/env python3
import json
from web3 import Web3

def test_contract():
    # 連接到本地節點
    w3 = Web3(Web3.HTTPProvider('http://localhost:8545'))

    # 載入合約信息
    with open('/root/contract_info.json', 'r') as f:
        contract_info = json.load(f)

    # 創建合約實例
    contract = w3.eth.contract(
        address=contract_info['address'],
        abi=contract_info['abi']
    )

    print("=== 合約測試 ===")
    print(f"合約地址: {contract_info['address']}")
    print(f"當前區塊: {w3.eth.block_number}")

    # 測試 getXml
    try:
        xml_content = contract.functions.getXml().call()
        print(f"當前 XML: {xml_content}")
    except Exception as e:
        print(f"getXml 錯誤: {e}")

    # 測試 setXml
    try:
        test_xml = "<test>Hello from Python</test>"
        print(f"設置 XML: {test_xml}")

        # 構建交易
        tx = contract.functions.setXml(test_xml).build_transaction({
            'from': '0x5D640Ba7Ed0755f44331544598e8F6EA3662baD0',
            'gas': 200000,
            'gasPrice': w3.eth.gas_price,
            'nonce': w3.eth.get_transaction_count('0x5D640Ba7Ed0755f44331544598e8F6EA3662baD0')
        })

        print(f"交易哈希: {tx}")

    except Exception as e:
        print(f"setXml 錯誤: {e}")

if __name__ == "__main__":
    test_contract()
