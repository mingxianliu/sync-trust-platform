import { LocalStorage, Cookies } from 'quasar';

const nspoid = Cookies.get('_nspoid') ?? null;
const nspometa = LocalStorage.getItem('_nspometa') ?? null;
const permission = LocalStorage.getItem('_per') ?? null;

export default function () {
  return {
    authToken: nspoid,
    authMeta: nspometa ? JSON.parse(nspometa) : null,
    indicatorCounter: 0,
    rowsPerPageOptions: [5, 10, 20, 50, 100],
    rowsPerPage: parseInt(LocalStorage.getItem('nspo_rowsPerPage')) || 20,
    privateKey: null,
    orgOptionsByAdmin: [],
    orgOptions: [],
    orgIsAdmin: [],
    orgPermission: [],
    permissions: permission ? permission : [],
  };
}
