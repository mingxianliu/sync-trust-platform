import { boot } from 'quasar/wrappers';
import { Cookies, LocalStorage } from 'quasar';
import axios from 'axios';
import rateLimit from 'axios-rate-limit';

// console.log(process.env);

// Be careful when using SSR for cross-request state pollution
// due to creating a Singleton instance here;
// If any client changes this (global) instance, it might be a
// good idea to move this instance creation inside of the
// "export default () => {}" function below (which runs individually
// for each client)
const api = rateLimit(
  // process.env.API_URL
  axios.create({
    baseURL: 'https://apxtest.ioneit.com',
    timeout: 0,
    withCredentials: false,
  }),
  {
    maxRequests: 1,
    perMilliseconds: 200,
  },
);

// Add a request interceptor
api.interceptors.request.use(
  function (config) {
    // Do something before request is sent
    return config;
  },
  function (error) {
    // Do something with request error
    return Promise.reject(error);
  },
);

// Add a response interceptor
api.interceptors.response.use(
  function (response) {
    // Any status code that lie within the range of 2xx cause this function to trigger
    // Do something with response data

    if (
      !response.data.Result &&
      response.data.Code === '401' &&
      !window.location.pathname.includes('/login')
    ) {
      Cookies.remove('_nspoid', { path: '/' });
      LocalStorage.remove('_nspometa', { path: '/' });
      window.location = '/login';
    }
    return response.data;
  },
  function (error) {
    const { code, message } = error.response.data;
    // Any status codes that falls outside the range of 2xx cause this function to trigger
    // Do something with response error
    if (code === '400401' && !window.location.pathname.includes('/login')) {
      Cookies.remove('_nspoid', { path: '/' });
      LocalStorage.remove('_nspometa', { path: '/' });
      window.location = '/login';
    }
    return Promise.reject({ code, message });
  },
);

export default boot(({ store }) => {
  store.$axios = (opts = {}, commit, headers = {}) => {
    if (Object.prototype.toString.call(opts.data) === '[object FormData]') {
      opts.data.append('Token', Cookies.get('_nspoid'));
    }
    if (commit !== undefined) {
      store.commit('app/increaseIndicatorCounter', null, { root: true });
    }
    return api
      .request({
        headers: {
          // Authorization: `Bearer ${Cookies.get('_ida')}`,
          'Content-Type': 'application/json',
          // Bearer: Cookies.get('_ida'),

          ...headers,
        },
        ...opts,
      })
      .finally(() => {
        // console.log(headers);
        if (commit !== undefined) {
          store.commit('app/decreaseIndicatorCounter', null, { root: true });
        }
      });
  };
});

// Here we define a named export
// that we can later use inside .js files:
export {};
