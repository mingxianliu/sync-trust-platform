import { Cookies, LocalStorage } from 'quasar';
import { route } from 'quasar/wrappers';
import {
  createRouter,
  createMemoryHistory,
  createWebHistory,
  createWebHashHistory,
} from 'vue-router';
import routes from './routes';
import { $_infoNotify } from 'src/mixin/common';

/*
 * If not building with SSR mode, you can
 * directly export the Router instantiation;
 *
 * The function below can be async too; either use
 * async/await or return a Promise which resolves
 * with the Router instance.
 */

const checkPermission = (to, from, next) => {
  const token = Cookies.get('_nspoid');
  const permissions = LocalStorage.getItem('_per');
  const { name = '', meta = null } = to;

  if (
    (!token || !permissions?.includes(meta.permissions)) &&
    name !== 'login'
  ) {
    Cookies.remove('_nspoid', { path: '/' });
    LocalStorage.remove('_nspometa', { path: '/' });
    LocalStorage.remove('_per', { path: '/' });
    next('/login');
  } else if (token && permissions?.includes(meta.permissions)) {
    if (name === 'login') {
      next('/');
    } else {
      next();
    }
  } else {
    next();
  }
};

const globalRouterGuard = (to, from, next) => {
  const token = Cookies.get('_nspoid');
  const permissions = LocalStorage.getItem('_per');
  const { meta = null } = to;

  const isPasswordOverdue = LocalStorage.getItem('isPasswordOverdue') === '1';

  if (token && permissions?.includes(meta.permissions)) {
    if (
      (from.name === 'ChangePassword' || to.name !== 'ChangePassword') &&
      isPasswordOverdue
    ) {
      $_infoNotify('Please change your password');
      return next({ name: 'ChangePassword' });
    }
  }
  next();
};

export default route(function () {
  const createHistory = process.env.SERVER
    ? createMemoryHistory
    : process.env.VUE_ROUTER_MODE === 'history'
    ? createWebHistory
    : createWebHashHistory;

  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes: [
      {
        path: '/',
        component: () => import('layouts/MainLayout.vue'),
        beforeEnter: (to, from, next) => checkPermission(to, from, next),
        children: [
          { path: '', redirect: { name: 'Dashboard' } },
          ...routes.filter((route) => route.path !== '/data-records-test'),
        ],
      },
      {
        path: '/data-records-test',
        name: 'DataRecordsTest',
        component: () => import('pages/DataRecordsTest'),
        meta: {
          title: '數據記錄測試',
          icon: '',
          hide: true,
          hideChildren: true,
          permissions: 'pass',
        },
      },
      {
        path: '/login',
        name: 'Login',
        component: () => import('pages/Login'),
      },
      {
        path: '/forgotPassword',
        name: 'ForgotPassword',
        component: () => import('pages/Login'),
      },
      {
        path: '/resetPassword',
        name: 'ResetPassword',
        component: () => import('pages/Login/ResetPassword'),
      },

      // Always leave this as last one, but you can also remove it
      {
        path: '/:catchAll(.*)*',
        component: () => import('pages/ErrorNotFound.vue'),
      },
    ],

    // Leave this as is and make changes in quasar.conf.js instead!
    // quasar.conf.js -> build -> vueRouterMode
    // quasar.conf.js -> build -> publicPath
    history: createHistory(
      process.env.MODE === 'ssr' ? void 0 : process.env.VUE_ROUTER_BASE,
    ),
  });

  Router.beforeEach(globalRouterGuard);

  return Router;
});
