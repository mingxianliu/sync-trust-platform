<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class ApiConfig extends BaseConfig
{
    public function decodeToken($token)
    {
        $token_info = openssl_decrypt($token, 'AES-128-CTR', TokenKey, 0, TokenIV);
        $token_info = json_decode($token_info, false);
        return $token_info;
    }
}
