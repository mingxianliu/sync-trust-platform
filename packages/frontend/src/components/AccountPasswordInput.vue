<template>
  <template v-for="(data, index) in props.userData" :key="index">
    <p class="text-bold q-mb-xs">{{ data.label }}</p>
    <q-input
      v-model.trim="data.val"
      :label="data.placeholder"
      :type="data.type"
      :rules="data.rules"
      :lazy-rules="data.lazyRules"
      :dense="data.dense"
      label-color="main-color"
      color="main-color"
      outlined
      class="q-mb-sm"
      bottom-slots
    >
      <template
        v-if="
          index === 'psw' ||
          index === 'confirm_password' ||
          index === 'old_password'
        "
        #append
      >
        <q-icon
          v-if="data.type === 'password'"
          name="eva-eye-off-2-outline"
          class="text-indigo-3 cursor-pointer"
          @click="handleType('text', index)"
        />
        <q-icon
          v-else
          name="eva-eye-outline"
          class="text-indigo-3 cursor-pointer"
          @click="handleType('password', index)"
        />
      </template>
    </q-input>

    <template v-if="data.needPasswordInfo">
      <div class="q-mb-lg">
        <p class="q-mb-sm">
          <q-icon
            name="eva-checkmark-circle-2-outline"
            size="22px"
            :color="data.isNumberCheck ? 'teal-13' : 'grey-8'"
            class="q-mr-sm"
          />{{ t('at_least_8_characters') }}
        </p>
        <p class="q-mb-sm">
          <q-icon
            name="eva-checkmark-circle-2-outline"
            size="22px"
            :color="data.isCaseCheck ? 'teal-13' : 'grey-8'"
            class="q-mr-sm"
          />{{ t('at_least_one_uppercase_and_one_lower_case_letter') }}
        </p>
        <p class="text-grey-6">{{ t('at_least_one_number') }}</p>
      </div>
    </template>
  </template>
</template>

<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const emits = defineEmits(['updateType']);

const props = defineProps({
  userData: {
    type: Object,
    default: () => {},
    require: true,
  },
});

const handleType = (type, name) => {
  emits('updateType', { type, name });
};
</script>

<style lang="scss" scoped></style>
