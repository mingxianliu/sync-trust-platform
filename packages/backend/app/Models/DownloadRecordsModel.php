<?php

namespace App\Models;

use CodeIgniter\Model;

class DownloadRecordsModel extends Model
{
	protected $table = 'downloadrecords';
	protected $primaryKey = 'ID';
	protected $allowedFields = ['ID', 'MemberNo', 'FileNo', 'Status', 'Blockchain', 'BlockchainTrans',
		 'CreateTime', 'UpdateTime', 'CreateUser', 'UpdateUser'];
	protected $useTimestamps = true;
	protected $createdField = 'CreateTime';
	protected $updatedField = 'UpdateTime';
	
	public function addRecords($data)
	{
		$ret = $this->insert($data);
		
		if ($ret)
		{
			return $this->insertID();
		}
		
		return $ret;
	}

	public function getFileRecord($no)
	{
		$list = $this
		->select('memberfiles.ID, membersdata.FileName , memberfiles.MemberNo, 
			memberfiles.FileNo, memberfiles.MemberReceiveNo, memberfiles.Files, memberfiles.Version, 
			memberfiles.IPFSHash, memberfiles.Blockchain, memberfiles.Status, memberfiles.CreateTime, memberfiles.UpdateTime, 
			memberfiles.CreateUser, memberfiles.UpdateUser')
		->join('membersdata', 'memberfiles.MemberNo = membersdata.MemberNo')
		->where('memberfiles.FileNo', $no)
		->get()
		->getResult();
		return $list;
	}
	
	public function updateBlockchainByNo($no, $hash, $hashTrans)
	{
		$this->set('Blockchain', $hash);
		$this->set('BlockchainTrans', $hashTrans);
		$this->where('FileNo', $no);
		$ret = $this->update();
		
		return $ret;
	}
}