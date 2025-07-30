<template>
  <div class="q-col-gutter-md">
    <template v-for="data in props.allData" :key="data.label">
      <div :class="data.col">
        <template v-if="isTitle">
          <p class="text-bold q-mb-sm">
            {{ data.title || data.label }}
            <span v-if="data.required" class="text-red">*</span>
          </p>
        </template>

        <q-input
          v-if="data.type === 'input'"
          v-model="data.val"
          :label="data.label"
          :name="data.label || 'input-field'"
          :class="data.classes"
          :rules="data.rules"
          label-color="main-color"
          color="main-color"
          outlined
          bottom-slots
          dense
          clearable
          :readonly="data.readonly"
          @keydown.enter="
            (value) => (data.event ? handleUpdate({ value, ...data }) : '')
          "
        >
          <template v-if="data.isSearch" #append>
            <q-icon
              name="search"
              class="cursor-pointer"
              color="indigo-3"
              @click="data.event ? handleUpdate({ value, ...data }) : ''"
            />
          </template>
        </q-input>
        <q-select
          v-else-if="data.type === 'select'"
          v-model="data.val"
          :options="data.options"
          :label="data.label"
          :name="data.label || 'select-field'"
          :class="data.classes"
          :rules="data.rules"
          :map-options="data.mapOptions"
          :disable="data.disable"
          label-color="main-color"
          color="main-color"
          dropdown-icon="eva-chevron-down-outline"
          bottom-slots
          clearable
          outlined
          dense
          @update:model-value="(value) => handleUpdate({ value, ...data })"
        />
        <q-select
          v-if="data.type === 'autocomplete'"
          v-model="data.val"
          class="col-4 q-mr-sm"
          :options="data.options"
          :label="data.label"
          :name="data.label || 'autocomplete-field'"
          :class="data.classes"
          :rules="data.rules"
          use-input
          outlined
          multiple
          dense
          clearable
          hide-dropdown-icon
          @input-value="(value) => handleUpdate({ value, ...data })"
        />
        <q-input
          v-else-if="data.type === 'date'"
          v-model="data.val"
          :label="data.label"
          :name="data.label || 'date-field'"
          :placeholder="data.dateFormat"
          :class="data.classes"
          :rules="data.rules"
          dense
          outlined
          clearable
        >
          <template #append>
            <q-icon name="event" color="main-color cursor-pointer">
              <q-popup-proxy transition-show="scale" transition-hide="scale">
                <q-date v-model="data.val" :mask="data.dateFormat" minimal>
                  <q-btn v-close-popup label="Close" color="primary" flat />
                </q-date>
              </q-popup-proxy>
            </q-icon>
          </template>
        </q-input>

        <q-input
          v-else-if="data.type === 'dateTime'"
          v-model="data.val"
          :label="data.label"
          :name="data.label || 'datetime-field'"
          :placeholder="data.dateFormat"
          :class="data.classes"
          :rules="data.rules"
          dense
          outlined
          clearable
        >
          <template #prepend>
            <q-icon name="event" color="blue-grey-4 cursor-pointer">
              <q-popup-proxy
                cover
                transition-show="scale"
                transition-hide="scale"
              >
                <q-date
                  v-model="data.val"
                  :mask="data.mask || 'YYYY-MM-DD HH:mm'"
                  minimal
                >
                  <q-btn v-close-popup label="Close" color="primary" flat />
                </q-date>
              </q-popup-proxy>
            </q-icon>
          </template>

          <template #append>
            <q-icon name="access_time" color="blue-grey-4 cursor-pointer">
              <q-popup-proxy
                cover
                transition-show="scale"
                transition-hide="scale"
              >
                <q-time
                  v-model="data.val"
                  :mask="data.mask || 'YYYY-MM-DD HH:mm'"
                  format24h
                >
                  <q-btn v-close-popup label="Close" color="primary" flat />
                </q-time>
              </q-popup-proxy>
            </q-icon>
          </template>
        </q-input>

        <template v-else-if="data.type === 'radio'">
          <q-radio
            v-for="item in data.options"
            :key="item.label"
            v-model="data.val"
            :label="item.label"
            :val="item.value"
            :name="data.label || 'radio-group'"
            :rules="data.rules"
            :disable="data.isDisable"
          />
        </template>

        <template v-else-if="data.type === 'checkbox'">
          <q-checkbox
            v-if="!data.options"
            v-model="data.val"
            :label="data.label"
            :name="data.label || 'checkbox'"
            color="main-color"
          />

          <q-checkbox
            v-for="item in data.options"
            v-else
            :key="item.label"
            v-model="item.value"
            :label="item.label"
            :name="item.label || 'checkbox-group'"
            color="main-color"
          />
        </template>

        <q-file
          v-else-if="data.type === 'file'"
          v-model="data.val"
          :label="data.label"
          :name="data.label || 'file-field'"
          :class="data.classes"
          :rules="data.rules"
          :accept="data.accept"
          label-color="main-color"
          color="main-color"
          :counter="data.counter"
          outlined
          dense
          bottom-slots
          clearable
          @update:model-value="
            (val) => {
              if (data.updated) data.updated(val, data);
            }
          "
        >
          <template #prepend>
            <q-icon name="attach_file" color="indigo-3" />
          </template>
        </q-file>

        <q-input
          v-else-if="data.type === 'password'"
          v-model.trim="data.val"
          :label="data.placeholder"
          :name="data.placeholder || 'password-field'"
          :type="data.pwdType"
          :rules="data.rules"
          :lazy-rules="data.lazyRules"
          :dense="data.dense"
          label-color="main-color"
          color="main-color"
          outlined
          class="q-mb-sm"
          bottom-slots
        >
          <template #append>
            <q-icon
              v-if="data.pwdType === 'password'"
              name="eva-eye-off-2-outline"
              class="text-indigo-3 cursor-pointer"
              @click="data.pwdType = 'text'"
            />
            <q-icon
              v-else
              name="eva-eye-outline"
              class="text-indigo-3 cursor-pointer"
              @click="data.pwdType = 'password'"
            />
          </template>
        </q-input>
      </div>
    </template>
    <slot></slot>
  </div>
</template>

<script setup>
const props = defineProps({
  allData: {
    type: Object,
    default: () => {},
  },
  isTitle: {
    type: Boolean,
    default: true,
  },
});

const emits = defineEmits(['handleUpdate']);

const handleUpdate = (props) => {
  emits('handleUpdate', props);
};
</script>

<style lang="scss" scoped></style>
