<?php

namespace App\Models;

class RsaModel
{
        public function decrypt($private, $targetFile, $saveFile)
        {
			
			$sc = '/var/www/html/nspo_backend/app/Models/decrypt.js';
			$private = str_replace('\\', '/', $private);
			$targetFile = str_replace('\\', '/', $targetFile);
			$saveFile = str_replace('\\', '/', $saveFile);
			system("node \"{$sc}\" \"" . $private . "\" \"" . $targetFile . "\" \"" . $saveFile . "\"", $result);
			return $result;
        }

        public function encrypt($public, $targetFile, $saveFile)
        {
			$sc = '/var/www/html/nspo_backend/app/Models/encrypt.js';
			$public = str_replace('\\', '/', $public);
			$targetFile = str_replace('\\', '/', $targetFile);
			$saveFile = str_replace('\\', '/', $saveFile);
			system("node \"{$sc}\" \"" . $public . "\" \"" . $targetFile . "\" \"" . $saveFile . "\"", $result);
			return $result;
        }
}
