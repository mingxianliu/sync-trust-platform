<?php
namespace App\Controllers;

use App\Models\MembersModel;
use App\Models\MembersDataModel;
use App\Models\MemberPetsModel;
use App\Models\NoticesModel;

class Member extends BaseController
{
	protected $helpers = ['form'];
	public function index()
    {
		$data = [
			'mainPage' => 'member',
			'controller' => 'Member',
			'login' => true,
			'user' => session()->get('user'),
		];

		echo view('admins/header', $data);
		echo view('admins/body', $data);
		echo view('admins/footer', $data);
	}

	public function add($id)
    {
		$data = [
			'mainPage' => 'memberAdd',
			'controller' => 'Member',
			'login' => true,
			'user' => session()->get('user'),
			'memberID' => intval($id),
		];

		echo view('admins/header', $data);
		echo view('admins/body', $data);
		echo view('admins/footer', $data);
	}

	public function img($dirName, $filename)
	{
		$validation = (\Config\Services::validation())->check($filename, 'required|max_length[100]|regex_match[/^[\w\.]+$/]');
		if ($validation) {
			$filepath = ROOTPATH . '/writable/uploads/member/' . $dirName . '/' . $filename;

			$contents = file_get_contents($filepath);
			return $this->response
				->setStatusCode(200)
				->setContentType(mime_content_type($filepath))
				->setBody($contents);
		} else {
			return $this->response->setStatusCode(403);
		}
	}

	public function get($id)
	{
		$validation = (\Config\Services::validation())->check($id, 'required|numeric', [
			'required' => '會員編號未輸入',
			'numeric' => '會員編號必須是數字',
		]);
		if ($validation) {
			if (intval($id) > 0){
				$model = new MembersModel();
				$member = $model->find($id);
				if ($member == null) {
					return $this->returnJson(200, [
						'Result' => false,
						'Errors' => [
							'DB' => '找不到此會員',
						],
					]);
				}
				$membersDataModel = new MembersDataModel();
				$membersData = $membersDataModel->where([
					'MemberNo' => $member['MemberNo'],
				])->first();
				$member['Name'] = $membersData['Name'];
				$member['Email'] = $membersData['Email'];
				$member['Gender'] = $membersData['Gender'];
				$member['Address'] = $membersData['Address'];
				$member['MemberPic'] = $membersData['MemberPic'] == null ? '' : $membersData['MemberPic'];
				$member['MemberPwd'] = '';
				unset($member['SMSRegCode']);
	
				return $this->returnJson(200, [
					'Result' => true,
					'Data' => $member,
				]);
			}else{
				return $this->returnJson(200, [
					'Result' => true,
				]);
			}
		} else {
			return $this->returnJson(200, [
				'Result' => false,
				'Errors' => (\Config\Services::validation())->getErrors(),
			]);
		}
	}

	public function list()
	{
		$validation = $this->validate([
			'search' => 'min_length[0]|max_length[100]',
			'start' => 'required|numeric',
			'count' => 'required|numeric|min_numeric[1]|max_numeric[10]',
		], [
			'search' => [
				'min_length' => '關鍵字未輸入',
				'max_length' => '關鍵字太長',
			],
			'start' => [
				'required' => '開始位置未輸入',
				'numeric' => '開始必須是數字',
			],
			'count' => [
				'required' => '數量未輸入',
				'numeric' => '數量必須是數字',
				'min_numeric' => '數量不正確',
				'max_numeric' => '數量不正確',
			],
		]);
		if ($validation) {
			$model = new MembersModel();
			$filterProduct = $model->filterMember(
				$this->request->getVar('search'),
				null,
				$this->request->getVar('start') * $this->request->getVar('count'),
				$this->request->getVar('count'),
				'CreateTime',
				'DESC'
			);
			return $this->returnJson(200, [
				'Result' => true,
				'List' => $filterProduct['List'],
				'Count' => $filterProduct['Count'],
			]);
		} else {
			$validation = \Config\Services::validation();
			return $this->returnJson(200, [
				'Result' => false,
				'Errors' => $validation->getErrors(),
			]);
		}
	}

