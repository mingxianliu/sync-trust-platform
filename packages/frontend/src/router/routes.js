const routes = [
  {
    path: 'dashboard',
    name: 'Dashboard',
    component: () => import('pages/Dashboard/index.vue'),
    meta: {
      title: '首頁',
      icon: 'eva-bar-chart-outline',
      permissions: 'pass',
      hide: false,
      leftMenu: true,
    },
  },
  {
    path: '/overview',
    name: 'Overview',
    component: () => import('pages/Overview'),
    meta: {
      title: 'Overview',
      icon: '',
      hide: true,
      hideChildren: true,
      permissions: 'pass',
    },
  },
  {
    path: '/sent_file',
    component: () => import('pages/SentFile'),
    meta: {
      title: '數據檔上鏈',
      icon: 'eva-paper-plane-outline',
      hide: false,
      hideChildren: true,
      permissions: 'isSend',
    },
    children: [
      {
        path: '',
        name: 'SentFiles',
        component: () => import('pages/SentFile/FileList'),
        meta: {
          title: '數據檔上鏈',
        },
      },
      {
        path: ':id',
        name: 'SentFilesRecord',
        component: () => import('pages/SentFile/FileRecord'),
        meta: {
          title: '存取紀錄',
        },
      },
    ],
  },
  {
    path: '/take_file',
    component: () => import('pages/TakeFile'),
    meta: {
      title: '數據檔檢視',
      svg: 'file_icon',
      hide: false,
      hideChildren: true,
      permissions: 'isReceive',
    },
    children: [
      {
        path: '',
        name: 'TakeFile',
        component: () => import('pages/TakeFile/FileList'),
        meta: {
          title: '數據檔檢視',
        },
      },
      {
        path: ':id',
        name: 'TakeFileRecord',
        component: () => import('pages/SentFile/FileRecord'),
        meta: {
          title: '存取紀錄',
        },
      },
    ],
  },
  {
    path: '/management/dataRecords',
    name: 'DataRecords',
    component: () => import('pages/DataRecords'),
    meta: {
      title: '數據資料查詢',
      icon: 'eva-bar-chart-outline',
      leftMenu: true,
      permissions: 'pass',
    },
  },
  {
    path: '/management',
    component: () => import('pages'),
    meta: {
      title: '系統管理',
      permissions: 'isOrgAdmin',
    },
    children: [
      {
        path: 'organize',
        name: 'Organize',
        component: () => import('pages/OrganizationalManagement'),
        meta: {
          title: '組織管理',
          svg: 'organize_icon',
          leftMenu: true,
          userPermissions: ['admin'],
        },
      },
      {
        path: 'user',
        name: 'User',
        component: () => import('pages/UserManagement'),
        meta: {
          title: '使用者與群組管理',
          svg: 'user_icon',
          leftMenu: true,
        },
      },
      {
        path: 'organize/:OrganizationNo/receiver',
        name: 'EditReceivedOrganization',
        component: () =>
          import('pages/OrganizationalManagement/EditReceivedOrganization'),
        meta: {
          title: '編輯接收方',
          svg: 'organize_icon',
        },
      },
      {
        path: 'addUser',
        name: 'AddUser',
        component: () => import('pages/UserManagement/AddUserPage'),
        meta: {
          title: '使用者與群組管理',
          svg: '編輯使用者',
        },
      },
      {
        path: 'editUser/:MemberNo',
        name: 'editUser',
        component: () => import('pages/UserManagement/EditUserPage'),
        meta: {
          title: '使用者與群組管理',
          svg: '編輯使用者',
        },
      },
      {
        path: 'user/:memberNo/file/list',
        name: 'userEncryptedFiles',
        component: () => import('pages/UserManagement/FileListRecord.vue'),
        meta: {
          title: '加密檔案列表',
        },
      },
      {
        path: 'user/:memberNo/:encodeNo/list',
        name: 'userEncryptedSpecificFileRecord',
        component: () => import('pages/UserManagement/SpecificFileRecord.vue'),
        meta: {
          title: '存取紀錄',
        },
      },
      {
        path: 'systemPublicKey',
        name: 'systemPublicKey',
        component: () => import('pages/SystemPublicKeyManagement/index'),
        meta: {
          title: '系統公鑰管理',
        },
      },
      {
        path: 'systemPublicKey/:memberNo/list',
        name: 'systemPublicKeyFileList',
        component: () =>
          import('pages/SystemPublicKeyManagement/FileListRecord'),
        meta: {
          title: '加密檔案列表',
        },
      },
      {
        path: 'systemPublicKey/:fileNo/file',
        name: 'systemPublicKeySpecificFile',
        component: () =>
          import('pages/SystemPublicKeyManagement/SpecificFileRecord'),
        meta: {
          title: '存取紀錄',
        },
      },
    ],
  },
  {
    path: '/setting',
    name: 'Setting',
    component: () => import('pages/Setting'),
    meta: {
      title: 'Setting',
      icon: 'eva-paper-plane-outline',
      hide: true,
      hideChildren: true,
      permissions: 'pass',
    },
    redirect: { name: 'ChangePassword' },
    children: [
      {
        path: 'change-password',
        name: 'ChangePassword',
        component: () => import('pages/Setting/ChangePassword'),
        meta: {
          title: 'Change Password',
          icon: 'group',
          leftMenu: true,
        },
      },
      {
        path: 'change-key',
        name: 'ChangeKey',
        component: () => import('pages/Setting/ChangeKey'),
        meta: {
          title: 'Change Key',
          icon: 'group',
          leftMenu: true,
        },
      },
    ],
  },
];

export default routes;
