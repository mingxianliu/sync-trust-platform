<?php

namespace App\Models;

use CodeIgniter\Model;

class HomesModel extends Model
{
	protected $table = 'adminuser';
	protected $primaryKey = 'ID';
	protected $allowedFields = ['ID', 'UserNo', 'UserAcc', 'UserPwd', 'UserName', 'UserPowder', 'Status', 'CreateTime', 'UpdateTime', 'CreateUser', 'UpdateUser'];
	
	public function getUserByAccount($account)
	{
		return $this->asArray()->where(['UserAcc' => $account])->first();
	}
	
}