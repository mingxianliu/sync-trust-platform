<?php

namespace App\Models;

use CodeIgniter\Model;

class MembersModel extends Model
{
	protected $table = 'members';
	protected $primaryKey = 'ID';
	protected $allowedFields = ['ID', 'MemberNo', 'MemberAcc', 'MemberPwd', 'MemberName', 'MemberPic',
					'OrganizationNo', 'IsAdmin', 'IsSend', 'IsReceive', 'PublicKey', 'PrivateKey',
					'PrivatePwd', 'Status', 'Email', 'GroupNo', 'LogoutTime', 'CreateTime', 'UpdateTime',
					'CreateUser', 'UpdateUser', 'DisableTime', 'ChangePWTime'
				];
	protected $useTimestamps = true;
	protected $createdField = 'CreateTime';
	protected $updatedField = 'UpdateTime';
				
	public function getEnableMemberByAccount($account)
	{
		$member = $this
		->select('members.*, organizations.OrganizationName, organizations.IsAdmin IsAdminOrg')
		->join('organizations', 'members.OrganizationNo = organizations.OrganizationNo')
		->where(['members.MemberAcc' => $account, 'members.Status' => 1])
		->first();
		
		return $member;
		
	}
	
	public function getMemberAll($orgNo, $isAdminOrg, $isAdmin, $order, $desc)
	{
		$cond = array();
		if (!$isAdminOrg)
		{
			if ($isAdmin)
			{
				$cond = array("members.OrganizationNo" => $orgNo);
			}
			else
			{
				$cond = array("members.OrganizationNo" => "");
			}
		}
		
		$orderTable = 'members.';
		if ($order == "OrganizationName")
		{
			$orderTable = "organizations.";
		}
		
		$members = $this
		->select('members.*, organizations.OrganizationName')
		->join('organizations', 'members.OrganizationNo = organizations.OrganizationNo')
		->where($cond)
		->orderBy($orderTable.$order, $desc)
		->findAll();
		
		return $members;
		
	}
	
	public function getEnableMember()
	{
		$members = $this
		->select('members.*, organizations.OrganizationName')
		->join('organizations', 'members.OrganizationNo = organizations.OrganizationNo')
		->where(['members.Status' => 1])
		->findAll();
		
		return $members;
		
	}
	
	public function filterMember($findTitle= "", $findOrg = "", $filterStatus = null, $startPos = 0, $count = 10, $orderType = 'CreateTime', $orderBy = 'DESC')
	{
		if ($filterStatus != null){
			$this->where([
				'members.Status' => 1,
			]);
		}
		
		if (!empty($findOrg)){
			$this->where([
				'members.OrganizationNo' => $findOrg,
			]);
		}
		
		if (!empty($findTitle)){
			$this->like('CONCAT(members.MemberAcc, organizations.Email, members.MemberName)', $findTitle);
		}
		
		$list = $this
		->select('members.*, organizations.OrganizationName')
		->join('organizations', 'members.OrganizationNo = organizations.OrganizationNo')
		->orderBy('members.'.$orderType, $orderBy)
		->limit($count, $startPos)
		->get()
		->getResult();

		$count = $this
		->select('members.*, organizations.OrganizationName')
		->join('organizations', 'members.OrganizationNo = organizations.OrganizationNo')
		->orderBy('members.'.$orderType, $orderBy)
		->limit($count, $startPos)
		->countAllResults();

		return [
			'List' => $list,
			'Count' => $count,
		];
	}

	public function getMember($memberNo)
	{
		$this->where([
			'members.MemberNo' => $memberNo,
		]);
		$this->select('members.*, organizations.OrganizationName');
		$this->join('organizations', 'members.OrganizationNo = organizations.OrganizationNo');
		
		$data = $this->asArray()->first();

		if ($data != null) {
			if ($data['PublicKey'] != null) {
				$data['PublicKey'] = preg_replace("/ /", "\n", $data['PublicKey']);
				$data['PublicKey'] = preg_replace("/-----BEGIN\nPUBLIC\nKEY-----/", "-----BEGIN PUBLIC KEY-----", $data['PublicKey']);
				$data['PublicKey'] = preg_replace("/-----END\nPUBLIC\nKEY-----/", "-----END PUBLIC KEY-----", $data['PublicKey']);
			}
	
			if ($data['PrivateKey'] != null) {
				$data['PrivateKey'] = preg_replace("/ /", "\n", $data['PrivateKey']);
				$data['PrivateKey'] = preg_replace("/-----BEGIN\nPRIVATE\nKEY-----/", "-----BEGIN PRIVATE KEY-----", $data['PrivateKey']);
				$data['PrivateKey'] = preg_replace("/-----END\nPRIVATE\nKEY-----/", "-----END PRIVATE KEY-----", $data['PrivateKey']);
			}
		}
		
		return $data;
	}
	
