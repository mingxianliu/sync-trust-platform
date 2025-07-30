<?php

namespace App\Models;

use CodeIgniter\Model;

class GroupsModel extends Model
{
	protected $table = 'groups';
	protected $primaryKey = 'ID';
	protected $allowedFields = ['ID', 'GroupNo', 'GroupName', 'OrganizationNo', 
					'IsAdmin', 'IsSend', 'IsReceive', 'Status', 'CreateTime', 'UpdateTime',
					'CreateUser', 'UpdateUser'
				];
	protected $useTimestamps = true;
	protected $createdField = 'CreateTime';
	protected $updatedField = 'UpdateTime';
				
	public function getGroupsAll($org)
	{
		$groups = $this
		->findAll();
		
		//if (!$org["IsAdmin"])
		//{
			$groups = $this
			->where(['OrganizationNo' => $org['OrganizationNo']])
			->findAll();
		//}
		
		return $groups;
	}
	
	public function getEnableGroups($org)
	{
		$groups = $this
		->where(['Status' => 1])
		->findAll();
		//if (!$org["IsAdmin"])
		//{
			$groups = $this
			->where(['OrganizationNo' => $org['OrganizationNo']])
			->where(['Status' => 1])
			->findAll();
		//}
		
		
		return $groups;
	}
	
	public function filterGroup($findTitle, $filterStatus = null, $startPos = 0, $count = 10, $orderType = 'CreateTime', $orderBy = 'DESC')
	{
		if ($filterStatus != null){
			$this->where([
				'Status' => 1,
			]);
		}
		$list = $this
		->orderBy($orderType, $orderBy)
		->limit($count, $startPos)
		->like('CONCAT(GroupName)', $findTitle)
		->get()
		->getResult();

		$count = $this
		->orderBy($orderType, $orderBy)
		->limit($count, $startPos)
		->like('CONCAT(GroupName)', $findTitle)
		->countAllResults();

		return [
			'List' => $list,
			'Count' => $count,
		];
	}

	public function getGroup($GroupNo)
	{
		$this->where([
			'GroupNo' => $GroupNo,
		]);
		
		$data = $this->asArray()->first();
		
		return $data;
	}
	
	public function addGroup($data)
	{
		$exist = $this->where(
			[
				'GroupNo' => $data["GroupNo"],
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
}