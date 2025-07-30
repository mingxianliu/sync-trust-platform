#!/usr/bin/env python3
import json
import time
import subprocess
from web3 import Web3

# Connect to the local Ethereum node
w3 = Web3(Web3.HTTPProvider('http://localhost:8545'))

def check_dag_progress():
    """Check DAG generation progress from geth logs"""
    try:
        result = subprocess.run(['tail', '-1', 'geth.log'], capture_output=True, text=True)
        if 'Generating DAG in progress' in result.stdout:
            # Extract percentage
            import re
            match = re.search(r'percentage=(\d+)', result.stdout)
            if match:
                return int(match.group(1))
        elif 'Successfully sealed new block' in result.stdout:
            return 100  # DAG complete, mining started
        return 0
    except:
        return 0

def wait_for_dag_completion():
    """Wait for DAG generation to complete"""
    print("⏳ Waiting for DAG generation to complete...")
    while True:
        progress = check_dag_progress()
        if progress == 100:
            print("✅ DAG generation completed!")
            time.sleep(5)  # Wait a bit more for mining to stabilize
            break
        elif progress > 0:
            print(f"📊 DAG Progress: {progress}%")
        else:
            print("⏳ Checking DAG progress...")
        time.sleep(10)

def deploy_contract():
    """Deploy the Coursetro contract"""
    print("🚀 Deploying Coursetro contract...")

    # Load contract ABI and bytecode
    with open("Coursetro.abi") as f:
        contract_abi = json.load(f)

    with open("Coursetro.bin") as f:
        contract_bytecode = f.read().strip()

    # Get the account
    accounts = w3.eth.accounts
    if not accounts:
        print("❌ No accounts found!")
        return

    account = accounts[0]
    print(f"📝 Using account: {account}")

    # Create contract instance
    Contract = w3.eth.contract(abi=contract_abi, bytecode=contract_bytecode)

    # Get gas estimate
    gas_estimate = Contract.constructor().estimate_gas({'from': account})
    print(f"⛽ Gas estimate: {gas_estimate}")

    # Deploy contract
    tx_hash = Contract.constructor().transact({
        'from': account,
        'gas': gas_estimate
    })

    print(f"📤 Transaction hash: {tx_hash.hex()}")

    # Wait for transaction receipt
    print("⏳ Waiting for transaction confirmation...")
    tx_receipt = w3.eth.wait_for_transaction_receipt(tx_hash, timeout=300)

    contract_address = tx_receipt.contractAddress
    print(f"✅ Contract deployed at: {contract_address}")

    # Save contract info
    contract_info = {
        'address': contract_address,
        'abi': contract_abi,
        'tx_hash': tx_hash.hex(),
        'block_number': tx_receipt.blockNumber
    }

    with open('deployed_contract.json', 'w') as f:
        json.dump(contract_info, f, indent=2)

    print("💾 Contract info saved to deployed_contract.json")

    # Test contract interaction
    contract_instance = w3.eth.contract(address=contract_address, abi=contract_abi)

    # Get initial value
    initial_value = contract_instance.functions.get().call({'from': account})
    print(f"📊 Initial value: {initial_value}")

    # Set a new value
    new_value = 42
    set_tx = contract_instance.functions.set(new_value).transact({'from': account})
    w3.eth.wait_for_transaction_receipt(set_tx)

    # Get updated value
    updated_value = contract_instance.functions.get().call({'from': account})
    print(f"📊 Updated value: {updated_value}")

    return contract_address

if __name__ == "__main__":
    # Check connection
    if not w3.is_connected():
        print("❌ Failed to connect to Ethereum node!")
        exit(1)

    print("✅ Connected to Ethereum node")
    print(f"🔗 Network ID: {w3.eth.chain_id}")
    print(f"📊 Latest block: {w3.eth.block_number}")

    # Skip DAG checking since mining is already working
    print("✅ Mining is already active, proceeding with deployment...")

    # Deploy contract
    contract_address = deploy_contract()

    if contract_address:
        print(f"\n🎉 Contract deployment successful!")
        print(f"📍 Contract address: {contract_address}")
        print(f"🔗 View on block explorer: http://localhost:8545")
    else:
        print("❌ Contract deployment failed!")
