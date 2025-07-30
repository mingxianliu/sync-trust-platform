<template>
  <div>
    <q-form greedy @submit="handleChangePassword">
      <div class="row q-col-gutter-lg">
        <div class="col-5">
          <p class="text-blue6 text-bold">變更密碼</p>
          <q-card>
            <q-card-section>
              <AccountPasswordInput
                :user-data="password"
                @update-type="handleType"
              />
              <div class="text-right">
                <q-btn
                  color="main-color"
                  label="確認修改"
                  class="btn-height q-ml-md q-mt-sm"
                  unelevated
                  type="submit"
                />
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>
    </q-form>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import AccountPasswordInput from 'src/components/AccountPasswordInput';
import { $_isEmpty } from 'src/mixin/common';
import { useStore } from 'vuex';
import {
  $_successNotify,
  $_errorNotify,
  $_parseFileToBase64,
} from 'src/mixin/common';
import { useRouter } from 'vue-router';

const store = useStore();
const router = useRouter();
const userImg = ref(null);

const password = ref({
  old_password: {
    label: '原始密碼',
    placeholder: '請輸入原始密碼',
    val: '',
    type: 'password',
    isNumberCheck: false,
    isCaseCheck: false,
    lazyRules: false,
    dense: true,
    rules: [$_isEmpty],
  },
  psw: {
    label: '新密碼',
    placeholder: '十二位數，且含大小寫字母、數字',
    val: '',
    type: 'password',
    isNumberCheck: false,
    isCaseCheck: false,
    lazyRules: false,
    dense: true,
    rules: [
      (val) => {
        // 包含大小寫
        const reg = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{12,}$/;
        let isCase = reg.test(val);
        return isCase ? true : '必須十二位數，且含大小寫字母、數字';
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

const meta = computed(() => {
  return store.getters['app/getAuthMeta'];
});

const passwordChangedOneDay = computed(() => {
  const now = new Date();
  const changeDate = new Date(meta.value.ChangePWTime);
  const subDays = parseInt(Math.abs(now - changeDate) / 1000 / 60 / 60 / 24);
  return subDays < 1;
});

const handleType = (typeObj) => {
  const { type, name } = typeObj;
  password.value[name].type = type;
};

const handleChangePassword = () => {
  const { old_password, psw } = password.value;
  const formData = new FormData();
  formData.append('OldPwd', old_password.val);
  formData.append('NewPwd', psw.val);
  if (passwordChangedOneDay.value) {
    return $_errorNotify('密碼不可於1日之內重新變更');
  }
  fetchChangePassword(formData, '變更成功，請重新登入', true);
};

const handleChangeAvatar = async () => {
  const pic = await $_parseFileToBase64(userImg.value[0]);
  const formData = new FormData();
  formData.append('MemberPic', pic);

  fetchChangePassword(formData, '變更成功, 於下次登入時更新', false);
};
const fetchChangePassword = (formData, msg, isLogin) => {
  return store
    .dispatch('app/putChangPassword', formData)
    .then((res) => {
      if (res.Result) {
        $_successNotify(msg);
        if (isLogin) {
          setTimeout(() => {
            store.commit('app/logout');
            router.push({ name: 'Login' });
          }, 3000);
        }

        return;
      }
      $_errorNotify('變更失敗');
    })
    .catch(() => {
      $_errorNotify('變更失敗');
    });
};
</script>

<style lang="scss" scoped></style>
