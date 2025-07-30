<?php
namespace App\Controllers;
use App\Models\MembersModel;
use App\Models\OrganizationsModel;
use App\Models\OrganizationMappingsModel;
use App\Models\GroupsModel;
use App\Models\MemberFilesModel;
use App\Models\MemberFileChunksModel;
use App\Models\DownloadRecordsModel;
use App\Models\ChangeKeyRecordsModel;
use App\Models\RsaModel;
use Config\ApiConfig;
use Exception;
use rannmann\PhpIpfsApi\IPFS;
class Api extends BaseController
{
	public function loginMember()
	{
		$validation = $this->validate([
			'Acc' => 'required',
			'Pwd' => 'required',
		], [
			'Acc' => [
				'required' => '會員帳號未輸入',
			],
			'Pwd' => [
				'required' => '會員密碼未輸入',
			],
		]);
		if ($validation) {
			$acc = $this->request->getVar('Acc');
			$pwd = $this->request->getVar('Pwd');
			$model = new MembersModel();
			$member = $model->getEnableMemberByAccount($acc);
			if (empty($member)) {
				return $this->returnJson(200, [
					'Result' => false,
					'Message' => '會員不存在',
				]);
			} else {
				if ($member['MemberPwd'] == hash('sha256', $pwd) || $member['MemberPwd'] == hash('sha256', TokenState . $pwd . TokenState)) {
					$token_info = [
						'ID' => $member['ID'],
						'MemberAcc' => $member['MemberAcc'],
						'MemberName' => $member['MemberName'],
						'MemberNo' => $member['MemberNo'],
						'MemberPwd' => $member['MemberPwd'],
						'MemberEmail' => $member['Email'],
						'LogoutTime' => $member['LogoutTime'],
						'ChangePWTime' => $member['ChangePWTime'],
						'IsSend' => $member['IsSend'],
						'IsReceive' => $member['IsReceive'],
						'IsAdmin' => $member['IsAdmin'],
						'IsAdminOrg' => $member['IsAdminOrg'],
						'OrgNo' => $member['OrganizationNo'],
						'UserRole' => 'Member',
					];
					$data = array(
						'Data' => $member,
						'Token' => openssl_encrypt(json_encode($token_info), 'AES-128-CTR', TokenKey, 0, TokenIV),
					);

					return $this->returnJson(200, [
						'Result' => true,
						'Message' => $data,
					]);
				} else {
					return $this->returnJson(200, [
						'Result' => false,
						'Message' => '帳號或密碼錯誤',
					]);
				}
			}
		} else {
			return $this->returnJson(200, [
				'Result' => false,
				'Errors' => \Config\Services::validation()->getErrors(),
			]);
		}
	}

	public function logoutMember()
	{
		$model = new MembersModel();
		$result = $model->where('MemberNo', json_decode(TokenInfo)->MemberNo)
		->set('LogoutTime', 'now()', false)
		->update();
		return $this->returnJson(200, [
			'Result' => $result,
		]);
	}

	public function forgetMember()
    {
		$emailAddr = $this->request->getVar('Email');
		
		if (empty($emailAddr))
		{
			return $this->returnJson(400, [
				'Result' => false,
				'message' => '請填上信箱',
			]);
		}
		else
		{
			$membersModel = new MembersModel();
			$membersData = $membersModel->where('Email', $emailAddr)->get()->getResult();
			if (count($membersData) == 0)
			{
				return $this->returnJson(400, [
					'Result' => false,
					'message' => '帳號不存在',
				]);
			}
			$memberNo = $membersData[0]->MemberNo;
			$updateTime = $membersData[0]->UpdateTime;
			
			$email = \Config\Services::email();
			$email->setFrom('service@ioneit.com', 'APX');
			$email->setTo($emailAddr);
			$email->setBCC('service@ioneit.com');

			$resetKey = openssl_encrypt(json_encode([
				'MemberNo' => $memberNo,
				'UpdateTime' => $updateTime,
				'KeyCreateTime' => time(),
			], false), 'AES-128-CTR', ResetPassKey, 0, ResetPassIV);

			$email->setSubject('APX - 密碼重設通知');
			$email->setMessage('收到請求重設密碼，請在24小時以內 <a href="' . base_url('resetpassword?' . urlencode(base64_encode($resetKey))) . '">重設密碼</a>。');

			return $this->returnJson(200, [
				'Result' => $email->send(),
			]);
		}
    }
    
	public function resetPassword()
    {
		$resetKey = $this->request->getVar('ResetKey');
		$password = $this->request->getVar('Password');
		if (empty($resetKey))
		{
			return $this->returnJson(400, [
				'Result' => false,
				'message' => '未提供密鑰',
			]);
		}
		
		if (empty($password))
		{
			return $this->returnJson(400, [
				'Result' => false,
				'message' => '密碼未輸入',
			]);
		}
		
		$resetKey = json_decode(openssl_decrypt(base64_decode($resetKey), 'AES-128-CTR', ResetPassKey, 0, ResetPassIV));
		if (empty($resetKey))
		{
			return $this->returnJson(400, [
				'Result' => false,
				'message' => '無效的重設密鑰',
			]);
		}
		
		if ((time() - $resetKey->KeyCreateTime) > 86400)
		{
			return $this->returnJson(400, [
				'Result' => false,
				'message' => '已超過24小時，無法使用',
			]);
		}
		
		$membersModel = new MembersModel();
		if (count($membersModel->where([
				'MemberNo' => $resetKey->MemberNo,
				'UpdateTime' => $resetKey->UpdateTime,
			])->get()->getResult()) == 0)
		{
			return $this->returnJson(400, [
				'Result' => false,
				'message' => '重設連結已失效',
			]);
		}
		
		$result = $membersModel->where([
			'MemberNo' => $resetKey->MemberNo,
		])->set([
			'MemberPwd' => hash('sha256', $this->request->getVar('Password')),
			'ChangePWTime' => date("Y-m-d h:i:s"),
		])->update();

		return $this->returnJson(200, [
			'Result' => $result,
		]);
    }
	
	public function filterMemberByOrg()
    {
		$model = new MembersModel();
		$tempOrgNo = $this->request->getVar("OrgNo");
		$tempOrgName = $this->request->getVar("OrgName");
		$exclude = $this->request->getVar("Exclude");
		$tempOrder = $this->request->getVar('Order');
		$tempDesc = $this->request->getVar('Desc');
		$memberNo = json_decode(TokenInfo)->MemberNo;
		$tempMemberName = $this->request->getVar("MemberName");
		$order = 'ID';
		if (!empty($tempOrder))
		{
			$order = $tempOrder;
		}
		$desc = "DESC";
		if (!empty($tempDesc))
		{
			$desc = $tempDesc;
		}
		$orgNo = "";
		if (!empty($tempOrgNo))
		{
			$orgNo = $tempOrgNo;
		}
		$orgName = "";
		if (!empty($tempOrgName))
		{
			$orgName = $tempOrgName;
		}
		$memberName = "";
		if (!empty($tempMemberName))
		{
			$memberName = $tempMemberName;
		}
		
		$list = $model->filterMemberByOrg($orgNo, $orgName, $memberNo, $memberName, $exclude, $order, $desc);

		return $this->returnJson(200, [
			'Result' => true,
			'List' => $list,
		]);
    }
	
	public function filterMemberByGroup()
    {
		$model = new MembersModel();
		$groupNo = $this->request->getVar("GroupNo");
		$exclude = $this->request->getVar("Exclude");
		#$model->select("MemberNo, MemberName");
		if(empty($exclude))
		{
			$model->where([
				'GroupNo' => $groupNo,
				'Status' => 1
			]);
		}
		else
		{
			$model->where([
				'GroupNo' => $groupNo,
				'MemberNo <>' => json_decode(TokenInfo)->MemberNo,
				'Status' => 1
			]);
		}
		
		$list = $model->get()->getResultArray();

		return $this->returnJson(200, [
			'Result' => true,
			'List' => $list,
		]);
    }
	
	public function createMemberPrivateKey()
    {
		$model = new MembersModel();
		$privatePwd = $this->request->getVar("privatePwd");
		$config = array(
			"digest_alg" => "sha256",
			"private_key_bits" => 2048,
			"private_key_type" => OPENSSL_KEYTYPE_RSA,
		);
		$privateKey = openssl_pkey_new($config);
		openssl_pkey_export($privateKey, $privateKeyPem);
		$publicKeyPem = openssl_pkey_get_details($privateKey)['key'];
		$publicKey = openssl_pkey_get_public($publicKeyPem);
		$pw = hash('sha256', TokenState.$privatePwd.TokenState);
		$publicKeyPem = trim($publicKeyPem, "\r\n");
		$privateKeyPem = trim($privateKeyPem, "\r\n");
		$data = array(
			'PublicKey' => $publicKeyPem,
			'PrivateKey' => $privateKeyPem,
			'PrivatePwd' => $pw
		);
		
		$memberNo = json_decode(TokenInfo)->MemberNo;
		$ret = $model->where([
			'MemberNo' => $memberNo,
		])->set($data)->update();
		
		if ($ret)
		{
			$record = array(
				'MemberNo' => $memberNo,
				'PublicKey' => $publicKeyPem,
				'PrivateKey' => $privateKeyPem,
				'PrivatePwd' => $pw
			);
			$this->addChangeKeyRecordData($record, 'member');
			
			$en_config = new \Config\Encryption();
			$json = array(
				"memberId" => $this->encodeAES($memberNo),
				"privateKey" => $this->encodeAES($privateKeyPem),
				"token" => $this->encodeAES($pw),
				"key" => $this->encodeAES($en_config->key),
				"iv" => $this->encodeAES($en_config->driver),
				"address" => $this->encodeAES("address")
			);
			
			return $this->returnJson(200, [
				'Result' => true,
				'json' => json_encode($json),
				'filename' => json_decode(TokenInfo)->MemberAcc.'.json'
			]);
		}
		else
		{
			return $this->returnJson(404, [
				'Result' => 'fail',
				'message' => '建立私鑰失敗',
			]);
		}
		
    }
	
