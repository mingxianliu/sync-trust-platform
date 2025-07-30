<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

//前台 API - 會員登入
$routes->get('/api/test', 'Api::test');
$routes->post('/api/login/member', 'Api::loginMember');
$routes->post('/api/logout/member', 'Api::logoutMember', ['filter' => 'auth']);
$routes->post('/api/del/member', 'Api::delMember', ['filter' => 'auth']);
$routes->post('/api/member/forget', 'Api::forgetMember');
$routes->post('/api/member/resetpassword', 'Api::resetPassword');
$routes->post('/api/member/info', 'Api::getMemberData', ['filter' => 'auth']);
$routes->post('/api/member/filterbyorg', 'Api::filterMemberByOrg', ['filter' => 'auth']);
$routes->post('/api/member/filterbygroup', 'Api::filterMemberByGroup', ['filter' => 'auth']);
$routes->post('/api/member/filterpublickey', 'Api::filterMemberPublicKey', ['filter' => 'auth']);
$routes->post('/api/member/filter', 'Api::filterMember', ['filter' => 'auth']);
$routes->post('/api/member/createprivatekey', 'Api::createMemberPrivateKey', ['filter' => 'auth']);
$routes->post('/api/member/checkprivatekey', 'Api::checkMemberPrivateKey', ['filter' => 'auth']);
$routes->post('/api/member/list', 'Api::getMemberList', ['filter' => 'auth']);
$routes->post('/api/member/add', 'Api::addMemberData', ['filter' => 'auth']);
$routes->post('/api/member/update', 'Api::updateMemberData', ['filter' => 'auth']);
$routes->post('/api/member/updatepicandpwd', 'Api::updateMemberPicAndPwd', ['filter' => 'auth']);
$routes->post('/api/member/checkfilecount', 'Api::checkMemberFileCount', ['filter' => 'auth']);

//前台 API - 會員檔案
$routes->post('/api/file/list', 'Api::getMemberFileList', ['filter' => 'auth']);
$routes->post('/api/file/add', 'Api::addMemberFileData', ['filter' => 'auth']);
$routes->post('/api/file/chunk', 'Api::addMemberFileChunkData', ['filter' => 'auth']);
$routes->post('/api/file/updateblockchainhash', 'Api::updateBlockchainHash', ['filter' => 'auth']);
//前台 API - 會員群組
$routes->post('/api/group/list', 'Api::getGroupList', ['filter' => 'auth']);
$routes->post('/api/group/add', 'Api::addGroupData', ['filter' => 'auth']);
$routes->post('/api/group/update', 'Api::updateGroupData', ['filter' => 'auth']);
$routes->post('/api/group/info', 'Api::getGroupData', ['filter' => 'auth']);

//前台 API - 組織
$routes->post('/api/organization/list', 'Api::getOrganizationList', ['filter' => 'auth']);
$routes->post('/api/organization/add', 'Api::addOrganizationData', ['filter' => 'auth']);
$routes->post('/api/organization/update', 'Api::updateOrganizationData', ['filter' => 'auth']);
$routes->post('/api/organizationmappings/list', 'Api::getOrganizationMappingsList', ['filter' => 'auth']);
$routes->post('/api/organizationmappings/add', 'Api::addOrganizationMappingsData', ['filter' => 'auth']);

//前台 API - 下載
$routes->post('/api/download/list', 'Api::getDownloadFileList', ['filter' => 'auth']);
$routes->post('/api/download/add', 'Api::addDownloadRecordData', ['filter' => 'auth']);

//前台 API - 換鑰
$routes->post('/api/changekey/list', 'Api::getChangeKeyList', ['filter' => 'auth']);

// 圖片顯示
$routes->get('imagerender/(:segment)/(:segment)', 'Home::renderImage/$1/$2');

//下載檔案
$routes->get('/api/download/(:segment)/(:segment)', 'Files::downloadFile/$1/$2');

//前台 API - IPFS LS
$routes->post('/api/ipfs/ls', 'Api::ipfsGetLs', ['filter' => 'auth']);
$routes->post('/api/blackchain/Inquire', 'Api::sendInquire', ['filter' => 'auth']);

//前台 API - 系統公鑰列表
$routes->post('/api/systemkey/list', 'Api::getSystemKeyList', ['filter' => 'auth']);
$routes->post('/api/systemkey/detail', 'Api::getSystemKeyDetail', ['filter' => 'auth']);
$routes->post('/api/systemkey/createprivatekey', 'Api::createSystemPrivateKey', ['filter' => 'auth']);
$routes->post('/api/checkchangepw', 'Api::checkChangePW', ['filter' => 'auth']);
$routes->get('api/dashboardStats', 'Api::dashboardStats');
$routes->get('api/dashboardTrend', 'Api::dashboardTrend');