<template>
  <q-dialog ref="dialogRef" @hide="onDialogHide">
    <q-card class="q-dialog-plugin">
      <q-card-section>
        <div class="text-h6">
          {{ title }}
        </div>
      </q-card-section>
      <q-card-section>
        <div>
          {{ message }}
        </div>
      </q-card-section>
      <q-card-actions align="right">
        <q-btn
          v-if="cancel?.show ?? true"
          :color="cancel?.color ?? 'primary'"
          :label="cancel.label"
          @click="onCancelClick"
        />

        <q-btn
          :color="download?.color ?? 'primary'"
          :label="download.label"
          @click="onOKClick('download')"
        />
        <q-btn
          :color="downloadWithDecrypt?.color ?? 'primary'"
          :label="downloadWithDecrypt.label"
          @click="onOKClick('downloadWithDecrypt')"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script>
import { useDialogPluginComponent } from 'quasar';

export default {
  props: {
    title: {
      type: String,
      default: '',
    },
    message: {
      type: String,
      default: '',
    },
    download: {
      type: Object,
      default: () => ({
        label: '僅下載',
        color: 'primary',
      }),
    },
    downloadWithDecrypt: {
      type: Object,
      default: () => ({
        label: '下載並解碼',
        color: 'primary',
      }),
    },
    cancel: {
      type: Object,
      default: () => ({
        show: false,
        label: '下載並解碼',
        color: 'primary',
      }),
    },
  },

  emits: [...useDialogPluginComponent.emits],

  setup() {
    const { dialogRef, onDialogHide, onDialogOK, onDialogCancel } =
      useDialogPluginComponent();

    return {
      dialogRef,
      onDialogHide,
      onOKClick(type) {
        onDialogOK({ type });
      },
      onCancelClick: onDialogCancel,
    };
  },
};
</script>
