import { boot } from 'quasar/wrappers';
import { createI18n } from 'vue-i18n';
import messages from 'src/i18n';
const localeCookie = localStorage.getItem('_locale');
const currentLocale = localeCookie || navigator.language || navigator.userLanguage || 'en-US';

const i18n = createI18n({
  locale: String(currentLocale),
  messages,
  legacy: false,
  globalInjection: true,
});

export default boot(({ app }) => {
  // Set i18n instance on app
  app.use(i18n);
});

export { i18n };
