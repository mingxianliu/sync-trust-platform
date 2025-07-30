<?php
namespace App\Controllers;
use App\Models\SuppliersModel;
use App\Models\SuppliersDataModel;
use App\Models\NoticesModel;
use App\Models\ServiceKindsModel;

class Home extends BaseController
{
	protected $helpers = ['form'];

	public function index()
    {
		echo view('homes/index');
    }
	
	public function renderImage($path, $imageName)
    {
        if(($image = file_get_contents(WRITEPATH."uploads/".$path."/".$imageName)) === FALSE)
		{
			show_404();
		}
            

        // choose the right mime type
        $mimeType = 'image/jpg';
		$this->response
			->setStatusCode(200)
			->setContentType($mimeType)
			->setBody($image)
			->send();
	}

	public function resetPassword($resetKey)
    {
		echo view('homes/reset', [
			'ResetKey' => base64_decode($resetKey),
		]);
	}
		
}
