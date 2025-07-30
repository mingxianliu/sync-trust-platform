<template>
  <q-form greedy @submit="handleLoginOrForgotPassword">
    <div class="login-page flex flex-center">
      <q-card class="login-box">
        <div class="login-title-area">
          <p class="main-title">Syckey One Trust</p>
          <p class="sub-title">智慧建築區塊鏈平台</p>
        </div>
        <q-tab-panels v-model="currenTab" animated>
          <q-tab-panel name="login">
            <q-card-section class="q-pa-none">
              <p class="login-title">登入</p>
              <AccountPasswordInput
                :user-data="userData"
                @update-type="handleType"
              />
              <p
                class="text-right q-my-md"
                @click="handleTab('forgotPassword')"
              >
                <span class="cursor-pointer">忘記密碼？</span>
              </p>
              <q-btn
                label="登入"
                :disable="!isDisable"
                color="main-color"
                text-color="white"
                type="submit"
                rounded
                no-caps
                class="full-width q-my-xl login-btn"
              />
            </q-card-section>
          </q-tab-panel>

          <q-tab-panel name="forgotPassword">
            <q-card-section class="q-pa-none">
              <p class="login-title">忘記密碼</p>
              <AccountPasswordInput
                :user-data="userMail"
                @update-type="handleType"
              />
              <div class="btn-box row">
                <q-btn
                  label="返回"
                  color="indigo-1"
                  text-color="main-color"
                  type="button"
                  no-caps
                  class="login-btn col q-mr-md"
                  @click="handleTab('login')"
                />
                <q-btn
                  label="重設密碼"
                  color="main-color"
                  text-color="white"
                  type="submit"
                  no-caps
                  class="login-btn col"
                />
              </div>
            </q-card-section>
          </q-tab-panel>
        </q-tab-panels>
      </q-card>
    </div>
  </q-form>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import AccountPasswordInput from 'src/components/AccountPasswordInput';
import { $_isEmpty } from 'src/mixin/common';
import { $_successNotify, $_errorNotify, $_infoNotify } from 'src/mixin/common';
import { useRouter, useRoute } from 'vue-router';
import { useStore } from 'vuex';

const store = useStore();
const router = useRouter();
const route = useRoute();

const isDisable = computed(() => {
  return userData.id.val.length > 0 && userData.psw.val.length > 0;
});

const currenTab = ref('login');
const handleTab = (val) => {
  currenTab.value = val;
  switch (val) {
    case 'forgotPassword':
      return router.push({ path: '/forgotPassword' });
    default:
      userMail.id.val = '';
      return router.push({ path: '/login' });
  }
};
const routeName = computed(() => {
  return `${route.name[0].toLowerCase()}${route.name.slice(1)}`;
});
watch(routeName, () => {
  currenTab.value = routeName.value;
});
onMounted(() => {
  currenTab.value = routeName.value;
});

const userMail = reactive({
  id: {
    label: '帳號',
    placeholder: '請輸入e-Mail',
    type: 'input',
    val: '',
    col: 'col',
    rules: [(val) => $_isEmpty(val)],
  },
});

const userData = reactive({
  id: {
    label: '帳號',
    placeholder: '請輸入帳號',
    val: '',
    type: 'text',
    lazyRules: 'ondemand',
    rules: [],
    // rules: [(val) => $_checkEmailRule(val)],
  },
  psw: {
    label: '密碼',
    placeholder: '請輸入密碼',
    val: '',
    type: 'password',
    lazyRules: 'ondemand',
    rules: [],
  },
});

const handleType = ({ type, name }) => {
  userData[name].type = type;
};

const meta = computed(() => {
  return store.getters['app/getAuthMeta'];
});

const handleLoginOrForgotPassword = () => {
  if (userMail.id.val.length > 1) {
    return fetchForgotPassword();
  }
  fetchLogin();
};

const fetchLogin = () => {
  const { id, psw } = userData;
  let formData = new FormData();
  formData.append('Acc', id.val);
  formData.append('Pwd', psw.val);

  return store
    .dispatch('login/getToken', formData)
    .then((res) => {
      if (res.Result) {
        store.commit('app/setAuthToken', res.Message.Token);
        delete res.Message.Token;
        store.commit('app/setAuthMeta', res.Message.Data);
        handlePermissions();
        localStorage.removeItem('isPasswordOverdue');

        if (isPasswordOverdue(res.Message.Data.ChangePWTime)) {
          $_infoNotify('Please change your password');
          localStorage.setItem('isPasswordOverdue', 1);
          router.push('/setting/change-password');
          return;
        }

        $_successNotify('Login Success');
        router.push('/');
        return;
      }
      $_errorNotify('Account Or Password Incorrect');
      store.commit('app/logout');
    })
    .catch(() => {});
};

const fetchForgotPassword = () => {
  let formData = new FormData();
  formData.append('Email', userMail.id.val);

  return store
    .dispatch('login/putForgotPassword', formData)
    .then(() => {
      $_successNotify('忘記密碼 mail 驗證信已寄出，請收信');
    })
    .catch((err) => {
      $_errorNotify(err.message);
    });
};

const handlePermissions = () => {
  const permission = ['pass'];
  Object.keys(meta.value).map((item) => {
    if (
      item === 'IsAdmin' &&
      meta.value.IsAdmin === '1' &&
      meta.value.IsAdminOrg === '1'
    ) {
      permission.push('isAdmin');
    }
    if (item === 'IsAdmin' && meta.value.IsAdmin === '1') {
      // 群組管理員
      permission.push('isOrgAdmin');
    }
    if (item === 'IsSend' && meta.value.IsSend === '1') {
      permission.push('isSend');
    }
    if (item === 'IsReceive' && meta.value.IsReceive === '1') {
      permission.push('isReceive');
    }
  });
  store.commit('app/setPermissions', permission);
};

const isPasswordOverdue = (date) => {
  if (date === '' || date === null) return true;
  const overdueDays = 90;
  const now = new Date();
  const changeDate = new Date(date);
  const subDays = parseInt(Math.abs(now - changeDate) / 1000 / 60 / 60 / 24);
  return subDays > overdueDays;
};
</script>

<style lang="scss" scoped>
.login-page {
  min-height: 100vh;
  background: #fff;
}
.flex-center {
  justify-content: center;
  align-items: center;
}
.login-box {
  width: 400px;
  max-width: 90vw;
  padding: 40px 24px;
}
.login-title-area {
  text-align: center;
  margin-bottom: 32px;
}
.main-title {
  font-size: 2rem;
  font-weight: bold;
  margin-bottom: 8px;
  color: $primary;
}
.sub-title {
  font-size: 1.2rem;
  color: $primary;
  margin-bottom: 24px;
}
.login-title {
  font-weight: bold;
  font-size: 1.5rem;
  text-align: center;
  margin-bottom: 40px;
}
span {
  text-decoration: underline;
  color: $blue6;
}
.login-btn {
  font-size: 1rem;
}
.btn-box {
  margin-top: 40px;
}
</style>
