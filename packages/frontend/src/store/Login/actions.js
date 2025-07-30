// export function someAction(/* context */) {}

export function getToken({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/login/member`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function putForgotPassword({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/member/forget`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}

export function putResetPassword({ commit }, data) {
  const opts = {
    method: 'post',
    url: `/api/member/resetpassword`,
    data,
  };
  const headers = { 'Content-Type': 'multipart/form-data' };

  return this.$axios(opts, commit, headers);
}
