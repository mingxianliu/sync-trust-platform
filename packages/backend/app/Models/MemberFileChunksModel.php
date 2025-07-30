<?php

namespace App\Models;

use CodeIgniter\Model;

class MemberFileChunksModel extends Model
{
	protected $table = 'memberfilechunks';
	protected $primaryKey = 'ID';
	protected $allowedFields = ['ID', 'MemberNo', 'FileNo', 'Sort', 'Files', 'Status',
		 'CreateTime', 'UpdateTime', 'CreateUser', 'UpdateUser'];
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

	public function getFileByNo($no)
	{
		$list = $this->where('FileNo', $no)->orderBy('Sort', 'ASC')->get()->getResult();
		
		return $list;
	}
}