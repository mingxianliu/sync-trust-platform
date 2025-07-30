export function postPrivatekey({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/member/createprivatekey`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function getPrivateFile({}, { data, onDownloadProgress }) {
  const opts = {
    method: 'get',
    url: `/api/download/${data}`,
    responseType: 'blob',
    onDownloadProgress,
  };

  return this.$axios(opts);
}

export function getPrivatekeyCheck({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/member/checkprivatekey`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function putChangPassword({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/member/updatepicandpwd`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}
