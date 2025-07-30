<template>
  <q-dialog persistent :value="props.value" @click="handleClose">
    <q-card style="width: 400px; max-width: 60vw">
      <!-- <q-card-section>
        <h6 class="q-ma-none text-main-color">File Loading...</h6>
      </q-card-section> -->

      <q-card-section class="q-my-lg">
        <div class="flex flex-center column view-box">
          <div class="q-my-md">
            <div v-if="props.status === 0" class="column flex-center">
              <q-spinner-gears
                color="blue-grey-7 q-mb-md"
                style="font-size: 4em"
              />
              <b class="q-ml-sm text-blue-grey-7">檔案加密上傳中...</b>
              <b class="q-ml-sm text-blue-grey-7">
                公鑰驗證：
                <span
                  class="public-key-verification"
                  :class="{ 'is-success': props.publicKeyVerification === '成功' }"
                >
                  {{ props.publicKeyVerification }}
                </span>
              </b>
            </div>

            <!-- <div v-if="props.status === 1">
              <q-icon name="o_cloud_upload" size="lg" color="blue-grey-7" />
              <b class="q-ml-sm text-blue-grey-7">檔案上傳中</b>
            </div> -->

            <div v-if="props.status === 2" class="column flex-center">
              <q-spinner-gears
                color="blue-grey-7  q-mb-md"
                style="font-size: 4em"
              />
              <b class="q-ml-sm text-blue-grey-7">檔案解密中</b>
            </div>

            <div v-if="props.status === 3" class="column flex-center">
              <q-spinner-gears
                color="blue-grey-7 q-mb-md"
                style="font-size: 4em"
              />
              <b class="q-ml-sm text-blue-grey-7">檔案下載中</b>
            </div>

            <div class="text-center">{{ time }}</div>
          </div>
          <q-linear-progress
            v-if="
              props.status === 0 || props.status === 2 || props.status === 3
            "
            size="50px"
            :value="progress"
            color="light-green-6"
            class="progress-box"
            stripe
            rounded
          >
            <div class="absolute-full flex flex-center">
              <q-badge
                color="white"
                text-color="light-green-6"
                :label="progressLabel"
              />
            </div>
          </q-linear-progress>
          <template
            v-if="
              props.status === 0 || props.status === 2 || props.status === 3
            "
          >
            <q-btn
              outline
              color="grey-6"
              label="取消"
              class="q-mt-lg"
              @click="handleCancelUpload"
            />
          </template>
        </div>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { $_errorNotify } from 'src/mixin/common';
import { LocalStorage } from 'quasar';

const emit = defineEmits(['close', 'updateProgressInfo', 'uploadStop']);

const props = defineProps({
  value: {
    type: Boolean,
    default: false,
  },
  progressInfo: {
    type: Number,
    default: 0,
  },
  // 0: 加密, 1: 上傳, 2: 解密, 3: 下載
  status: {
    type: Number,
    default: 0,
  },
  publicKeyVerification: {
    type: String,
    default: "-",
  },
});
const handleClose = () => {
  emit('close');
  clear();
};
const clear = () => {};

const progress = computed(() => {
  return props.progressInfo / 100;
});

const progressLabel = computed(() => {
  return `${(progress.value * 100).toFixed(2)} %`;
});

let setMinute = ref(0);
const time = ref('00:00:00');
const checkTime = () => {
  setMinute.value += 1;
  const hour = Math.floor(setMinute.value / 3600);
  const minute = Math.floor(Math.floor(setMinute.value % 3600) / 60);
  const second = setMinute.value % 60;
  time.value = `${hour < 10 ? '0' + hour : hour}:${
    minute < 10 ? '0' + minute : minute
  }:${second < 10 ? '0' + second : second}`;
};

const handleCancelUpload = () => {
  window.stop();
  emit('uploadStop');
  LocalStorage.set('_isStop', 'true', { path: '/' });
  handleClose();
  $_errorNotify('已取消');
};

const step = ref(0);
const handleTime = () => {
  step.value = setInterval(() => {
    checkTime();
  }, 1000);
};

watch(
  () => props.value,
  (val) => {
    if (val) {
      return handleTime();
    }
    clearInterval(step.value);
    setMinute.value = 0;
    time.value = '00:00:00';
  },
);
</script>

<style lang="scss" scoped>
.view-box {
  margin: auto;

  .progress-box {
    width: 90%;
  }
}

.public-key-verification {
  color: #ff0000;
  &.is-success {
    color: #5be71f;
  }
}
</style>