	public function checkMemberPrivateKey()
    {
		$model = new MembersModel();
		$privatePwd = $this->request->getVar("privatePwd");
		$hashPwd = hash('sha256', TokenState.$privatePwd.TokenState);
		
		$json = $this->request->getVar("json");
		$json = str_replace("\\", "", $json);
		$json = json_decode($json, true);
		
		$json_member_id = $this->decodeAES($json["memberId"]);
		$json_private_key = $this->decodeAES($json["privateKey"]);
		$json_token = $this->decodeAES($json["token"]);
		$key = $this->decodeAES($json["key"]);
		$iv = $this->decodeAES($json["iv"]);
		$address = $this->decodeAES($json["address"]);
		
		$model->where([
			'MemberNo' => json_decode(TokenInfo)->MemberNo,
		]);
		
		$member = $model->get()->getResultArray();
		if ($json_member_id != json_decode(TokenInfo)->MemberNo)
		{
			return $this->returnJson(400, [
				'message' => "私鑰與使用者不符合",
			]);
		}
		
		if ($json_token != $hashPwd || $member[0]['PrivatePwd'] != $hashPwd)
		{
			return $this->returnJson(400, [
				'message' => "輸入密碼錯誤",
			]);
		}
		
		if ($json_private_key != $member[0]['PrivateKey'])
		{
			return $this->returnJson(400, [
				'message' => "金鑰錯誤",
			]);
		}
		
		
		return $this->returnJson(200, [
			'Result' => 1,
			'private_key' => $member[0]['PrivateKey'],
			'address' => $address,
		]);
		
    }
	
	public function filterMember()
    {
		$model = new MembersModel();
		$memberNo = $this->request->getVar("MemberNo");
		$model->where([
			'MemberNo' => $memberNo,
		]);
		
		$data = $model->get()->getResultArray();

		return $this->returnJson(200, [
			'Result' => true,
			'Data' => count($data) > 0 ? $data[0] : null,
		]);
    }
	
	public function filterMemberPublicKey()
    {
		$model = new MembersModel();
		$memberNo = $this->request->getVar("MemberNo");
		$model->select("MemberNo, PublicKey");
		$model->where([
			'MemberNo' => $memberNo,
		]);
		
		$data = $model->get()->getResultArray();

		return $this->returnJson(200, [
			'Result' => true,
			'Data' => $data,
		]);
    }
	
	public function getMemberData()
    {
		$memberNo = $this->request->getVar('MemberNo');
		$model = new MembersModel();
		$data = $model->where([
			'MemberNo' => $memberNo,
		])->get()->getResult();
		return $this->returnJson(200, [
			'Result' => true,
			'Message' => count($data) == 1 ? $data[0] : null,
		]);
    }

	public function getMemberList()
    {
		$model = new MembersModel();
		$isAdmin = json_decode(TokenInfo)->IsAdmin;
		$isAdminOrg = json_decode(TokenInfo)->IsAdminOrg;
		$orgNo = json_decode(TokenInfo)->OrgNo;
		$tempOrder = $this->request->getVar('Order');
		$tempDesc = $this->request->getVar('Desc');
		$order = 'ID';
		if (!empty($tempOrder))
		{
			$order = $tempOrder;
		}
		$desc = "DESC";
		if (!empty($tempDesc))
		{
			$desc = $tempDesc;
		}
		$members = $model->getMemberAll($orgNo, $isAdminOrg, $isAdmin, $order, $desc);
		$page = $this->request->getVar('Page');
		$count = $this->request->getVar('Count');
		
		if (empty($page))
		{
			$page = 0;
		}
		
		if (empty($count))
		{
			$count = 8;
		}
		
		
		return $this->returnJson(200, [
			'Result' => true,
			'Members' => array_slice($members, $page * $count, $count),
			'Page' => $page,
			'Count' => $count,
			'Total' => count($members)
		]);
    }
	
	public function addMemberData()
    {
		$model = new MembersModel();
		$memberAcc = $this->request->getVar('MemberAcc');
		$memberEmail = $this->request->getVar('Email');
		$orgNo = $this->request->getVar('OrgNo');
		if (empty($memberAcc))
		{
			return $this->returnJson(200, [
				'Result' => false,
				'Errors' => [
					'MemberAcc' => '帳號不可為空',
				],
			]);
		}
		
		if (empty($orgNo))
		{
			return $this->returnJson(200, [
				'Result' => false,
				'Errors' => [
					'MemberAcc' => '組織不可為空',
				],
			]);
		}
		
		if (count($model->where('MemberAcc', $memberAcc)->get()->getResult()) > 0)
		{
			return $this->returnJson(200, [
				'Result' => false,
				'Errors' => [
					'MemberAcc' => '帳號已使用',
				],
			]);
		}
		
		if (count($model->where('Email', $memberEmail)->get()->getResult()) > 0)
		{
			return $this->returnJson(200, [
				'Result' => false,
				'Errors' => [
					'MemberEmail' => 'Email已使用',
				],
			]);
		}
		
		$modelOrg = new OrganizationsModel();
		$org = $modelOrg->getOrganization($orgNo);
		$limit = count($model->where('OrganizationNo', $orgNo)->get()->getResult());
		
		if ((int)$org['MemberLimit'] <= $limit)
		{
			return $this->returnJson(200, [
				'Result' => false,
				'Errors' => [
					'MemberLimit' => '組織會員已達最大限制',
				],
			]);
		}

		// $pic = $this->request->getFile('MemberPic');
		// $filepath = "";
			
		// if (! empty($pic->getName()))
		// {
			// $validationRule = [
				// 'MemberPic' => [
					// 'label' => 'Image File',
					// 'rules' => 'uploaded[MemberPic]'
						// . '|is_image[MemberPic]'
						// . '|mime_in[MemberPic,image/jpg,image/jpeg,image/gif,image/png,image/webp]'
						// . '|max_size[MemberPic,100]'
				// ],
			// ];
			
			// if (! $this->validate($validationRule)) {
				// $errors = $this->validator->getErrors();
			// }
			
			// if (! $pic->hasMoved()) {
				// $filepath = $pic->store();
			// } else {
				// $errors = 'The file has already been moved.';
			// }
		// }
		
		$result = $model->insert([
			'MemberNo' =>  hash('sha256', 'MEMBER_' . time() . '_' . rand(0, 999999)),
			'MemberAcc' => $memberAcc,
			'MemberPwd' => hash('sha256', $this->request->getVar('MemberPwd')),
			'MemberName' => $this->request->getVar('MemberName'),
			'OrganizationNo' => $orgNo,
			'GroupNo' => $this->request->getVar('GroupNo'),
			'IsAdmin' => $this->request->getVar('IsAdmin'),
			'IsSend' => $org["IsSend"],
			'IsReceive' => $org["IsReceive"],
			'Status' => $this->request->getVar('Status'),
			'Email' => $memberEmail,
			'CreateUser' => json_decode(TokenInfo)->MemberNo,
			'UpdateUser' => json_decode(TokenInfo)->MemberNo,
		]) > 0;
		
		return $this->returnJson(200, [
			'Result' => $result,
		]);
    }
	
	public function updateMemberData()
    {
		$model = new MembersModel();
		$id = $this->request->getVar('MemberId');
		$memberAcc = $this->request->getVar('MemberAcc');
		if (count($model->where(['MemberAcc' => $memberAcc, 'ID <>' => $id])->get()->getResult()) > 0)
		{
			return $this->returnJson(200, [
				'Result' => false,
				'Errors' => [
					'MemberAcc' => '帳號已使用',
				],
			]);
		}
		
		$orgNo = $this->request->getVar('OrgNo');
		$modelOrg = new OrganizationsModel();
		$org = $modelOrg->getOrganization($orgNo);
		$limit = count($model->where(['OrganizationNo' => $orgNo, 'ID <>' => $id])->get()->getResult());
		
		if ((int)$org['MemberLimit'] <= $limit)
		{
			return $this->returnJson(200, [
				'Result' => false,
				'Errors' => [
					'MemberLimit' => '組織會員已達最大限制',
				],
			]);
		}
		
		$data = array(
			'MemberAcc' => $memberAcc,
			'MemberName' => $this->request->getVar('MemberName'),
			'OrganizationNo' => $orgNo,
			'Email' => $this->request->getVar('Email'),
			'GroupNo' => $this->request->getVar('GroupNo'),
			'Status' => $this->request->getVar('Status'),
			'IsAdmin' => $this->request->getVar('IsAdmin'),
			'IsSend' => $this->request->getVar('IsSend'),
			'IsReceive' => $this->request->getVar('IsReceive'),
			'UpdateUser' => json_decode(TokenInfo)->MemberNo,
		);
		
		$pwd = $this->request->getVar('MemberPwd');
		if (!empty($pwd))
		{
			$data['MemberPwd'] = hash('sha256', $pwd);
			$data['ChagnePWTime'] = date("Y-m-d h:i:s");
		}
		
		$ret = $model->where(['ID' => $id])->set($data)->update();
		
		return $this->returnJson(200, [
			'Result' => $ret,
		]);
    }
	
