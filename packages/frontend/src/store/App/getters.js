export function getAuthToken(state) {
  return state.authToken;
}

export function getAuthMeta(state) {
  return state.authMeta;
}

export function getRowsPerPage(state) {
  return state.rowsPerPage;
}

export function getRowsPerPageOptions(state) {
  return state.rowsPerPageOptions;
}

export function isApiLoading(state) {
  return state.indicatorCounter > 0;
}

export function getPrivateKey(state) {
  return state.privateKey;
}

export function getPublicKey(state) {
  return state.publicKey;
}

export function getOrgOptionsByAdmin(state) {
  return state.orgOptionsByAdmin;
}

export function getOrgOptions(state) {
  return state.orgOptions;
}

export function getOrgIsAdmin(state) {
  return state.orgIsAdmin;
}

export function getOrgPermission(state) {
  return state.orgPermission;
}

export function getPermissions(state) {
  return state.permissions;
}