	public function submit()
	{
		$rule = [
			'ID' => 'permit_empty|numeric',
			'MemberAcc' => 'required|max_length[50]',
			'MemberPwd' => 'permit_empty',
			'Gender' => 'permit_empty|regex_match[/^[FM]$/]',
			'Phone' => 'permit_empty|max_length[20]',
			'MemberPic_File' => 'permit_empty',
			'Status' => 'permit_empty|regex_match[/^[01]$/]',
			'Name' => 'permit_empty|max_length[100]',
			'Address' => 'max_length[200]',
			'Email' => 'max_length[100]',
		];
		$message = [
			'ID' => [
				'numeric' => '會員ID必須是數字',
			],
			'MemberAcc' => [
				'required' => '帳號未輸入',
				'max_length' => '帳號最多50字元',
			],
			'Gender' => [
				'required' => '性別未輸入',
				'regex_match' => '性別輸入不正確',
			],
			'Phone' => [
				'required' => '手機號碼未輸入',
				'max_length' => '手機號碼最多20字元',
			],
			'Status' => [
				'regex_match' => '狀態輸入不正確',
			],
			'Name' => [
				'required' => '姓名未輸入',
				'max_length' => '姓名最多100字元',
			],
			'Address' => [
				'max_length' => '地址最多200字元',
			],
			'Email' => [
				'max_length' => 'Email最多100字元',
			],
		];

		$validation = $this->validate($rule, $message);
		if ($validation) {
			$status = $this->request->getVar('Status') == null ? 1 : $this->request->getVar('Status');
			if (!(\Config\Services::validation())->check($this->request->getVar('MemberAcc'), 'required|valid_email') && 
			!(\Config\Services::validation())->check($this->request->getVar('MemberAcc'), 'required|regex_match[/^\d{10}$/]')) {
				return $this->returnJson(200, [
					'Result' => false,
					'Errors' => [
						'MemberAcc' => '帳號請輸入 手機號碼 或 Email',
					],
				]);
			}


			$model = new MembersModel();
			$memberID = intval($this->request->getVar('ID'));

			$data = [
				'ID' => $this->request->getVar('ID'),
				'MemberAcc' => $this->request->getVar('MemberAcc'),
				'Status' => $status,
				'Phone' => $this->request->getVar('Phone'),
			];
			if (session()->has('user')){
				if (session()->get('user')['UserRole'] == 'Member'){
					if (intval(session()->get('user')['ID']) != $memberID){
						return $this->returnJson(200, [
							'Result' => false,
							'Errors' => [
								'DB' => '您沒有權限',
							],
						]);
					}
					unset($data['MemberAcc']);
				}
			}else{
				$data['ID'] = 0;
				$memberID = 0;
			}

			if ($memberID == 0){
				if (count($model->where('MemberAcc', $this->request->getVar('MemberAcc'))->get()->getResult()) > 0){
					return $this->returnJson(200, [
						'Result' => false,
						'Errors' => [
							'MemberAcc' => '帳號已被已用',
						],
					]);
				}
				if ($this->request->getVar('MemberPwd') == '') {
					return $this->returnJson(200, [
						'Result' => false,
						'Errors' => [
							'MemberPwd' => '密碼未輸入',
						],
					]);
				}
				$data['MemberNo'] = hash('sha256', 'member_' . time() . '_' . rand(0, 999999));
			}

			$result = false;

			if ($memberID > 0) {
				//修改會員
				$member = $model->find($memberID);
				if ($member == null) {
					return $this->returnJson(200, [
						'Result' => false,
						'Errors' => [
							'DB' => '資料不存在，無法修改',
						],
					]);
				}
				if ($this->request->getVar('MemberPwd') != ''){
					$data['MemberPwd'] = hash('sha256', $this->request->getVar('MemberPwd'));
				}
				$data['MemberNo'] = $member['MemberNo'];
				$data['UpdateUser'] = session()->user['UserNo'];
				$result = $model->update($memberID, $data);
			} else {
				//新增會員
				$data['MemberPwd'] = hash('sha256', $this->request->getVar('MemberPwd'));
				$data['CreateUser'] = session()->has('user') ? session()->user['UserNo'] : $data['MemberNo'];
				$data['UpdateUser'] = session()->has('user') ? session()->user['UserNo'] : $data['MemberNo'];
				$result = $model->insert($data);
				$memberID = $model->getInsertID();
			}

			if ($result){
				$dataDetail = [
					'Name' => $this->request->getVar('Name'),
					'MemberPic_File' => $this->request->getVar('MemberPic_File'),
					'Gender' => $this->request->getVar('Gender'),
					'Phone' => $this->request->getVar('Phone'),
					'Address' => $this->request->getVar('Address'),
					'MemberNo' => $data['MemberNo'],
					'Email' => $this->request->getVar('Email'),
				];

				$imgs = [
					'MemberPic' => ['Img' => $this->request->getFile('MemberPic_File'), 'saveName' => ''],
				];
				foreach ($imgs as $key => $value){
					$img = $imgs[$key]['Img'];
					if ($img != null) {
						if ($img->isValid() && !$img->hasMoved()) {
							$validation = \Config\Services::validation();
							$validation = $validation->check($img, 'mime_in[' . $key . '_File,image/png,image/jpg,image/jpeg]|max_size[' . $key . '_File,10240]', [
								'mime_in' => '圖片格式不可上傳',
								'max_size' => '圖片太大不可上傳',
							]);
							if (!$validation) {
								return $this->returnJson(200, [
									'Result' => false,
									'Errors' => [
										$key . '_File' => (\Config\Services::validation())->getErrors()['check'],
									],
								]);
							}
							$imgs[$key]['saveName'] = rand(0, 9999) . '_' . time() . '_' . hash_file('sha256', $img->getTempName()) . '.' . $img->getClientExtension();
							if (!file_exists(WRITEPATH . 'uploads/member/' . $memberID . '/')) mkdir(WRITEPATH . 'uploads/member/' . $memberID . '/', 0777);
							$img->move(WRITEPATH . 'uploads/member/' . $memberID, $imgs[$key]['saveName']);
						}
					}
				}

				if ($imgs['MemberPic']['saveName'] != '') {
					$dataDetail['MemberPic'] = $memberID . '/' . $imgs['MemberPic']['saveName'];
				}

				//會員資料
				$model = new MembersDataModel();
				$model->where('MemberNo', $data['MemberNo']);
				$membersData = $model->get()->getResultArray();
				foreach ($membersData as $key => $value){
					$dataDetail['UpdateUser'] = session()->user['UserNo'];
					$result = $model->update($value['ID'], $dataDetail);
					foreach (['MemberPic'] as $dataValue){
						if ($result && array_key_exists($dataValue, $dataDetail)){
							if (file_exists(WRITEPATH . 'uploads/member/' . $value[$dataValue]) && $value[$dataValue] != ''){
								unlink(WRITEPATH . 'uploads/member/' . $value[$dataValue]);
							}
						}
					}
				}
				if (count($membersData) == 0){
					$dataDetail['CreateUser'] = session()->has('user') ? session()->user['UserNo'] : $data['MemberNo'];
					$dataDetail['UpdateUser'] = session()->has('user') ? session()->user['UserNo'] : $data['MemberNo'];
					$result = $model->insert($dataDetail);
				}
			}

			if (session()->has('user')){
				if ($result && session()->get('user')['UserRole'] == 'Member'){
					$info = session()->get('user');
					$info['UserName'] = $this->request->getVar('MemberName');
					session()->set(array('user' => $info));
				}
			}

			return $this->returnJson(200, [
				'Result' => $result > 0,
				'MemberID' => $memberID,
			]);
		} else {
			return $this->returnJson(200, [
				'Result' => false,
				'Errors' => (\Config\Services::validation())->getErrors(),
			]);
		}
	}

