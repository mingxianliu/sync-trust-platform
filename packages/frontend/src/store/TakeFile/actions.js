export function getFile({ commit }, data) {
  const opts = {
    method: 'get',
    url: ``,
    data,
  };

  return this.$axios(opts, commit, headers);
}
