export function getFileList({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/file/list`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function postFileUpload({}, data) {
  const opts = {
    method: 'post',
    url: `/api/file/add`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, undefined, headers);
}

export function postFileChunk({}, data) {
  const opts = {
    method: 'post',
    url: `/api/file/chunk`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, undefined, headers);
}

export function getOrganizationList({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/organization/list`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function getOrganizationMember({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/member/filterbyorg`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function getMemberKey({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/member/filterpublickey`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function putDownloadStatus({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/download/add`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function getRecordList({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/download/list`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function getBlockchainInfo({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/blackchain/Inquire`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function updateBlockchainHash({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/file/updateblockchainhash`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}
