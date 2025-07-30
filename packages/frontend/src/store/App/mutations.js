import { LocalStorage, Cookies } from 'quasar';

export function setAuthToken(state, value) {
  state.authToken = value;
  Cookies.set('_nspoid', value, { path: '/' });
}

export function setAuthMeta(state, value) {
  state.authMeta = value;
  LocalStorage.set('_nspometa', JSON.stringify(value), { path: '/' });
}

export function logout(state) {
  state.authToken = null;
  state.authMeta = null;
  Cookies.remove('_nspoid', { path: '/' });
  LocalStorage.remove('_nspometa', { path: '/' });
  LocalStorage.remove('_per', { path: '/' });
  LocalStorage.remove('isPasswordOverdue', { path: '/' });
  state.privateKey = null;
}

export function setRowsPerPage(state, payload) {
  LocalStorage.set('nspo_rowsPerPage', payload.rowsPerPage);
  state.rowsPerPage = payload.rowsPerPage;
}

export function increaseIndicatorCounter(state) {
  state.indicatorCounter += 1;
}

export function decreaseIndicatorCounter(state) {
  if (state.indicatorCounter) {
    state.indicatorCounter -= 1;
  }
}

export function setPrivateKey(state, value) {
  state.privateKey = value;
}

export function setOrgOptionsByAdmin(state, value) {
  state.orgOptionsByAdmin = value;
}

export function setOrgOptions(state, value) {
  state.orgOptions = value;
}

export function setOrgIsAdmin(state, value) {
  state.orgIsAdmin = value;
}

export function setOrgPermission(state, value) {
  state.orgPermission = value;
}

export function setPermissions(state, value) {
  state.permissions = value;
  LocalStorage.set('_per', value);
}
