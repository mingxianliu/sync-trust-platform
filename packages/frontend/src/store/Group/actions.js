export function getGroupList({ commit }, data) {
  const opts = {
    method: 'post',
    url: `api/group/list`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function postAddGroup({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/group/add`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function postUpdateGroup({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/group/update`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function getGroupByGroupNo({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/group/info`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}
