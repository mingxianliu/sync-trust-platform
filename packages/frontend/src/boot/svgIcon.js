import { boot } from 'quasar/wrappers';
import SvgIcon from 'components/SvgIcon.vue';

const req = require.context('assets/icons', false, /\.svg$/);
const requireAll = (requireContext) =>
  requireContext.keys().map(requireContext);
requireAll(req);

export default boot(({ app }) => {
  app.component('SvgIcon', SvgIcon);
});