	public function updateMemberPicAndPwd()
    {
		$model = new MembersModel();
		$filepath = "";
		$img = $this->request->getVar('MemberPic');
		if (!empty($img))
		{
			$today = date("Ymd");
			$folderPath = WRITEPATH.'uploads/';
			$image_parts = explode(";base64,", $img);
			$image_type_aux = explode("image/", $image_parts[0]);
			$image_type = $image_type_aux[1];
			$image_base64 = base64_decode($image_parts[1]);
			if (!is_dir($folderPath.$today))
			{
				mkdir($folderPath.$today, 0777, TRUE);
			}
			$fileName = $today.'/'.uniqid() . '.png';
			$file = $folderPath . $fileName;
			file_put_contents($file, $image_base64);
			$filepath = $fileName;
		}
		
		$oldPwd = $this->request->getVar('OldPwd');
		$newPwd = $this->request->getVar('NewPwd');
		$data = array(
			'UpdateUser' => json_decode(TokenInfo)->MemberNo,
		);
		
		if (!empty($oldPwd) && !empty($newPwd))
		{
			$ret = $model->where(['MemberNo' => json_decode(TokenInfo)->MemberNo])->first();
			if ($ret['MemberPwd'] == hash('sha256', $oldPwd) || $ret['MemberPwd'] == hash('sha256', TokenState . $oldPwd . TokenState))
			{
				$data['MemberPwd'] = hash('sha256', $newPwd);
				$data['ChangePWTime'] = date('Y-m-d h:i:s');
			}
			else
			{
				return $this->returnJson(200, [
					'Result' => false,
					'Error' => '原密碼不正確',
				]);
			}
		}
		
		if (! empty($filepath))
		{
			$data['MemberPic'] = $filepath;
		}
		
		$ret = $model->where(['MemberNo' => json_decode(TokenInfo)->MemberNo])->set($data)->update();
		
		return $this->returnJson(200, [
			'Result' => $ret,
		]);
    }
	
	public function getOrganizationMappingsList()
    {
		$model = new OrganizationMappingsModel();
		$type = $this->request->getVar('Type');
		$orgNo = $this->request->getVar('OrgNo');
		//$order = $this->request->getVar('Order');
		//$desc = $this->request->getVar('Desc');
		if (empty($order))
		{
			if ($type == "sender")
			{
				$order = 'senderOrg.OrganizationName';
			}
			else
			{
				$order = 'receiverOrg.OrganizationName';
			}
		}
		
		if (empty($desc))
		{
			$desc = 'ASC';
		}
		
		$organizations = $model->getOrganizationMappings($type, $orgNo, $order, $desc);
		$page = $this->request->getVar('Page');
		$count = $this->request->getVar('Count');
		
		if (empty($page))
		{
			$page = 0;
		}
		
		if (empty($count))
		{
			$count = 20;
		}
		
		
		return $this->returnJson(200, [
			'Result' => true,
			'Message' => array_slice($organizations, $page * $count, $count),
			'Page' => $page,
			'Count' => $count,
			'Total' => count($organizations)
		]);
    }
	
	public function addOrganizationMappingsData()
    {
		$model = new OrganizationMappingsModel();
		$sender = $this->request->getVar('Sender');
		$receivers = $this->request->getVar('Receivers[]');
		$ret = $model->where('Sender', $sender)->delete();
		
		foreach ($receivers as $receiver)
		{
			$result = $model->insert([
				'Sender' => $sender,
				'Receiver' => $receiver,
				'CreateTime' => date('Y-m-d H:i:s'),
				'CreateUser' => json_decode(TokenInfo)->MemberNo,
			]) > 0;
		}
		return $this->returnJson(200, [
			'Result' => $result,
		]);
    }

	public function getOrganizationList()
    {
		$model = new OrganizationsModel();
		$type = $this->request->getVar('Type');
		$name = $this->request->getVar('Name');
		$order = $this->request->getVar('Order');
		$desc = $this->request->getVar('Desc');
		$mode = $this->request->getVar('Mode');
		if (empty($mode))
		{
			$mode = '';
		}
		
		if (empty($name))
		{
			$name = '';
		}
		
		if (empty($order))
		{
			$order = 'ID';
		}
		
		if (empty($desc))
		{
			$desc = 'Desc';
		}
		
		if (empty($type))
			$organizations = $model->getOrganizationsAll($name, $mode, $order, $desc);
		else
			$organizations = $model->getEnableOrganizations($name, $mode, $order, $desc);
		$page = $this->request->getVar('Page');
		$count = $this->request->getVar('Count');
		
		if (empty($page))
		{
			$page = 0;
		}
		
		if (empty($count))
		{
			$count = 8;
		}
		
		
		return $this->returnJson(200, [
			'Result' => true,
			'Message' => array_slice($organizations, $page * $count, $count),
			'Page' => $page,
			'Count' => $count,
			'Total' => count($organizations)
		]);
    }
	
	public function addOrganizationData()
    {
		$model = new OrganizationsModel();
		$orgNo = $this->request->getVar('OrgNo');
		if (count($model->where('OrganizationNo', $orgNo)->get()->getResult()) > 0)
		{
			return $this->returnJson(200, [
				'Result' => false,
				'Errors' => [
					'OrganizationNo' => '組織代號已使用',
				],
			]);
		}
		
		$groupLimit = $this->request->getVar('GroupLimit');
		$memberLimit = $this->request->getVar('MemberLimit');
		if (empty($groupLimit))
		{
			$groupLimit = 5;
		}
		
		if (empty($memberLimit))
		{
			$memberLimit = 10;
		}
		
		$result = $model->insert([
			'OrganizationNo' => $orgNo,
			'OrganizationName' => $this->request->getVar('OrgName'),
			'GroupLimit' => $groupLimit,
			'MemberLimit' => $memberLimit,
			'Status' => $this->request->getVar('Status'),
			'CreateUser' => json_decode(TokenInfo)->MemberNo,
			'UpdateUser' => json_decode(TokenInfo)->MemberNo,
		]) > 0;
		
		return $this->returnJson(200, [
			'Result' => $result,
		]);
    }
	
	public function updateOrganizationData()
    {
		$model = new OrganizationsModel();
		$modelMembers = new MembersModel();
		$modelGroups = new GroupsModel();
		$id = $this->request->getVar('OrgId');
		$orgNo = $this->request->getVar('OrgNo');
		$exist = $model->where([
			'OrganizationNo' => $orgNo,
			'ID <>' => $id
		])
		->get()->getResult();
		
		if (count($exist) > 0)
		{
			return $this->returnJson(200, [
				'Result' => false,
				'Errors' => [
					'OrganizationNo' => '組織代號已使用',
				],
			]);
		}
		
		$orgInit = $model->where([
			'ID' => $id
		])
		->get()->getResult();
		
		$stateInit = $orgInit[0]->Status;
		$isAdmin = $this->request->getVar('IsAdmin');
		$isSend = $this->request->getVar('IsSend');
		$isReceive = $this->request->getVar('IsReceive');
		$state = $this->request->getVar('Status');
		$groupLimit = $this->request->getVar('GroupLimit');
		$memberLimit = $this->request->getVar('MemberLimit');
		if (empty($groupLimit))
		{
			$groupLimit = 5;
		}
		
		if (empty($memberLimit))
		{
			$memberLimit = 10;
		}
		
		$data = array(
			'OrgNo' => $orgNo,
			'OrganizationName' => $this->request->getVar('OrgName'),
			'Status' => $state,
			'IsAdmin' => $isAdmin,
			'IsSend' => $isSend,
			'IsReceive' => $isReceive,
			'GroupLimit' => $groupLimit,
			'MemberLimit' => $memberLimit,
			'UpdateUser' => json_decode(TokenInfo)->MemberNo,
		);
		
		
		$ret = $model->where(['ID' => $id])->set($data)->update();
		
		// 依組織停用權限
		if(empty($isAdmin))
		{
			$retMember = $modelMembers->where(['OrganizationNo' => $orgNo, 'Status' => 1])->set(array('IsAdmin' => 0))->update();
			$retGroup = $modelGroups->where(['OrganizationNo' => $orgNo, 'Status' => 1])->set(array('IsAdmin' => 0))->update();
		}
		
		if(empty($isSend))
		{
			$retMember = $modelMembers->where(['OrganizationNo' => $orgNo, 'Status' => 1])->set(array('IsSend' => 0))->update();
			$retGroup = $modelGroups->where(['OrganizationNo' => $orgNo, 'Status' => 1])->set(array('IsSend' => 0))->update();
		}
		
		if(empty($isReceive))
		{
			$retMember = $modelMembers->where(['OrganizationNo' => $orgNo, 'Status' => 1])->set(array('IsReceive' => 0))->update();
			$retGroup = $modelGroups->where(['OrganizationNo' => $orgNo, 'Status' => 1])->set(array('IsReceive' => 0))->update();
		}
		
		if(empty($state) || (empty($stateInit) && $state))
		{
			$roles = array(
				'IsSend' => $isSend,
				'IsReceive' => $isReceive,
				'IsAdmin' => $isAdmin,
				'Status' => $state,
			);
			
			$retMember = $modelMembers->where(['OrganizationNo' => $orgNo])->set($roles)->update();
			$retGroup = $modelGroups->where(['OrganizationNo' => $orgNo])->set($roles)->update();
		}
		
		return $this->returnJson(200, [
			'Result' => $ret,
		]);
    }
	
