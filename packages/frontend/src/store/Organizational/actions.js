export function getOrganizationList({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/organization/list`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function putOrganizationUpdate({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/organization/update`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function postAddOrganization({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/organization/add`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function getOrganizationMappingList({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/organizationmappings/list`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function addOrganizationMapping({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/organizationmappings/add`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}
