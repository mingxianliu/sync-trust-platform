<template>
  <q-dialog :value="props.value" @click="handleClose">
    <q-card
      style="
        min-width: 800px;
        max-width: 98vw;
        min-height: 520px;
        padding: 48px 48px 36px 48px;
      "
    >
      <q-card-section class="flex justify-between items-center q-mb-lg">
        <div class="text-h4 q-mb-none">{{ props.title }}</div>
        <q-btn
          icon="close"
          color="grey-6"
          flat
          round
          @click.stop="handleClose"
        />
      </q-card-section>

      <q-separator spaced />

      <q-card-section class="q-gutter-lg q-mb-lg" style="font-size: 1.15rem">
        <div v-for="item in IpfsInfo" :key="item.label" class="q-mb-md">
          <template v-if="item.label === '狀態'">
            <div class="text-subtitle1 q-mb-xs">
              <b>{{ item.label }}：</b>
              <q-badge
                :color="item.value === '成功' ? 'positive' : 'negative'"
                :label="item.value"
                class="q-ml-sm q-px-md q-py-xs text-body1"
                style="font-size: 1.1em"
              />
            </div>
          </template>
          <template v-else-if="item.label === '完整內容'">
            <div class="text-subtitle1 q-mb-xs">
              <b>{{ item.label }}：</b>
            </div>
            <q-card
              flat
              bordered
              class="bg-grey-2 q-pa-lg q-mb-md"
              style="
                font-family: monospace;
                font-size: 1.15em;
                word-break: break-all;
                max-height: 320px;
                overflow-y: auto;
              "
            >
              {{ item.value }}
            </q-card>
          </template>
          <template v-else>
            <div>
              <b>{{ item.label }}：</b
              ><span class="break-all">{{ item.value }}</span>
            </div>
          </template>
        </div>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, watch } from 'vue';

const emit = defineEmits(['close']);

const props = defineProps({
  value: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: '',
  },
  data: {
    type: Object,
    default: () => {},
  },
});

const handleClose = () => {
  emit('close');
  clear();
};
const clear = () => {};

const IpfsInfo = ref([]);

watch(
  () => props.value,
  () => {
    IpfsInfo.value = [];
    Object.entries(props.data).forEach(([key, value]) => {
      IpfsInfo.value = [
        ...IpfsInfo.value,
        {
          label: key,
          value,
        },
      ];
    });
  },
);
</script>

<style lang="scss" scoped>
.word-break {
  word-break: break-word;
}
.break-all {
  word-break: break-all;
}
</style>
