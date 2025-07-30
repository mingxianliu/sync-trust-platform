import { store } from 'quasar/wrappers';
import { createStore } from 'vuex';

import app from './App';
import login from './Login';
import takeFile from './TakeFile';
import sentFile from './SentFile';
import organizational from './Organizational';
import user from './User';
import group from './Group';
import systemPublicKey from './SystemPublicKey';

/*
 * If not building with SSR mode, you can
 * directly export the Store instantiation;
 *
 * The function below can be async too; either use
 * async/await or return a Promise which resolves
 * with the Store instance.
 */

export default store(function (/* { ssrContext } */) {
  const Store = createStore({
    modules: {
      app,
      login,
      takeFile,
      sentFile,
      organizational,
      user,
      group,
      systemPublicKey
    },

    // enable strict mode (adds overhead!)
    // for dev mode and --debug builds only
    strict: process.env.DEBUGGING,
  });

  return Store;
});