	public function createSystemPrivateKey()
    {
		$model = new MembersModel();
		$privatePwd = 'Aa123456';
		$config = array(
			"digest_alg" => "sha256",
			"private_key_bits" => 2048,
			"private_key_type" => OPENSSL_KEYTYPE_RSA,
		);
		$privateKey = openssl_pkey_new($config);
		openssl_pkey_export($privateKey, $privateKeyPem);
		$publicKeyPem = openssl_pkey_get_details($privateKey)['key'];
		$publicKey = openssl_pkey_get_public($publicKeyPem);
		$pw = hash('sha256', TokenState.$privatePwd.TokenState);
		$publicKeyPem = trim($publicKeyPem, "\r\n");
		$privateKeyPem = trim($privateKeyPem, "\r\n");
		$data = array(
			'PublicKey' => $publicKeyPem,
			'PrivateKey' => $privateKeyPem,
			'PrivatePwd' => $pw
		);
		
		$memberNo = $this->request->getVar('MemberNo');
		$modelFiles = new MemberFilesModel();
		
		// $encodeList = $modelFiles->getFileByEncodeNo($memberNo);
		// $ret = false;
		// if (empty($encodeList))
		// {
			// $ret = $model->where([
				// 'MemberNo' => $memberNo,
			// ])->set($data)->update();
		// }
		// else
		// {
			$member = $model->getMember($memberNo);
			$newMember = $member;
			unset($newMember['ID']);
			$newMemberNo = hash('sha256', 'MEMBER_' . time() . '_' . rand(0, 999999));
			$newMember['MemberNo'] = $newMemberNo;
			$newMember['CreateUser'] = $newMemberNo;
			$newMember['UpdateUser'] = $newMemberNo;
			$newMember['PublicKey'] = $publicKeyPem;
			$newMember['PrivateKey'] = $privateKeyPem;
			$newMember['PrivatePwd'] = $pw;
			$newMember['CreateTime'] = date('Y-m-d h:i:s');
			$newMember['UpdateTime'] = date('Y-m-d h:i:s');
			$ret = $model->insert($newMember);
			$model->where([
				'MemberNo' => $memberNo,
			])->set(['Status' => 0, 'DisableTime' => date('Y-m-d h:i:s')])->update();
			$memberNo = $newMemberNo;
		// }
		
		if ($ret)
		{
			$record = array(
				'MemberNo' => $memberNo,
				'PublicKey' => $publicKeyPem,
				'PrivateKey' => $privateKeyPem,
				'PrivatePwd' => $pw
			);
			$this->addChangeKeyRecordData($record, 'system');
		}
		
		return $this->returnJson(200, [
			'Result' => true,
		]);
		
    }
	
	public function getSystemKeyList()
    {
		$model = new MembersModel();
		$page = $this->request->getVar('Page');
		$count = $this->request->getVar('Count');
		
		if (empty($page))
		{
			$page = 0;
		}
		
		if (empty($count))
		{
			$count = 20;
		}
		
		$list = $model->getSystemKeyList($page, ($page * $count));
		
		return $this->returnJson(200, [
			'Result' => true,
			'List' => $list['List'],
			'Page' => $page,
			'Count' => $count,
			'Total' => $list['Count']
		]);
    }
	
	public function getSystemKeyDetail()
    {
		$model = new MemberFilesModel();
		$encodeNo = $this->request->getVar('MemberNo');
		$list = $model->getSystemKeyDetail($encodeNo);
		$newList  = array();
		$receiveList = array();
		$temp = array();
		if (!empty($list))
		{
			for ($i=0; $i < count($list); $i++)
			{
				$row = $list[$i];
				if ($i + 1 == count($list))
				{
					$temp = $row;
					$temp['receiveList'] = $receiveList;
					$newList[] = $temp;
					$receiveList = array();
				}
				else
				{
					$rowNext = $list[$i+1];
					if ($row['IPFSHash'] != $rowNext['IPFSHash'])
					{
						$temp = $row;
						$temp['receiveList'] = $receiveList;
						$newList[] = $temp;
						$receiveList = array();
					}
					else
					{
						$receiveList[] = $row;
					}
				}
			}
		}
		
		
		return $this->returnJson(200, [
			'Result' => true,
			'List' => $newList,
			'Total' => count($newList)
		]);
    }
	
