<template>
  <q-dialog persistent :model-value="props.show" @click="handleClose">
    <q-card style="width: 500px; max-width: 60vw">
      <q-form greedy @submit.prevent="submit">
        <q-card-section>
          <h6 class="q-ma-none text-main-color">新增群組</h6>
        </q-card-section>

        <GroupForm v-model="groupData" />

        <q-separator inset />

        <q-card-actions align="right" class="q-my-lg q-mr-sm">
          <q-btn
            color="indigo-1"
            text-color="main-color"
            label="取消"
            padding="sm xl"
            unelevated
            @click="handleClose"
          />
          <q-btn
            type="submit"
            color="main-color"
            label="確認"
            unelevated
            padding="sm xl"
          />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref } from 'vue';
import { useStore } from 'vuex';
import { $_successNotify, $_errorNotify } from 'src/mixin/common';
import GroupForm from 'src/components/UserManagement/GroupForm';

const emit = defineEmits(['close', 'updateList']);
const store = useStore();

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
});

const groupData = ref({
  groupNo: null,
  groupName: null,
  org: null,
  isSend: {
    val: false,
    isDisable: true,
  },
  isReceive: {
    val: false,
    isDisable: true,
  },
});

const submit = async () => {
  const formData = new FormData();
  formData.append('GroupNo', groupData.value.groupNo);
  formData.append('GroupName', groupData.value.groupName);
  formData.append('Status', 1);
  formData.append('OrgNo', groupData.value.org.value);
  formData.append('IsSend', groupData.value.isSend.val ? '1' : '0');
  formData.append('IsReceive', groupData.value.isReceive.val ? '1' : '0');
  formData.append('IsAdmin', "0");

  const res = await store.dispatch('group/postAddGroup', formData);
  if (res.Result) {
    $_successNotify('Successful Operation');
    emit('updateList');
    handleClose();
    return;
  }
  const error = Object.values(res.Errors).join(",")
  $_errorNotify(error);
};

const handleClose = () => {
  emit('close');
  clear();
};

const clear = () => {
  groupData.value = {
    groupNo: null,
    groupName: null,
    org: null,
    isSend: {
      val: false,
      isDisable: true,
    },
    isReceive: {
      val: false,
      isDisable: true,
    },
  };
};
</script>

<style lang="scss" scoped></style>
