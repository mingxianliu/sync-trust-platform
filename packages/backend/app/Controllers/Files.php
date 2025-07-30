<?php
namespace App\Controllers;

use App\Models\MemberFilesModel;
use App\Models\MembersModel;

class Files extends BaseController
{
	protected $helpers = ['form'];
	public function downloadFile($path, $fileName)
    {
		set_time_limit(0);
		ini_set("memory_limit","4096M");
		$fullpath = WRITEPATH."uploads/".$path."/".$fileName;
        if(($file = file_get_contents($fullpath)) === FALSE)
		{
			show_404();
		}
            
        return $this->response->download($fullpath, null);
	}

	private function returnJson($code, $data = "")
	{
		return $this->response->setStatusCode($code)->setJSON($data);
	}
}
