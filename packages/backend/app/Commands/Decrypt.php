<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\RsaModel;
use App\Models\MembersModel;
use App\Models\MemberFilesModel;
use Config\ApiConfig;

class Decrypt extends BaseCommand
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
    protected $name = 'command:decrypt';

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
            $result = shell_exec('ps -aux | grep "spark command:decrypt"');
            if (count(preg_split('/\n/', $result)) > 4) {
                CLI::print('重複執行中斷');
                return;
            }
        } else {
            if (file_exists(WRITEPATH . 'decrypt.lock')) {
                CLI::print('重複執行中斷');
                return;
            }
            file_put_contents(WRITEPATH . 'decrypt.lock', '');
        }
		try {
			$memberFilesModel = new MemberFilesModel();
			$list = $memberFilesModel->like('Files', 'encrypt:%.enc')->get()->getResult();
			
			$rsaModel = new RsaModel();

            $delSource = [];
			foreach ($list as $item) {
                $path = WRITEPATH . "uploads/" . preg_replace('/^encrypt:/', '', $item->Files);
                if (!file_exists($path)) {
                    continue;
                }
                $r = $rsaModel->decrypt($path . '.private', $path, preg_replace('/\.enc$/', '', $path));

                $memberFilesModel->update($item->ID, [
                    'Files' => preg_replace('/\.enc$/', '', $item->Files),
                    //'FileSize' => filesize(preg_replace('/\.enc$/', '', $path)),
                ]);	
                if (!in_array($path . '.private', $delSource)) {
                    array_push($delSource, $path . '.private');
                }
			}
            foreach ($delSource as $item) {
                unlink($item);
            }
		} catch (Exception $err) {
			CLI::print('ERROR:'.$err);
		}
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            unlink(WRITEPATH . 'decrypt.lock');
        }
        CLI::print('執行結束');
    }
}