	public function delete($id)
	{
		$membersModel = new MembersModel();
		$memberNo = $membersModel->find($id);
		if ($memberNo == null) return $this->returnJson(200);
		$memberNo = $memberNo['MemberNo'];

		$membersDataModel = new MembersDataModel();
		$membersData = $membersDataModel->where('MemberNo', $memberNo);
		if ($membersData->get() != null) {
			foreach ($membersDataModel->where('MemberNo', $memberNo)->get()->getResultArray() as $key => $value) {
				$membersDataModel->delete($value['ID']);
			}
		}
		$membersModel->delete($id);

		return $this->returnJson(200);
	}

	public function member()
    {
		echo view('members/index');
    }
	
	public function joinMember()
    {
		$acc = $this->request->getVar('acc');
		$pwd = $this->request->getVar('pwd');
		$data = [
			'controller' => 'Member',
			'error' => ''
		];
		
		if (! empty($acc) && ! empty($pwd))
		{
			$name = $this->request->getVar('name');
			$gender = $this->request->getVar('gender');
			$phone = $this->request->getVar('phone');
			$birthday = $this->request->getVar('birthday');
			$email = $this->request->getVar('email');
			$address = $this->request->getVar('address');
			$pic = "";
			
			if (! empty($name) && ! empty($gender) && ! empty($birthday) && ! empty($email) && ! empty($phone))
			{
				$model = new MembersModel();
				$modelData = new MembersDataModel();
				$modelPet = new MemberPetsModel();
				$modelNotices = new NoticesModel();
				$img = $this->request->getFile('pic');
				$pic = $modelNotices->checkImage($img, 'pic');
				$memberNo = hash('sha256', 'Member_' . time() . '_' . rand(0, 999999));
				$pwdSha256 =  hash("sha256", TokenState.$pwd.TokenState);
				$member = array(
					'MemberNo' => $memberNo,
					'MemberAcc' => $acc,
					'MemberPwd' => $pwdSha256,
					'Status' => 1,
					'Phone' => $phone,
					'CreateTime' => date('Y-m-d h:i:s')
				);
				
				$memberData = array(
					'MemberNo' => $memberNo,
					'Name' => $name,
					'MemberPic' => $pic,
					'Birthday' => $birthday,
					'Gender' => $gender,
					'Phone' => $phone,
					'Email' => $email,
					'Address' => $address,
					'CreateTime' => date('Y-m-d h:i:s'),
					'CreateUser' => 'system'
				);
				
				$petNo = hash('sha256', 'MemberPet_' . time() . '_' . rand(0, 999999));;
				$petName = $this->request->getVar('pet_name');
				$petGender = $this->request->getVar('pet_gender');
				$petKind = $this->request->getVar('pet_kind');
				$petBreed = $this->request->getVar('pet_breed');
				$petBirthday = $this->request->getVar('pet_birthday');
				$petLength = $this->request->getVar('pet_length');
				$petWeight = $this->request->getVar('pet_weight');
				$petBlood = $this->request->getVar('pet_blood');
				$petFood = $this->request->getVar('pet_food');
				$petMajorTrauma = $this->request->getVar('pet_major_trauma');
				$petDrugAllergy = $this->request->getVar('pet_drug_allergy');
				$petClinic = $this->request->getVar('pet_clinic');
				$petClinicStatus = $this->request->getVar('pet_clinic_status');
				
				$petData = array(
					'MemberNo' => $memberNo,
					'PetNo' => $petNo,
					'PetName' => $petName,
					'Gender' => $petGender,
					'PetKind' => $petKind,
					'PetBreed' => $petBreed,
					'Birthday' => $petBirthday,
					'BodyLength' => $petLength,
					'Weight' => $petWeight,
					'BloodType' => $petBlood,
					'FoodRec' => $petFood,
					'MajorTraumaRec' => $petMajorTrauma,
					'DrugAllergyRec' => $petDrugAllergy,
					'Clinic' => $petClinic,
					'ClinicStatus' => $petClinicStatus,
					'CreateTime' => date('Y-m-d h:i:s'),
					'CreateUser' => 'system'
				);
				
				$ret = $model->addMember($member);
				$ret2 = 0;
				if ($ret)
				{
					$ret2 = $modelData->addMemberData($memberData);
					$ret3 = $modelPet->addPets($petData);
				}
				else
				{
					$data['error'] = '帳號已存在，請重新填寫';
				}
			}
			else
			{
				$data['error'] = '*必填欄位不可為空';
			}
			
			if (empty($data['error']))
			{
				echo view('members/success', $data);
			}
			else
			{
				echo view('members/join', $data);
			}
		}
		else
		{
			echo view('members/join', $data);
		}
	}

	private function returnJson($code, $data = "")
	{
		return $this->response->setStatusCode($code)->setJSON($data);
	}
}
