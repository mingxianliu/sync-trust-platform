<?php

namespace App\Models;

use CodeIgniter\Model;

class OrganizationsModel extends Model
{
	protected $table = 'organizations';
	protected $primaryKey = 'ID';
	protected $allowedFields = ['ID', 'OrganizationNo', 'OrganizationName',
					'IsAdmin', 'IsSend', 'IsReceive', 'Status', 'CreateTime', 'UpdateTime',
					'GroupLimit', 'MemberLimit', 'CreateUser', 'UpdateUser'
				];
	protected $useTimestamps = true;
	protected $createdField = 'CreateTime';
	protected $updatedField = 'UpdateTime';
				
	public function getOrganizationsAll($name, $mode, $order, $desc)
	{
		$organizationsTASA = $this->where(['OrganizationNo' => 'TASA'])->orderBy('Status', 'DESC')->get()->getResult();
		
		$this->where(['OrganizationNo <>' => 'TASA']);
		if (!empty($name))
		{
			$this->like(['OrganizationName' => $name]);
		}
		
		if (!empty($mode))
		{
			if ($mode == "send")
			{
				$this->where(['IsSend' => 1]);
			}
			else
			{
				$this->where(['IsReceive' => 1]);
			}
		}
		
		$this->orderBy('Status', 'DESC');
		if (!empty($order))
		{
			$this->orderBy($order, $desc);
		}
		
		$organizationsOthers = $this->get()->getResult();
		
		$organizations = array_merge($organizationsTASA, $organizationsOthers);
		
		return $organizations;
	}
	
	public function getEnableOrganizations($name, $mode, $order, $desc)
	{
		$this->where(['Status' => 1]);
		if (!empty($mode))
		{
			if ($mode == "send")
			{
				$this->where(['IsSend' => 1]);
			}
			else
			{
				$this->where(['IsReceive' => 1]);
			}
		}
		
		if (!empty($name))
		{
			$this->like(['OrganizationName' => $name]);
		}
		
		if (!empty($order))
		{
			$this->orderBy($order, $desc);
		}
		
		$organizations = $this->get()->getResult();

		
		return $organizations;
	}
	
	public function filterOrganization($findTitle, $filterStatus = null, $startPos = 0, $count = 10, $orderType = 'CreateTime', $orderBy = 'DESC')
	{
		if ($filterStatus != null){
			$this->where([
				'Status' => 1,
			]);
		}
		$list = $this
		->orderBy($orderType, $orderBy)
		->limit($count, $startPos)
		->like('CONCAT(OrganizationName)', $findTitle)
		->get()
		->getResult();

		$count = $this
		->orderBy($orderType, $orderBy)
		->limit($count, $startPos)
		->like('CONCAT(OrganizationName)', $findTitle)
		->countAllResults();

		return [
			'List' => $list,
			'Count' => $count,
		];
	}

	public function getOrganization($OrgNo)
	{
		$this->where([
			'OrganizationNo' => $OrgNo,
		]);
		
		$data = $this->asArray()->first();
		
		return $data;
	}
	
	public function addOrganization($data)
	{
		$exist = $this->where(
			[
				'OrganizationNo' => $data["OrganizationNo"],
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