<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\RsaModel;
use App\Models\MembersModel;
use App\Models\MemberFilesModel;
use Config\ApiConfig;

class Encrypt extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'CodeIgniter';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'command:encrypt';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = '';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'command:name [arguments] [options]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $result = shell_exec('ps -aux | grep "spark command:encrypt"');
            if (count(preg_split('/\n/', $result)) > 4) {
                CLI::print('重複執行中斷');
                return;
            }
        } else {
            if (file_exists(WRITEPATH . 'encrypt.lock')) {
                CLI::print('重複執行中斷');
                return;
            }
            file_put_contents(WRITEPATH . 'encrypt.lock', '');
        }
		try {
			$memberModel = new MembersModel();
			
			$memberFilesModel = new MemberFilesModel();
			$list = $memberFilesModel->like('Files', 'encrypt:%')->notLike('Files', '%.enc')->get()->getResult();
			
			$rsaModel = new RsaModel();

            $delSource = [];
			foreach ($list as $item) {
				$sendMember = $memberModel->getMember($item->MemberNo);
				$receiveMember = $memberModel->getMember($item->MemberReceiveNo);
                if ($receiveMember == null) {
                    continue;
                }
				$file = preg_split('/^encrypt:/', $item->Files)[1];
				$folderPath = WRITEPATH . 'uploads/' . mb_split('\/', $file)[0];
                $filePath = $folderPath . '/' . mb_split('\/', $file)[1];
                if (!file_exists($filePath)) {
                    continue;
                }
				$publicKey = $receiveMember['PublicKey'];

			    $publicKeyPath = sys_get_temp_dir() . '/' . 'public.' . uniqid() . rand(0, 9999);
                $savePath = $filePath . '.' . uniqid(rand(0, 9999));
                file_put_contents($publicKeyPath, $publicKey);
                $rsaModel->encrypt($publicKeyPath, $filePath, $savePath);
				if (!in_array($filePath, $delSource)) {
                    array_push($delSource, $filePath);
                }
				$memberFilesModel->update($item->ID, [
					'Files' => mb_split('\/', $file)[0] . '/' . basename($savePath),
					'EncodeStatus' => 1,
				]);
				
				//正式機要開啟寄信
				// $sender = $sendMember['MemberName'];
				// $receiverEmail = $receiveMember["Email"];
				// $email = \Config\Services::email();
				// $email->setFrom('service@nspo.viuto-aiot.com', '太空中心');
				// $email->setTo($receiverEmail);
				// $email->setBCC('kf_server@viuto.com.tw');
				// $email->setSubject('太空中心 - 新檔案上傳');
				// $url = "<a href='".base_url("take_file")."'>太空中心</a>";
				// $email->setMessage($sender." 上傳了檔案給您，請上 [".$url."] 接收檔案，檔名：".$item->FileName);
				// $email->send();

			}
            foreach ($delSource as $item) {
                unlink(preg_replace('/.enc$/', '', $item));
            }
		} catch (Exception $err) {

		}
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            unlink(WRITEPATH . 'encrypt.lock');
        }
        CLI::print('執行結束');
    }
}
