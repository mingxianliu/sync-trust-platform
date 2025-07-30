import { i18n } from 'boot/i18n';
import { Notify } from 'boot/notify-defaults';
import { useStore } from 'vuex';

const { t } = i18n.global;

export const $_checkEmailRule = (prop) => {
  const emailRule =
    /^\w+((-\w+)|(\.\w+)|(\+\w+))*@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z]+$/;
  return !emailRule.test(prop) ? t('format_error') : true;
};

export const $_isEmpty = (val) => {
  return !!val ? true : t('field_is_required');
};

export const $_successNotify = (msg, actions = null) => {
  Notify.create({
    color: 'positive',
    message: msg,
    ...actions,
  });
};

export const $_errorNotify = (msg, actions = null) => {
  Notify.create({
    color: 'negative',
    message: msg,
    ...actions,
  });
};

export const $_infoNotify = (msg, actions = null) => {
  Notify.create({
    color: 'blue-grey-8',
    icon: 'eva-alert-circle-outline',
    message: msg,
    ...actions,
  });
};

export const $_permission = (meta) => {
  if (meta.value.IsAdmin === '1' && meta.value.IsAdminOrg === '1') {
    return 'admin';
  }
  if (meta.value.IsSend === '1') {
    return 'send';
  }
  if (meta.value.IsReceive === '1') {
    return 'receive';
  }
};

export const $_handleDownload = (url, name) => {
  const a = document.createElement('a');
  a.href = url;
  a.download = name;
  a.click();
  // 釋放記憶體
  a.href = '';
};

export const $_parseFileToBase64 = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = () => resolve(reader.result);
    reader.onerror = (error) => reject(error);
  });
};

export const $_parseFileToJson = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.readAsText(file, 'UTF-8');
    reader.onload = () => resolve(reader.result);
    reader.onerror = (error) => reject(error);
  });
};

export const $_fetchOrgListOption = () => {
  const store = useStore();

  const formData = new FormData();
  formData.append('Count', '1000');
  formData.append('Page', '0');
  formData.append('Type', '1');
  // Type 0: 所有list 1: 啟用的 list 2: 啟用＆平台管理者 list

  return store
    .dispatch('sentFile/getOrganizationList', formData)
    .then((res) => {
      if (res.Result) {
        let admin = [];
        let isAdminList = [];
        const options = res.Message.map((item) => {
          if (item.IsAdmin === '1') {
            admin = [...admin, item.OrganizationNo];
            isAdminList.push({
              label: item.OrganizationName,
              value: item.OrganizationNo,
            });
          }
          return {
            label: item.OrganizationName,
            value: item.OrganizationNo,
          };
        });

        store.commit('app/setOrgOptionsByAdmin', options);
        store.commit('app/setOrgOptions', isAdminList);
        store.commit('app/setOrgIsAdmin', admin);
      }
    });
};
