// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract SimpleStorage {
    uint storedData;

    struct DataRecord {
        string content;
        uint256 timestamp;
        address uploader;
        string description;
        bool exists;
    }

    DataRecord[] public dataRecords;

    event DataRecorded(
        uint256 indexed recordId,
        string content,
        uint256 timestamp,
        address indexed uploader,
        string description
    );

    event DataUpdated(
        uint256 indexed recordId,
        string content,
        uint256 timestamp
    );

    function set(uint x) public {
        storedData = x;
    }

    function get() public view returns (uint) {
        return storedData;
    }

    function addDataRecord(string memory _content, string memory _description) public {
        DataRecord memory newRecord = DataRecord({
            content: _content,
            timestamp: block.timestamp,
            uploader: msg.sender,
            description: _description,
            exists: true
        });

        dataRecords.push(newRecord);
        uint256 recordId = dataRecords.length - 1;

        emit DataRecorded(recordId, _content, block.timestamp, msg.sender, _description);
    }

    function updateDataRecord(uint256 _recordId, string memory _content) public {
        require(_recordId < dataRecords.length, "Record does not exist");
        require(dataRecords[_recordId].exists, "Record has been deleted");

        dataRecords[_recordId].content = _content;
        dataRecords[_recordId].timestamp = block.timestamp;

        emit DataUpdated(_recordId, _content, block.timestamp);
    }

    function getDataRecord(uint256 _recordId) public view returns (
        string memory content,
        uint256 timestamp,
        address uploader,
        string memory description,
        bool exists
    ) {
        require(_recordId < dataRecords.length, "Record does not exist");
        DataRecord memory record = dataRecords[_recordId];
        return (record.content, record.timestamp, record.uploader, record.description, record.exists);
    }

    function getDataRecordCount() public view returns (uint256) {
        return dataRecords.length;
    }

    function getAllDataRecords() public view returns (DataRecord[] memory) {
        return dataRecords;
    }

    function setXml(string memory _xml) public {
        storedData = uint256(keccak256(abi.encodePacked(_xml)));
        addDataRecord(_xml, "XML Data");
    }

    function getXml() public view returns (string memory) {
        if (dataRecords.length > 0) {
            return dataRecords[dataRecords.length - 1].content;
        }
        return "";
    }
}
