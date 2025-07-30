<template>
  <q-file
    ref="filePicker"
    :model-value="selectedFiles"
    label-color="main-color"
    color="main-color"
    outlined
    dense
    multiple
    :directory="props.isDirectory"
    :webkitdirectory="props.isDirectory"
    @update:model-value="onFilePickerSelected"
  >
    <template #selected>
      <q-chip
        v-for="(name, findex) in fileChipList"
        :key="findex"
        removable
        square
        :tabindex="findex"
        color="indigo-1"
        text-color="blue6"
        class="q-ma-none q-mr-xs"
        @remove="onFileRemove(findex)"
      >
        {{ name }}
      </q-chip>
    </template>

    <template #append>
      <button
        class="select-file-btn text-blue6 cursor-pointer"
        @click="onSelectButtonClick"
      >
        選擇
      </button>
    </template>
  </q-file>
</template>

<script setup>
import { computed, ref } from 'vue';
const emits = defineEmits([
  'update:modelValue',
  'removeFileByIndex',
  'removeAllFile',
]);

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
    required: true,
  },
  isDirectory: {
    type: Boolean,
    default: false,
  },
});

const filePicker = ref(null);

const selectedFiles = computed(() => {
  if (props.isDirectory) {
    return props.modelValue.flat();
  }
  return props.modelValue;
});

const fileChipList = computed(() => {
  if (props.modelValue.length === 0) return [];

  if (props.isDirectory) {
    const directorySet = new Set();
    selectedFiles.value.forEach((x) => {
      // eslint-disable-next-line no-underscore-dangle
      directorySet.add(x.__key.split('/')[0]);
    });
    return Array.from(directorySet);
  }
  return selectedFiles.value.map((x) => x.name);
});

const onSelectButtonClick = () => {
  filePicker.value.pickFiles();
};

const onFilePickerSelected = (value) => {
  if (props.isDirectory) {
    return emits('update:modelValue', [...props.modelValue, value]);
  }
  return emits('update:modelValue', [...props.modelValue, ...value]);
};

const onFileRemove = (index) => {
  emits('removeFileByIndex', index);
};
</script>

<style scoped lang="scss">
.select-file-btn {
  display: flex;
  align-items: center;
  border: none;
  font-size: 14px;
  padding: 7px 12px;
  border-radius: 6px;
  background: $blue4;
  box-sizing: border-box;
  height: 28px;
}
</style>
