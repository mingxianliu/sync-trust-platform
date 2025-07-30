<?php
namespace App\Filters;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\Config;
use Config\ApiConfig;
use App\Models\MembersModel;
class Auth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $apiConfig = new ApiConfig();
        $token = $request->getVar('Token');
		
        try {
            $token_info = $apiConfig->decodeToken($token);
            $result = false;
            if ($token_info->UserRole == 'Member') {
                $model = new MembersModel();
                $result = count($model->where([
                    'ID' => $token_info->ID,
                    'MemberPwd' => $token_info->MemberPwd,
                    'LogoutTime' => $token_info->LogoutTime,
                    'Status' => 1,
                ])->get()->getResultArray()) > 0;
            }
            if ($result){
                define('TokenInfo', json_encode($token_info));
            }else{
                throw new \Exception('auth error');
            }
        } catch (\Exception $e) {
            
            $response = Config\Services::response();
            
            return $response->setStatusCode(200)->setJSON([
                'Result' => false,
                'Message' => 'Token無效',
                'Code' => '401'
            ]);
        }
	}

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do something here
    }
}