	public function addMember($data)
	{
		$exist = $this->where(
			[
				'MemberAcc' => $data["MemberAcc"],
			]
		)
		->findAll();
			
		if(count($exist) > 0)
		{
			return false;
		}
		
		$ret = $this->insert($data);
		
		if ($ret)
		{
			return $this->insertID();
		}
		
		return $ret;
	}
	
	public function getChangePWTimeOverMember()
	{
		$members = $this
		->where("ChangePWTime <= DATE_ADD(NOW(),INTERVAL -90 DAY)")
		->findAll();
		
		return $members;
	}
	
	public function getSystemKeyList($startPos = 0, $count = 20)
	{
		$list = $this
		->select("MAX(members.CreateTime) AS CreateTime, COUNT(memberfiles.FileName) AS FileCount, Min(memberfiles.IPFSHash) AS IPFSHash, members.MemberNo, IFNULL(MAX(members.DisableTime), '') AS DisableTime")
		->select('MAX(members.PublicKey) AS PublicKey, SHA2(MAX(members.PublicKey), 512) AS Hash, MAX(members.Status) AS Status, MAX(memberfiles.ID) AS ID')
		->join('memberfiles', 'members.MemberNo = memberfiles.EncodeNo', 'LEFT')
		->where(['members.OrganizationNo' => 'NSPOEncode'])
		->groupBy('members.MemberNo')
		->orderBy('Status DESC, ID DESC')
		->limit($count, $startPos)
		->get()
		->getResult();
		
		$count = $this
		->select("MAX(members.CreateTime) AS CreateTime, COUNT(memberfiles.FileName) AS FileCount, Min(memberfiles.IPFSHash) AS IPFSHash, members.MemberNo, IFNULL(MAX(members.DisableTime), '') AS DisableTime")
		->select('MAX(members.PublicKey) AS PublicKey, SHA2(MAX(members.PublicKey), 512) AS Hash, MAX(members.Status) AS Status, MAX(memberfiles.ID) AS ID')
		->join('memberfiles', 'members.MemberNo = memberfiles.EncodeNo', 'LEFT')
		->where(['members.OrganizationNo' => 'NSPOEncode'])
		->groupBy('members.MemberNo')
		->orderBy('Status DESC, ID DESC')
		->limit($count, $startPos)
		->countAllResults();

		return [
			'List' => $list,
			'Count' => $count,
		];
	}
	
	public function filterMemberByOrg($orgNo, $orgName, $memberNo, $memberName, $exclude, $order, $desc)
	{
		$this->select('members.ID, members.MemberNo, members.MemberName, members.MemberAcc, members.OrganizationNo, members.IsAdmin, members.IsReceive, members.IsSend,');
		$this->select('members.Email, members.Status, members.GroupNo, members.LogoutTime, members.DisableTime, members.ChangePWTime, organizations.OrganizationName, organizations.IsAdmin IsAdminOrg');
		$this->join('organizations', 'members.OrganizationNo = organizations.OrganizationNo');
		
		if (!empty($orgNo))
		{
			$this->where([
				'members.OrganizationNo' => $orgNo,
			]);
				
		}
		
		if (!empty($orgName))
		{
			$this->like([
				'organizations.OrganizationName' => $orgName,
			]);
				
		}
		
		if (!empty($memberName))
		{
			$this->like([
				'members.MemberName' => $memberName,
			]);
		}
		
		if(empty($exclude))
		{
			$this->where([
				'members.Status' => 1
			]);
		}
		else
		{
			$this->where([
				'members.MemberNo <>' => $memberNo,
				'members.Status' => 1
			]);
		}
		
		$orderTable = 'members.';
		if ($order == "OrganizationName")
		{
			$orderTable = "organizations.";
		}
		
		$this->orderBy($orderTable.$order, $desc);
		$list = $this->get()->getResultArray();
		
		return $list;
	}
}