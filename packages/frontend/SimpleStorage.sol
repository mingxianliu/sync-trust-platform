pragma solidity ^0.4.26;

contract SimpleStorage {
    uint256 private storedData;

    event DataStored(address indexed from, uint256 value);

    function set(uint256 x) public {
        storedData = x;
        emit DataStored(msg.sender, x);
    }

    function get() public view returns (uint256) {
        return storedData;
    }

    function getBalance() public view returns (uint256) {
        return address(this).balance;
    }

    function deposit() public payable {
        emit DataStored(msg.sender, msg.value);
    }
}
