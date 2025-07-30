<?php

namespace App\Models;

use CodeIgniter\Model;

class MemberFilesModel extends Model
{
	protected $table = 'memberfiles';
	protected $primaryKey = 'ID';
	protected $allowedFields = ['ID', 'MemberNo', 'FileNo', 'MemberReceiveNo', 'FileName', 
		'Files', 'Version', 'IPFSHash', 'Blockchain', 'BlockchainTrans', 'Status', 'EncodeNo', 'EncodeStatus',
		'CreateTime', 'UpdateTime', 'CreateUser', 'UpdateUser', 'FileSize', 'Flag'];
	protected $useTimestamps = true;
	protected $createdField = 'CreateTime';
	protected $updatedField = 'UpdateTime';
	
	public function addFiles($data)
	{
		$ret = $this->insert($data);
		
		if ($ret)
		{
			return $this->insertID();
		}
		
		return $ret;
	}

	public function getFile($id)
	{
		$list = $this
		->select('memberfiles.ID, membersdata.FileName , memberfiles.MemberNo, 
			memberfiles.FileNo, memberfiles.MemberReceiveNo, memberfiles.Files, memberfiles.Version, 
			memberfiles.IPFSHash, memberfiles.Blockchain, memberfiles.Status, memberfiles.CreateTime, memberfiles.UpdateTime, 
			memberfiles.CreateUser, memberfiles.UpdateUser')
		->join('membersdata', 'memberfiles.MemberNo = membersdata.MemberNo')
		->where('memberfiles.ID', $id)
		->get()
		->getResult();
		
		return $list;
	}
	
	public function getFileByNo($no)
	{
		$file = $this->where('FileNo', $no)->first();
		
		return $file;
	}
	
	public function getFileByName($name, $memberReceiveNo)
	{
		$file = $this->where(
			[
				'FileName' => $name,
				'MemberReceiveNo' => $memberReceiveNo,
			]
		)
		->orderBy('Version DESC')->first();
		
		return $file;
	}


	public function filterFile($findTitle, $filterNumber = null, $filterStatus = null, $startPos = 0, $count = 10, $orderType = 'CreateTime', $orderBy = 'DESC')
	{
		if ($filterStatus != null){
			$this->where('memberfiles.Status', 0);
		}
		if ($filterNumber != null){
			$this->where('memberfiles.MemberNo', $filterNumber);
		}
		
		$list = $this
		->select('memberfiles.ID, membersdata.FileName , memberfiles.MemberNo, 
			memberfiles.FileNo, memberfiles.MemberReceiveNo, memberfiles.Files, memberfiles.Version, 
			memberfiles.IPFSHash, memberfiles.Blockchain, memberfiles.Status, memberfiles.CreateTime, memberfiles.UpdateTime, 
			memberfiles.CreateUser, memberfiles.UpdateUser')
		->join('membersdata', 'memberfiles.MemberNo = membersdata.MemberNo')
		->orderBy('memberfiles.'.$orderType, $orderBy)
		->limit($count, $startPos)
		->like('CONCAT(memberfiles.FileName)', $findTitle)
		->get()
		->getResult();

		$count = $this
		->select('memberfiles.ID, membersdata.FileName , memberfiles.MemberNo, 
			memberfiles.FileNo, memberfiles.MemberReceiveNo, memberfiles.Files, memberfiles.Version, 
			memberfiles.IPFSHash, memberfiles.Blockchain, memberfiles.Status, memberfiles.CreateTime, memberfiles.UpdateTime, 
			memberfiles.CreateUser, memberfiles.UpdateUser')
		->join('membersdata', 'memberfiles.MemberNo = membersdata.MemberNo')
		->orderBy('members.'.$orderType, $orderBy)
		->limit($count, $startPos)
		->like('CONCAT(memberfiles.FileName)', $findTitle)
		->countAllResults();

		return [
			'List' => $list,
			'Count' => $count,
		];
	}
	
	public function updateStatusById($no, $status)
	{
		$this->set('Status', $status);
		$this->where('FileNo', $no);
		$ret = $this->update();
		
		return $ret;
	}
	
	public function updateIPFSHashById($no, $hash)
	{
		$this->set('IPFSHash', $hash);
		$this->where('FileNo', $no);
		$ret = $this->update();
		
		return $ret;
	}
	
	public function updateBlockchainByNo($no, $hash, $hashTrans)
	{
		$this->set('Blockchain', $hash);
		$this->set('BlockchainTrans', $hashTrans);
		$this->where('FileNo', $no);
		$ret = $this->update();
		
		return $ret;
	}
	
	public function getFileByEncodeNo($encodeNo)
	{
		$list = $this
		->where('memberfiles.EncodeNo', $encodeNo)
		->where('memberfiles.EncodeStatus', 0)
		->get()
		->getResult();
		
		return $list;
	}
	
	public function getSystemKeyDetail($memberNo, $startPos = 0, $count = 1000)
	{
		$this->select("
			memberfiles.*,
			A.MemberName As 'SenderName',
			B.MemberName As 'ReceiveName',
			C.OrganizationNo As 'SenderOrgNo',
			C.OrganizationName As 'SenderOrgName',
			D.OrganizationNo As 'ReceiveOrgNo',
			D.OrganizationName As 'ReceiveOrgName'
		");
		$this->join('members A', 'memberfiles.MemberNo = A.MemberNo');
		$this->join('members B', 'memberfiles.MemberReceiveNo = B.MemberNo');
		$this->join('organizations C', 'A.OrganizationNo = C.OrganizationNo');
		$this->join('organizations D', 'B.OrganizationNo = D.OrganizationNo');
		$this->where([
			'memberfiles.Status >=' => 0,
			'memberfiles.EncodeNo' => $memberNo,
		]);
		
		$this->orderBy('memberfiles.ID DESC, memberfiles.FileName ASC, memberfiles.Version DESC');
		$this->limit($count, $startPos);
		$list = $this->get()->getResultArray();
		
		return $list;
	}
	
	public function checkMemberFileCount($memberNo, $changeDate)
	{
		if (!empty($changeDate))
		{
			$this->where([
				'CreateTime >=' => $changeDate,
			]);
		}
		
		$this->where([
			'Status' => 0,
			'MemberReceiveNo' => $memberNo,
		]);
		
		$count = $this->countAllResults();

		return $count;
	}
}