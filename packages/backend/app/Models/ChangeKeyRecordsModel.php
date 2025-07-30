<?php

namespace App\Models;

use CodeIgniter\Model;

class ChangeKeyRecordsModel extends Model
{
	protected $table = 'changekeyrecords';
	protected $primaryKey = 'ID';
	protected $allowedFields = ['ID', 'MemberNo', 'PublicKey', 'PrivateKey', 'PrivatePwd',
					'Blockchain', 'Status', 'CreateTime', 'UpdateTime',
					'CreateUser', 'UpdateUser', 'FileList'];
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

	public function getRecord($no)
	{
		$list = $this
		->select('changekeyrecords.*')
		->where('changekeyrecords.MemberNo', $no)
		->orderBy('changekeyrecords.id DESC')
		->get()
		->getResult();
		
		return $list;
	}
	
	public function updateBlockchainByNo($no, $hash)
	{
		$this->set('Blockchain', $hash);
		$this->set('Status', 1);
		$this->where('MemberNo', $no);
		$this->where('Status', 0);
		$ret = $this->update();
		
		return $ret;
	}
}