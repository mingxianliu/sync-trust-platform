<template>
  <q-form greedy @submit="fetchResetPassword">
    <div class="login-page flex row">
      <div class="col-7 column flex-center">
        <div class="title">
          <img src="~assets/img/logo_bg.png" alt="TASA" />
          <p>TASA</p>
          <p>Blockchian-IPFS</p>
          <p>Platform</p>
        </div>
      </div>

      <div class="col-5 flex flex-center">
        <q-card class="login-box">
          <q-card-section class="q-pa-none">
            <p class="login-title">重置密碼</p>
            <AccountPasswordInput
              :user-data="password"
              @update-type="handleType"
            />

            <q-btn
              label="送出"
              :disable="!isDisable"
              color="main-color"
              text-color="white"
              type="submit"
              rounded
              no-caps
              class="full-width q-my-xl login-btn"
            />
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-form>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AccountPasswordInput from 'src/components/AccountPasswordInput';
import { $_successNotify, $_errorNotify, $_isEmpty } from 'src/mixin/common';
import { useRoute, useRouter } from 'vue-router';
import { useStore } from 'vuex';

const store = useStore();
const route = useRoute();
const router = useRouter();

const isDisable = computed(() => {
  return (
    password.value.psw.val.length > 0 &&
    password.value.confirm_password.val.length > 0
  );
});

onMounted(() => {});

const password = ref({
  psw: {
    label: '新密碼',
    placeholder: '請輸入新密碼',
    val: '',
    type: 'password',
    isNumberCheck: false,
    isCaseCheck: false,
    lazyRules: false,
    dense: true,
    rules: [
      () => {
        // 包含大小寫
        // const reg = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{1,}$/;
        // let isCase = reg.test(val);
        // password.value.psw.isNumberCheck = val.length >= 8 ? true : false;
        // password.value.psw.isCaseCheck = isCase ? true : false;
      },
      $_isEmpty,
    ],
  },
  confirm_password: {
    label: '確認密碼',
    placeholder: '請再次輸入新密碼',
    val: '',
    type: 'password',
    lazyRules: true,
    dense: true,
    rules: [
      (val) => {
        return password.value.psw.val === val ? true : 'invalid';
      },
      $_isEmpty,
    ],
  },
});

const handleType = (typeObj) => {
  const { type, name } = typeObj;
  password.value[name].type = type;
};

const fetchResetPassword = () => {
  const { psw } = password.value;
  let query = '';
  Object.keys(route.query).map((item) => {
    query = item;
  });

  let formData = new FormData();
  formData.append('Password', psw.val);
  formData.append('ResetKey', query);

  return store
    .dispatch('login/putResetPassword', formData)
    .then(() => {
      $_successNotify('重置密碼成功');
      router.push('/login');
    })
    .catch((err) => {
      $_errorNotify(err.message);
    });
};
</script>

<style lang="scss" scoped>
.login-page {
  min-height: 100vh;
  background: url(src/assets/img/login_img.png);
  background-color: #000;
  background-size: cover;

  .title {
    font-size: 3.6875rem;
    font-weight: bold;
    color: white;
    margin: 0 0 160px -135px;
    line-height: 70px;
    p {
      margin: 0;
    }
  }
}
.login-box {
  height: 588px;
  width: 75%;
  min-width: 460px;
  max-width: 460px;
  margin-right: 100px;
  padding: 40px 24px;

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
}
.btn-box {
  margin-top: 220px;
}
</style>
