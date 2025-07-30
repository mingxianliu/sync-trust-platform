export function getMemberList({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/member/list`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function putMemberUpdate({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/member/update`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function postAddMember({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/member/add`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function getMemberByMemberNo({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/member/filter`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function getMemberByGroupNo({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/member/filterbygroup`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function getHistoricalKeyByMemberNo({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/changekey/list`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}
