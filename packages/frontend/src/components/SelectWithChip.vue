<template>
  <q-select
    :model-value="props.modelValue"
    :options="props.options"
    label-color="main-color"
    color="main-color"
    dropdown-icon="eva-chevron-down-outline"
    clearable
    outlined
    dense
    v-bind="props"
    @update:model-value="(value) => emits('update:modelValue', value)"
  >
    <template #selected-item="scope">
      <q-chip
        v-if="scope.opt.label && scope.index <= props.chipLimit"
        removable
        square
        :tabindex="scope.tabindex"
        color="indigo-1"
        text-color="blue6"
        class="q-ma-none q-mr-xs"
        v-bind="chipProps"
        @remove="scope.removeAtIndex(scope.index)"
      >
        {{
          scope.index === props.chipLimit
            ? `+${exceedLimitChip}...`
            : scope.opt.label
        }}
      </q-chip>
    </template>
  </q-select>
</template>

<script setup>
import { computed } from 'vue';
const emits = defineEmits(['update:modelValue']);

const props = defineProps({
  modelValue: {
    type: [String, Number, Array, null],
    default: '',
    required: true,
  },
  options: {
    type: Array,
    default: () => [],
  },
  chipProps: {
    type: Object,
    default: () => ({}),
  },
  chipLimit: {
    type: Number,
    default: Number.MAX_SAFE_INTEGER,
  },
});

const exceedLimitChip = computed(
  () => props.modelValue?.length - props.chipLimit || 0,
);
</script>
