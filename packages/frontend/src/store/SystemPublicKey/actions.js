export function getSystemKeyInfoList({ commit }, data) {
  const opts = {
    method: 'post',
    url: `api/systemkey/list`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function getSystemKeyFileList({ commit }, data) {
  const opts = {
    method: 'post',
    url: `api/systemkey/detail`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function createNewSystemKey({ commit }, data) {
  const opts = {
    method: 'post',
    url: `api/systemkey/createprivatekey`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function checkMemberFileCount({ commit }, data) {
  const opts = {
    method: 'post',
    url: `api/member/checkfilecount`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}
