<?php

namespace App\Models;

use CodeIgniter\Model;

class OrganizationMappingsModel extends Model
{
	protected $table = 'organizationmappings';
	protected $primaryKey = 'ID';
	protected $allowedFields = ['ID', 'Sender', 'Receiver', 'CreateTime', 'CreateUser'];
	protected $useTimestamps = false;
				
	public function getOrganizationMappings($type, $no, $order, $desc)
	{
		$this->join('organizations senderOrg', 'organizationmappings.Sender = senderOrg.OrganizationNo AND senderOrg.Status = 1');
		$this->join('organizations receiverOrg', 'organizationmappings.Receiver = receiverOrg.OrganizationNo AND receiverOrg.Status = 1');
		$this->select('senderOrg.OrganizationNo senderOrgNo, senderOrg.OrganizationName senderOrgName');
		$this->select('receiverOrg.OrganizationNo receiverOrgNo, receiverOrg.OrganizationName receiverOrgName');
		if ($type == "sender")
		{
			$this->where(['Sender' => $no]);
		}
		else
		{
			$this->where(['Receiver' => $no]);
		}
		
		if (!empty($order))
		{
			$this->orderBy($order, $desc);
		}
		
		$organizations = $this->get()->getResult();

		return $organizations;
	}
	
	public function addOrganizationMappings($data)
	{
		$ret = $this->insert($data);
		
		if ($ret)
		{
			return $this->insertID();
		}
		
		return $ret;
	}
}