	public function getMemberFileList()
	{
		$type = $this->request->getVar('type');
		$fileName = $this->request->getVar('FileName');
		$orgNo = $this->request->getVar('OrgNo');
		$memberNo = $this->request->getVar('MemberNo');
		$groupNo = $this->request->getVar('GroupNo');
		$state = $this->request->getVar('Status');
		$isAdmin = $this->request->getVar('IsAdmin');
		$model = new MemberFilesModel();
		$model->select("
			memberfiles.*,
			A.MemberName As 'SenderName',
			B.MemberName As 'ReceiveName',
			C.OrganizationNo As 'SenderOrgNo',
			C.OrganizationName As 'SenderOrgName',
			D.OrganizationNo As 'ReceiveOrgNo',
			D.OrganizationName As 'ReceiveOrgName'
		");
		$model->join('members A', 'memberfiles.MemberNo = A.MemberNo');
		$model->join('members B', 'memberfiles.MemberReceiveNo = B.MemberNo');
		$model->join('organizations C', 'A.OrganizationNo = C.OrganizationNo');
		$model->join('organizations D', 'B.OrganizationNo = D.OrganizationNo');
		$cond = array();
		$model->where([
			'memberfiles.Status >=' => 0,
		]);
		
		if ($state != "")
		{
			$model->where([
				'memberfiles.Status' => $state,
			]);
		}
		
		if (!empty($fileName))
		{
			$model->like([
				'memberfiles.FileName' => $fileName,
			]);
		}
		
		if ($type == "receive")
		{
			if (empty($isAdmin))
			{
				$model->where([
					'memberfiles.MemberReceiveNo' => json_decode(TokenInfo)->MemberNo,
				]);
			}
			
			if (!empty($orgNo))
			{
				$model->where([
					'C.OrganizationNo' => $orgNo,
				]);
			}
			
			if (!empty($memberNo))
			{
				$model->where([
					'memberfiles.MemberNo' => $memberNo,
				]);
			}
			
			if (!empty($groupNo))
			{
				$model->where([
					'A.GroupNo' => $groupNo,
				]);
			}
		}
		
		if ($type == "send")
		{
			if (empty($isAdmin))
			{
				$model->where([
					'memberfiles.MemberNo' => json_decode(TokenInfo)->MemberNo,
				]);
			}
			
			if (!empty($orgNo))
			{
				$model->where([
					'D.OrganizationNo' => $orgNo,
				]);
			}
			
			if (!empty($memberNo))
			{
				$model->where([
					'memberfiles.MemberReceiveNo' => $memberNo,
				]);
			}
			
			if (!empty($groupNo))
			{
				$model->where([
					'B.GroupNo' => $groupNo,
				]);
			}
		}
		
		$model->orderBy('memberfiles.ID DESC, memberfiles.FileName ASC, memberfiles.Version DESC');
		$list = $model->get()->getResultArray();
		$newList  = array();
		$receiveList = array();
		$temp = array();
		if (!empty($list))
		{
			for ($i=0; $i < count($list); $i++)
			{
				$row = $list[$i];
				if ($i + 1 == count($list))
				{
					$temp = $row;
					$temp['receiveList'] = $receiveList;
					$newList[] = $temp;
					$receiveList = array();
				}
				else
				{
					$rowNext = $list[$i+1];
					if ($row['IPFSHash'] != $rowNext['IPFSHash'])
					{
						$temp = $row;
						$temp['receiveList'] = $receiveList;
						$newList[] = $temp;
						$receiveList = array();
					}
					else
					{
						$receiveList[] = $row;
					}
				}
			}
		}
		
		
		return $this->returnJson(200, [
			'Result' => true,
			'List' => $newList,
		]);
	}

	public function addMemberFileData()
	{
		if (empty(json_decode(TokenInfo)->IsSend))
		{
			return $this->returnJson(401, [
				'Result' => false,
				'message' => "沒有發送權限",
			]);
		}
		else
		{
			$encodeNo = $this->request->getVar('EncodeNo');
			$fileName = $this->request->getVar('FileName');
			$fileSize = $this->request->getVar('FileSize');
			$blackHash = "";
			$IPFSHash = hash('sha256', 'IPFS_' . time() . '_' . rand(0, 999999));
			$filepath = date("Ymd")."/".uniqid().$fileName;
			$result = false;
			$fileNo = hash('sha256', 'FN_' . time() . '_' . rand(0, 999999));
			$memberReceiveNo = $this->request->getVar('MemberReceiveNo');
			$model = new MemberFilesModel();
			if (empty($fileSize))
			{
				$fileSize = 0;
			}
			$version = 1;
			$existFile = $model->getFileByName($fileName, $memberReceiveNo);
			if (!empty($existFile))
			{
				// if ((int) $existFile['FileSize'] == $fileSize)
				// {
					// $version = (int) $existFile['Version'];
				// }
				// else
				// {
					// $version = (int) $existFile['Version'] + 1;
				// }
				$version = (int) $existFile['Version'] + 1;
			}
			
			$result = $model->insert([
				'MemberNo' => json_decode(TokenInfo)->MemberNo,
				'FileNo' => $fileNo,
				'MemberReceiveNo' => $memberReceiveNo,
				'IPFSHash' =>  $IPFSHash,
				'Blockchain' =>  $blackHash,
				'FileName' => $fileName,
				'Files' => 'encrypt:' . $filepath,
				'FileSize' => $fileSize,
				'EncodeNo' => $encodeNo,
				'Version' => $version,
				'Status' => 0,
				'CreateUser' => json_decode(TokenInfo)->MemberNo,
				'UpdateUser' => json_decode(TokenInfo)->MemberNo,
			]) > 0;
			
			$modelDownload = new DownloadRecordsModel();
			$resultDownload = $modelDownload->insert([
				'MemberNo' => $memberReceiveNo,
				'FileNo' => $fileNo,
				'Blockchain' => $blackHash,
				'Status' => 0,
				'CreateUser' => $memberReceiveNo,
				'UpdateUser' => $memberReceiveNo,
			]);
			
			$otherFileNoList = array();
			$otherMemberReceiveList = $this->request->getVar('OtherMemberReceiveList[]');
			if (!empty($otherMemberReceiveList))
			{
				foreach ($otherMemberReceiveList as $otherReceiveNo)
				{
					if (!empty($otherReceiveNo))
					{
						$version = 1;
						$existFile = $model->getFileByName($fileName, $otherReceiveNo);
						if (!empty($existFile))
						{
							// if ((int) $existFile['FileSize'] == $fileSize)
							// {
								// $version = (int) $existFile['Version'];
							// }
							// else
							// {
								// $version = (int) $existFile['Version'] + 1;
							// }
							$version = (int) $existFile['Version'] + 1;
						}
						$otherFileNo = hash('sha256', 'FN_' . time() . '_' . rand(0, 999999));
						$otherResult = $model->insert([
							'MemberNo' => json_decode(TokenInfo)->MemberNo,
							'FileNo' => $otherFileNo,
							'MemberReceiveNo' => $otherReceiveNo,
							'IPFSHash' =>  $IPFSHash,
							'Blockchain' =>  $blackHash,
							'FileName' => $fileName,
							'Files' => 'encrypt:' . $filepath,
							'FileSize' => $fileSize,
							'EncodeNo' => $encodeNo,
							'Version' => $version,
							'Status' => 0,
							'CreateUser' => json_decode(TokenInfo)->MemberNo,
							'UpdateUser' => json_decode(TokenInfo)->MemberNo,
						]) > 0;
						
						$otherResultDownload = $modelDownload->insert([
							'MemberNo' => $otherReceiveNo,
							'FileNo' => $otherFileNo,
							'Blockchain' => $blackHash,
							'Status' => 0,
							'CreateUser' => $otherReceiveNo,
							'UpdateUser' => $otherReceiveNo,
						]);
						
						$otherFileNoList[] = $otherFileNo;
					}
				}
			}
			
			return $this->returnJson(200, [
				'Result' => $result,
				'fileNo' => $fileNo,
				'otherFileNo' => $otherFileNoList,
				'download' => $resultDownload
			]);
		}
	}
	
	public function addMemberFileChunkData()
	{
		$model = new MemberFileChunksModel();
		$modelFiles = new MemberFilesModel();
		$merge = $this->request->getVar('merge');
		$fileNo = $this->request->getVar('FileNo');
		$otherFileNo = $this->request->getVar('OtherFileNo[]');
		$result = false;
		$mergeError = 0;
					
		if (empty(json_decode(TokenInfo)->IsSend))
		{
			return $this->returnJson(401, [
				'Result' => false,
				'message' => "沒有發送權限",
			]);
		}
					
		if ($merge == "0") 
		{
			$files = $this->request->getVar('Files');
			$sort = $this->request->getVar('sort');
			$today = date("Ymd");
			$folderPath = WRITEPATH.'uploads/'.$today;
			$fileName = uniqid().'.tmp';
			$filePath = $folderPath.'/'. $fileName;
			if (!is_dir($folderPath))
			{
				mkdir($folderPath, 0777, TRUE);
			}
			file_put_contents($filePath, $files);
			
			// if (! empty($files->getName()))
			// {
				// if (! $files->hasMoved()) {
					// $filepath = $files->store();
				// } else {
					// $errors = 'The file has already been moved.';
				// }
			
				if (!empty($filePath))
				{
					$result = $model->insert([
						'MemberNo' => json_decode(TokenInfo)->MemberNo,
						'FileNo' => $fileNo,
						'Sort' => $sort,
						'Files' => $today.'/'.$fileName,
						'Status' => 0,
						'CreateUser' => json_decode(TokenInfo)->MemberNo,
						'UpdateUser' => json_decode(TokenInfo)->MemberNo,
					]) > 0;
				}
			// }
		}
		else if ($merge == "1")
		{
			$file = $modelFiles->getFileByNo($fileNo);
			$file['Files'] = preg_split('/^encrypt:/', $file['Files'])[1];
			$fileChunkCount = $this->request->getVar('chunkCount');
			$fileChunks = $model->getFileByNo($fileNo);
			#if (count($fileChunks) == $fileChunkCount)
			if (true)
			{
				$uploadPath = WRITEPATH."uploads/".$file['Files'];
				if (file_exists($uploadPath))
				{
					@unlink($uploadPath);
				}
				
				if (!$out = @fopen($uploadPath, "wb")) {
					return $this->returnJson(401, [
						'Result' => false,
						'message' => "無法寫入",
					]);
				}
				
				if ($fileChunkCount == "1")
				{
					if (flock($out, LOCK_EX) ) 
					{
						foreach($fileChunks as $chunk) 
						{
							$tmpFile = WRITEPATH."uploads/".$chunk->Files;
							if (!is_file($tmpFile))
							{
								continue;
							}
								
							if (!$in = @fopen($tmpFile, "rb")) 
							{
								break;
							}
							while ($buff = fread($in, 4096)) 
							{
								fwrite($out, base64_decode($buff));
							}
							@fclose($in);
							 //删除分片
							@unlink($tmpFile);
						}
						
						flock($out, LOCK_UN);
					}
					@fclose($out);
						
					// $fileExplode = explode("/", $file['Files']);
					// foreach($fileChunks as $chunk) 
					// {
						// $tmpFile = WRITEPATH."uploads/".$chunk->Files;
						// $fileInfo = new \CodeIgniter\Files\File($tmpFile);
						// $fileInfo->move(WRITEPATH . 'uploads/'.$fileExplode[0], $fileExplode[1]);
						
					// }
					$result = true;
					
				}
				else
				{
					while(!$result || $mergeError < 3)
					{
						if (flock($out, LOCK_EX) ) 
						{
							foreach($fileChunks as $chunk) 
							{
								$tmpFile = WRITEPATH."uploads/".$chunk->Files;
								if (!is_file($tmpFile))
								{
									continue;
								}
									
								if (!$in = @fopen($tmpFile, "rb")) 
								{
									break;
								}
								while ($buff = fread($in, 4096)) 
								{
									fwrite($out, base64_decode($buff));
								}
								@fclose($in);
								 //删除分片
								@unlink($tmpFile);
							}
							
							flock($out, LOCK_UN);
						}
						@fclose($out);
						
						if (filesize($uploadPath) > 0)
						{
							$result = true;
							$mergeError = 3;
						}
						else
						{
							$result = false;
							$mergeError++;
						}
					}
				}
			}
			else
			{
				return $this->returnJson(401, [
					'Result' => false,
					'message' => "分片總數不對，請重新上傳",
				]);
			}
			
			if (!$result)
			{
				// 合併失敗刪除檔案
				$ret = $modelFiles->delete($file['ID']);
			}

			$uploadPath = WRITEPATH."uploads/".$file['Files'];
			$ipfs = new IPFS("localhost", 8080, 5001);
			set_time_limit(0);
			$ipfs->setCurlTimeout(0);

			$modelMembers = new MembersModel();
			// 改成用系統公鑰加密
			$receiveMember = $modelMembers->getMember($file['EncodeNo']);

			$ipfsFilePath = $uploadPath . '.zip';
			$zip = new \ZipArchive();
			if($zip->open($ipfsFilePath, \ZIPARCHIVE::CREATE | \ZIPARCHIVE::OVERWRITE) === TRUE){
				
				$publicKeyPath = $uploadPath . '.public';
				file_put_contents($publicKeyPath, $receiveMember['PublicKey']);

				$zip->addFile($uploadPath, $file['FileName']);
				$zip->addFile($publicKeyPath, 'public.key');
				$zip->close();
			}

			$hash = $ipfs->addFromPath($ipfsFilePath);
			$modelFiles->updateIPFSHashById($fileNo, $hash);
			$blockchain_init = $file['Blockchain'];
			$blockchainTrans = $this->signFile($file);
			$blockchain = $this->getBlockchainInquire($blockchainTrans);
			#$no = $this->request->getVar("no");
			$modelFiles->updateBlockchainByNo($fileNo, $blockchain, $blockchainTrans);
			$modelDownload = new DownloadRecordsModel();
			$modelDownload->updateBlockchainByNo($fileNo, $blockchain, $blockchainTrans);

			$privateKeyPath = $uploadPath . '.private';
			file_put_contents($privateKeyPath, $receiveMember['PrivateKey']);

			unlink($publicKeyPath);
			unlink($ipfsFilePath);

			if (!empty($otherFileNo))
			{
				foreach ($otherFileNo as $otherNo)
				{
					if (!empty($otherNo))
					{
						$otherFile = $modelFiles->getFileByNo($otherNo);
						if (!$result)
						{
							// 合併失敗刪除檔案
							$ret = $modelFiles->delete($otherFile['ID']);
						}
						$modelFiles->updateIPFSHashById($otherNo, $hash);
					}
				}
			}
		}
		else if ($merge == "2")
		{
			$file = $modelFiles->getFileByNo($fileNo);
			// 合併取消刪除檔案
			$ret = $modelFiles->delete($file['ID']);
			
			if (!empty($otherFileNo))
			{
				foreach ($otherFileNo as $otherNo)
				{
					if (!empty($otherNo))
					{
						$otherFile = $modelFiles->getFileByNo($otherNo);
						$otherRet = $modelFiles->delete($otherFile['ID']);
					}
				}
			}
			
		}
		
		return $this->returnJson(200, [
			'Result' => $result,
		]);
	}
	
	public function getDownloadFileList()
	{
		$fileNo = $this->request->getVar('FileNo');
		$model = new MemberFilesModel();
		$model->select("
			A.MemberName As 'SenderName',
			B.MemberName As 'ReceiveName',
			C.OrganizationNo As 'SenderOrgNo',
			C.OrganizationName As 'SenderOrgName',
			D.OrganizationNo As 'ReceiveOrgNo',
			D.OrganizationName As 'ReceiveOrgName',
			memberfiles.CreateTime As 'CreateTime',
			memberfiles.FileName,
			memberfiles.Version,
			E.MemberNo, E.FileNo, E.Status,
			E.Blockchain, E.CreateUser, E.UpdateUser,
			(CASE WHEN E.Status = 0 THEN '' ELSE E.UpdateTime END) AS UpdateTime
		");
		$model->join('downloadrecords E', 'memberfiles.FileNo = E.FileNo');
		$model->join('members A', 'memberfiles.MemberNo = A.MemberNo');
		$model->join('members B', 'E.MemberNo = B.MemberNo');
		$model->join('organizations C', 'A.OrganizationNo = C.OrganizationNo');
		$model->join('organizations D', 'B.OrganizationNo = D.OrganizationNo');
		
		$model->where([
			'E.FileNo' => $fileNo,
		]);
		
		$model->where([
			'memberfiles.Status >=' => 0,
		]);
		
		$model->orderBy('E.ID', 'DESC');
		$list = $model->get()->getResultArray();
		$sql = $model->getLastQuery();
		
		return $this->returnJson(200, [
			'Result' => true,
			'List' => $list,
		]);
	}
	
	public function addDownloadRecordData()
	{
		$model = new DownloadRecordsModel();
		$modelFiles = new MemberFilesModel();
		$fileNo = $this->request->getVar('FileNo');
		$file = $modelFiles->getFileByNo($fileNo);
		$memberNo = json_decode(TokenInfo)->MemberNo;
		$status = $this->request->getVar('Status');
		if (empty($status))
		{
			$status = 1;
		}
		if ($file['MemberReceiveNo'] == $memberNo)
		{
			if($status >= $file['Status'])
			{
				$ret = $modelFiles->updateStatusById($fileNo, $status);
			}
		}
		
		$result = $model->insert([
			'MemberNo' => $memberNo,
			'FileNo' => $fileNo,
			'Blockchain' => $file['Blockchain'],
			'Status' => $status,
			'CreateUser' => $memberNo,
			'UpdateUser' => $memberNo,
		]) > 0;
		
		return $this->returnJson(200, [
			'Result' => $result,
		]);
	}
	
	public function ipfsGetLs()
	{
		$hash = $this->request->getVar("hash");
		$ipfs = new IPFS("localhost", 8080, 5001);
		$nodes = $ipfs->ls($hash);

		foreach ($nodes as $node) 
		{
			echo "Hash=".$node['Hash'];
			echo "Size=".$node['Size'];
			echo "Name=".$node['Name'];
		}
	}
	
	private function getBlockchainInquire($hashTrans)
	{
		$blockchain = "";
		
		while(empty($blockchain) || $blockchain == "None")
		{
			$param = array('transactionhash' => $hashTrans);
			$client = \Config\Services::curlrequest([
				'baseURI' => BlockChainUrl,
			]);
			$response = $client->request('POST', '/blockchain/inquire/', [
				'form_params' => $param,
			]);
			
			$data = str_replace(")\n", "", str_replace("AttributeDict(", "", $response->getBody()));
			$data = str_replace("),", ",", str_replace("HexBytes(", "", $data));
			$data = str_replace(")}", "}", $data);
			$data = str_replace("'", "", $data);
			$data = str_replace(" ", "", $data);
			$data = str_replace("}", "", $data);
			$data = str_replace("{", "", $data);
			$temps = explode(",", $data);
			foreach ($temps as $temp)
			{
				$items = explode(":", $temp);
				if ($items[0] == "blockHash")
				{
					$blockchain = $items[1];
				}
			}
		}
		
		return $blockchain;
	}
	
	public function sendInquire()
	{
		$hash = $this->request->getVar("Hash");
		$param = array('transactionhash' => $hash);
		$client = \Config\Services::curlrequest([
			'baseURI' => BlockChainUrl,
		]);
		$response = $client->request('POST', '/blockchain/inquire/', [
			'form_params' => $param,
		]);
		
		$data = str_replace(")\n", "", str_replace("AttributeDict(", "", $response->getBody()));
		$data = str_replace("),", ",", str_replace("HexBytes(", "", $data));
		$data = str_replace(")}", "}", $data);
		$data = str_replace("'", "", $data);
		$data = str_replace(" ", "", $data);
		$data = str_replace("}", "", $data);
		$data = str_replace("{", "", $data);
		$temps = explode(",", $data);
		$items = array();
		foreach ($temps as $temp)
		{
			$item = explode(":", $temp);
			$items[$item[0]] = $item[1];
		}
		
		return $this->returnJson(200, [
			"Result" => $items,
		]);
	}
	
	public function getGroupList()
    {
		$model = new GroupsModel();
		$modelOrg = new OrganizationsModel();
		$type = $this->request->getVar('Type');
		$orgNo = $this->request->getVar('OrgNo');
		if (empty($orgNo))
		{
			$orgNo = json_decode(TokenInfo)->OrgNo;
		}
		$org = $modelOrg->getOrganization($orgNo);
		if ($type == "all")
			$groups = $model->getGroupsAll($org);
		else
			$groups = $model->getEnableGroups($org);
		$page = $this->request->getVar('Page');
		$count = $this->request->getVar('Count');
		
		if (empty($page))
		{
			$page = 0;
		}
		
		if (empty($count))
		{
			$count = 8;
		}
		
		
		return $this->returnJson(200, [
			'Result' => true,
			'Message' => array_slice($groups, $page * $count, $count),
			'Page' => $page,
			'Count' => $count,
			'Total' => count($groups)
		]);
    }
	
	public function addGroupData()
    {
		$model = new GroupsModel();
		$modelOrg = new OrganizationsModel();
		$groupNo = $this->request->getVar('GroupNo');
		$orgNo = $this->request->getVar('OrgNo');
		$isAdmin = $this->request->getVar('IsAdmin');
		$isSend = $this->request->getVar('IsSend');
		$isReceive = $this->request->getVar('IsReceive');
		if (count($model->where('GroupNo', $groupNo)->get()->getResult()) > 0)
		{
			return $this->returnJson(200, [
				'Result' => false,
				'Errors' => [
					'GroupNo' => '群組代號已使用',
				],
			]);
		}
		
		if (empty($orgNo))
		{
			$orgNo = json_decode(TokenInfo)->OrgNo;
		}
		
		$org = $modelOrg->getOrganization($orgNo);
		$limit = count($model->where('OrganizationNo', $orgNo)->get()->getResult());
		if ((int)$org['GroupLimit'] <= $limit)
		{
			return $this->returnJson(200, [
				'Result' => false,
				'Errors' => [
					'GroupLimit' => '組織群組已達最大限制',
				],
			]);
		}
		
		$result = $model->insert([
			'GroupNo' => $groupNo,
			'GroupName' => $this->request->getVar('GroupName'),
			'OrganizationNo' => $orgNo,
			'Status' => $this->request->getVar('Status'),
			//'IsAdmin' => $org['IsAdmin'],
			//'IsSend' => $org['IsSend'],
			//'IsReceive' => $org['IsReceive'],
			'IsAdmin' => $isAdmin,
			'IsSend' => $isSend,
			'IsReceive' => $isReceive,
			'CreateUser' => json_decode(TokenInfo)->MemberNo,
			'UpdateUser' => json_decode(TokenInfo)->MemberNo,
		]) > 0;
		
		return $this->returnJson(200, [
			'Result' => $result,
		]);
    }
	
	public function updateGroupData()
    {
		$model = new GroupsModel();
		$modelMembers = new MembersModel();
		$modelOrg = new OrganizationsModel();
		$id = $this->request->getVar('GroupId');
		$groupNo = $this->request->getVar('GroupNo');
		$exist = $model->where([
			'GroupNo' => $groupNo,
			'ID <>' => $id
		])
		->get()->getResult();
		
		if (count($exist) > 0)
		{
			return $this->returnJson(200, [
				'Result' => false,
				'Errors' => [
					'GroupNo' => '群組代號已使用',
				],
			]);
		}
		
		$groupInit = $model->where([
			'ID' => $id
		])
		->get()->getResult();
		
		$stateInit = $groupInit[0]->Status;
		//$isAdmin = $this->request->getVar('IsAdmin');
		$isSend = $this->request->getVar('IsSend');
		$isReceive = $this->request->getVar('IsReceive');
		$state = $this->request->getVar('Status');
		$orgNo = $this->request->getVar('OrgNo');
		if (empty($orgNo))
		{
			$orgNo = json_decode(TokenInfo)->OrgNo;
		}
		$org = $modelOrg->getOrganization($orgNo);
		$limit = count($model->where(['OrganizationNo' => $orgNo, 'ID <>' => $id])->get()->getResult());
		if ((int)$org['GroupLimit'] <= $limit)
		{
			return $this->returnJson(200, [
				'Result' => false,
				'Errors' => [
					'GroupLimit' => '組織群組已達最大限制',
				],
			]);
		}
		
		$data = array(
			'GroupNo' => $groupNo,
			'GroupName' => $this->request->getVar('GroupName'),
			'OrganizationNo' => $orgNo,
			'Status' => $state,
			//'IsAdmin' => $isAdmin,
			'IsSend' => $isSend,
			'IsReceive' => $isReceive,
			'UpdateUser' => json_decode(TokenInfo)->MemberNo,
		);
		
		
		$ret = $model->where(['ID' => $id])->set($data)->update();
		
		// 依群組停用權限
		//if(empty($isAdmin))
		//{
			//$retMember = $modelMembers->where(['GroupNo' => $groupNo, 'Status' => 1])->set(array('IsAdmin' => 0))->update();
		//}
		
		if(empty($isSend))
		{
			$retMember = $modelMembers->where(['GroupNo' => $groupNo, 'Status' => 1])->set(array('IsSend' => 0))->update();
		}
		
		if(empty($isReceive))
		{
			$retMember = $modelMembers->where(['GroupNo' => $groupNo, 'Status' => 1])->set(array('IsReceive' => 0))->update();
		}
		
		//if(empty($state) || (empty($stateInit) && $state))
		//{
			$roles = array(
				'IsSend' => $isSend,
				'IsReceive' => $isReceive,
				//'IsAdmin' => $isAdmin,
				'Status' => $state,
			);
			
			$retMember = $modelMembers->where(['GroupNo' => $groupNo])->set($roles)->update();
		//}
		
		return $this->returnJson(200, [
			'Result' => $ret,
		]);
    }
	
	public function getGroupData()
    {
		$groupNo = $this->request->getVar('GroupNo');
		$model = new GroupsModel();
		$data = $model->where([
			'GroupNo' => $groupNo,
		])->get()->getResult();
		return $this->returnJson(200, [
			'Result' => true,
			'Message' => count($data) == 1 ? $data[0] : null,
		]);
    }
	
	public function getChangeKeyList()
	{
		$model = new ChangeKeyRecordsModel();
		$modelMembers = new MembersModel();
		$modelFiles = new MemberFilesModel();
		$memberNo = $this->request->getVar('MemberNo');
		$mode = $this->request->getVar('Mode');
		$modelMembers->select("
			0 AS ID, MemberNo, PublicKey, PrivateKey, PrivateKey, UpdateUser AS Blockchain, Status, CreateTime, UpdateTime, CreateUser, UpdateUser, '' AS FileList
		");
		
		$modelMembers->where([
			'MemberNo' => $memberNo,
			'Status' => 1,
		]);
		
		$listMember = $modelMembers->get()->getResultArray();
		$model->select("
			changekeyrecords.*
		");
		
		$model->where([
			'MemberNo' => $memberNo,
		]);
		
		$model->where([
			'Status =' => 1,
		]);
		
		$model->orderBy('ID', 'DESC');
		$list = $model->get()->getResultArray();
		$listMerge = array_merge($listMember, $list);
		if (empty($list))
		{
			$list = $listMember;
		}
		
		if (!empty($list))
		{
			$dateStart = "";
			$dateEnd = "";
			for ($i=0;$i<count($list);$i++)
			{
				$details = array();
				if (($i + 1) == count($list))
				{
					$dateStart = "";
				}
				else
				{
					if ($i == 0)
					{
						$dateEnd = "";
					}
					else
					{
						$dateEnd = $list[($i - 1)]['CreateTime'];
					}
					$dateStart = $list[$i]['CreateTime'];
				}
				
				$modelFiles->select("
					memberfiles.*,
					A.MemberName As 'SenderName',
					B.MemberName As 'ReceiveName',
					C.OrganizationNo As 'SenderOrgNo',
					C.OrganizationName As 'SenderOrgName',
					D.OrganizationNo As 'ReceiveOrgNo',
					D.OrganizationName As 'ReceiveOrgName'
				");
				$modelFiles->join('members A', 'memberfiles.MemberNo = A.MemberNo');
				$modelFiles->join('members B', 'memberfiles.MemberReceiveNo = B.MemberNo');
				$modelFiles->join('organizations C', 'A.OrganizationNo = C.OrganizationNo');
				$modelFiles->join('organizations D', 'B.OrganizationNo = D.OrganizationNo');
				
				if ($mode == "system")
				{
					$modelFiles->where([
						'memberfiles.EncodeNo' => $memberNo,
					]);
				}
				else
				{
					$modelFiles->where([
						'memberfiles.MemberReceiveNo' => $memberNo,
					]);
				}
				
				if (!empty($dateEnd))
				{
					$modelFiles->where([
						'memberfiles.CreateTime <' => $dateEnd
					]);
				}
				
				if (!empty($dateStart))
				{
					$modelFiles->where([
						'memberfiles.CreateTime >=' => $dateStart
					]);
				}
				
				$modelFiles->orderBy('memberfiles.ID DESC, memberfiles.FileName ASC, memberfiles.Version DESC');
				
				$details = $modelFiles->get()->getResultArray();
				$sql = $modelFiles->getLastQuery();
				$list[$i]['Details'] = $details;
				$fileList = array();
				foreach ($details as $detail)
				{
					array_push($fileList, $detail['FileName']);
				}
				$list[$i]['FileList'] = join(",", $fileList);
			}
		}
		
		return $this->returnJson(200, [
			'Result' => true,
			'List' => $list,
			'sql' => (string)$sql
		]);
	}
	
	public function checkChangePW()
    {
		$model = new MembersModel();
		$members = $model->getChangePWTimeOverMember();
		
		return $this->returnJson(200, [
			'Result' => true,
			'Members' => $members,
		]);
    }
	
	public function checkMemberFileCount()
    {
		$memberNo = $this->request->getVar('MemberNo');
		$model = new ChangeKeyRecordsModel();
		$changeKeyRecords = $model->getRecord($memberNo);
		$changeDate = "";
		if (!empty($changeKeyRecords))
		{
			$changeDate = $changeKeyRecords[0]->CreateTime;
		}	
		$modelFiles = new MemberFilesModel();
		$count = $modelFiles->checkMemberFileCount($memberNo, $changeDate);
		
		return $this->returnJson(200, [
			'Result' => true,
			'Count' => $count,
		]);
    }
	
	private function addChangeKeyRecordData($member, $mode="system")
	{
		$model = new ChangeKeyRecordsModel();
		$modelFiles = new MemberFilesModel();
		$memberNo = $member['MemberNo'];
		$record = array(
			'MemberNo' => $memberNo,
			'PublicKey' => $member['PublicKey'],
			'PrivateKey' => $member['PrivateKey'],
			'PrivatePwd' => $member['PrivatePwd'],
			'Blockchain' => '',
			'Status' => 1,
			'CreateUser' => $memberNo,
			'UpdateUser' => $memberNo,
		);
		$blockchainTrans = $this->signChangeKeyFile($record);
		$blockchain = $this->getBlockchainInquire($blockchainTrans);
			
		$record['BlockchainTrans'] = $blockchainTrans;
		$record['Blockchain'] = $blockchain;
		$changeKeyRecords = $model->getRecord($memberNo);
		
		$fileLists = array();
		$fileList = "";
		if ($mode == "system")
		{
			$modelFiles->select("
				memberfiles.FileName,
			");
				
			$modelFiles->where([
				'CreateTime <=' => date('Y-m-d h:i:s'),
				'EncodeNo' => $memberNo,
			]);
			
			if (!empty($changeKeyRecords))
			{
				$modelFiles->where([
					'CreateTime >' => $changeKeyRecords[0]->CreateTime,
				]);
			}
			$tempFileLists = $modelFiles->get()->getResultArray();
			if (count($tempFileLists) > 0)
			{
				foreach ($tempFileLists as $temp)
				{
					$fileLists[] = $temp['FileName'];
				}
				$fileList = join(", ", $fileLists);
			}
		}
		
		$fileNoLists = array();
		if ($mode == "member")
		{
			$today = date('Y-m-d', strtotime("+1 day", strtotime(date('Y-m-d'))));
			$resultFile = $modelFiles->where([
				"CreateTime <= " => $today,
				'MemberReceiveNo' => $memberNo,
				'Flag' => 1
			])->set('Flag', 0)->update();
		}
		
		$record['FileList'] = $fileList;
		$result = $model->insert($record) > 0;
		
		return $this->returnJson(200, [
			'Result' => $result,
		]);
	}
	
	private function returnJson($code, $data="")
	{
		return $this->response->setStatusCode($code)->setJSON($data);
	}
	
	private function encodeAES($data)
	{
		$encrypter = \Config\Services::encrypter();
		$ciphertext = $encrypter->encrypt($data);
		$encrypt_text = base64_encode($ciphertext);
		
		return $encrypt_text;
	}
	
	private function decodeAES($encrypt)
	{
		$encrypter = \Config\Services::encrypter();
		$encrypt = base64_decode($encrypt);
		$decrypt_text = $encrypter->decrypt($encrypt);
		
		return $decrypt_text;
	}
	
	private function signFile($file)
	{
		//return hash('sha256', 'SignFile_' . time() . '_' . rand(0, 999999));
		
		set_time_limit(0);
		ini_set('memory_limit', '2048M');
		$xml = "<?xml version='1.0' encoding='UTF-8'?>\n<MemberFiles>\n";
		foreach ($file as $key => $val)
		{
			$xml .= "<".$key.">".$val."</".$key.">\n";
		}
		$xml .= "</MemberFiles>";
		
		
		$today = date("Ymd");
		$folderPath = WRITEPATH.'uploads/'.$today;
		$fileName = uniqid().'.xml';
		$filePath = $folderPath.'/'. $fileName;
		if (!is_dir($folderPath))
		{
			mkdir($folderPath, 0777, TRUE);
		}
		file_put_contents($filePath, $xml);
		sleep(1);
		
		$blockchain = "";
		
		while(empty($blockchain))
		{
			$param = array(
				'my_file' => new \CURLFile($filePath, 'text/plain', $fileName),
			);
			$client = \Config\Services::curlrequest([
				'baseURI' => BlockChainUrl,
			]);
			$response = $client->request('POST', '/blockchain/signfile/', [
				'headers' => [
					'Content-type' => 'multipart/form-data'
				],
				'multipart' => $param,
			]);
			
			$data = str_replace(")\n", "", str_replace("attributedict(", "", $response->getBody()));
			$data = str_replace("),", ",", str_replace("hexbytes(", "", $data));
			$data = str_replace(")}", "}", $data);
			$data = str_replace("'", "", $data);
			$data = str_replace(" ", "", $data);
			$data = str_replace("}", "", $data);
			$data = str_replace("{", "", $data);
			$temps = explode(",", $data);
			foreach ($temps as $temp)
			{
				$items = explode(":", $temp);
				if ($items[0] == "hash")
				{
					$blockchain = $items[1];
				}
			}
		}
		
		return $blockchain;
	}
	
	private function signChangeKeyFile($record)
	{
		//return hash('sha256', 'ChangeKey_' . time() . '_' . rand(0, 999999));
		
		set_time_limit(0);
		ini_set('memory_limit', '2048M');
		$xml = "<?xml version='1.0' encoding='UTF-8'?>\n<MemberChangeKeyFiles>\n";
		foreach ($record as $key => $val)
		{
			$xml .= "<".$key.">".$val."</".$key.">\n";
		}
		$xml .= "</MemberChangeKeyFiles>";
		
		
		$today = date("Ymd");
		$folderPath = WRITEPATH.'uploads/'.$today;
		$fileName = uniqid().'_log.xml';
		$filePath = $folderPath.'/'. $fileName;
		if (!is_dir($folderPath))
		{
			mkdir($folderPath, 0777, TRUE);
		}
		file_put_contents($filePath, $xml);
		sleep(1);
		
		$blockchain = "";
		
		while(empty($blockchain))
		{
			$param = array(
				'my_file' => new \CURLFile($filePath, 'text/plain', $fileName),
			);
			$client = \Config\Services::curlrequest([
				'baseURI' => BlockChainUrl,
			]);
			$response = $client->request('POST', '/blockchain/signfile/', [
				'headers' => [
					'Content-type' => 'multipart/form-data'
				],
				'multipart' => $param,
			]);
			
			$data = str_replace(")\n", "", str_replace("attributedict(", "", $response->getBody()));
			$data = str_replace("),", ",", str_replace("hexbytes(", "", $data));
			$data = str_replace(")}", "}", $data);
			$data = str_replace("'", "", $data);
			$data = str_replace(" ", "", $data);
			$data = str_replace("}", "", $data);
			$data = str_replace("{", "", $data);
			$temps = explode(",", $data);
			foreach ($temps as $temp)
			{
				$items = explode(":", $temp);
				if ($items[0] == "hash")
				{
					$blockchain = $items[1];
				}
			}
		}
		
		return $blockchain;
	}
	
	public function updateBlockchainHash()
	{
		$otherFileNo = $this->request->getVar('OtherFileNo[]');
		$blockchain = "";
		$error = "";
		if (!empty($otherFileNo))
		{
			foreach ($otherFileNo as $otherNo)
			{
				if (!empty($otherNo))
				{
					$modelFiles = new MemberFilesModel();
					$otherFile = $modelFiles->getFileByNo($otherNo);
					try
					{
						$blockchainTrans = $this->signFile($otherFile);
						$blockchain = $this->getBlockchainInquire($blockchainTrans);
						$modelFiles->updateBlockchainByNo($otherNo, $blockchain, $blockchainTrans);
						$modelDownload = new DownloadRecordsModel();
						$modelDownload->updateBlockchainByNo($otherNo, $blockchain, $blockchainTrans);
					}
					catch (Exception $e) 
					{
						$error = $e->getMessage();
					}
				}
			}
		}
		
		return $this->returnJson(200, [
			'Result' => true,
			'Blockchain' => $blockchain,
			'Error' => $error,
			'OtherFileNo' => $otherFileNo
		]);
	}
	
	/**
     * Dashboard 統計數據 API
     */
    public function dashboardStats()
    {
        $fileModel = new \App\Models\MemberFilesModel();
        $downloadModel = new \App\Models\DownloadRecordsModel();
        $memberModel = new \App\Models\MembersModel();
        $orgModel = new \App\Models\OrganizationsModel();

        // 區塊數（假設 FileNo 唯一且遞增，或可用 Blockchain 欄位數量）
        $chainBlockCount = $fileModel->countAllResults();
        // IPFS 使用容量（所有檔案 FileSize 加總，單位 GB）
        $ipfsUsage = round($fileModel->selectSum('FileSize')->first()['FileSize'] / 1024 / 1024 / 1024, 2);
        // 數據上鏈數
        $totalRecords = $fileModel->countAllResults();
        // 數據檔上鏈數（假設 Status=1 為已上鏈）
        $fileRecords = $fileModel->where('Status', 1)->countAllResults();
        // 成功/失敗比率（假設 Status=1 成功，Status=0 失敗）
        $successCount = $fileModel->where('Status', 1)->countAllResults();
        $failCount = $fileModel->where('Status', 0)->countAllResults();
        // 異常事件數（假設 Status=-1 為異常）
        $abnormalCount = $fileModel->where('Status', -1)->countAllResults();
        // IPFS 狀態（簡單回傳 正常）
        $ipfsStatus = '正常';
        // 區塊鏈狀態（簡單回傳 正常）
        $blockchainStatus = '正常';

        return $this->returnJson(200, [
            'totalRecords' => $totalRecords,
            'fileRecords' => $fileRecords,
            'successCount' => $successCount,
            'failCount' => $failCount,
            'abnormalCount' => $abnormalCount,
            'ipfsStatus' => $ipfsStatus,
            'blockchainStatus' => $blockchainStatus,
            'chainBlockCount' => $chainBlockCount,
            'ipfsUsage' => $ipfsUsage,
        ]);
    }

    /**
     * Dashboard 上鏈資料趨勢圖 API
     */
    public function dashboardTrend()
    {
        $fileModel = new \App\Models\MemberFilesModel();
        // 近 7 天
        $labels = [];
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('n/j', strtotime($date));
            $count = $fileModel->where('DATE(CreateTime)', $date)->countAllResults();
            $data[] = $count;
        }
        return $this->returnJson(200, [
            'labels' => $labels,
            'datasets' => [[
                'label' => '上鏈資料量',
                'data' => $data,
                'borderColor' => '#1976d2',
                'backgroundColor' => 'rgba(25, 118, 210, 0.1)',
                'tension' => 0.3,
                'fill' => true,
            ]],
        ]);
    }
}
