<template>
  <q-dialog persistent :model-value="props.show" @click="emit('close')">
    <q-card style="width: 500px; max-width: 60vw">
      <q-form greedy @submit.prevent="submit">
        <q-card-section>
          <h6 class="q-ma-none text-main-color">編輯群組</h6>
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
import { ref, watch } from 'vue';
import { useStore } from 'vuex';
import { $_successNotify, $_errorNotify } from 'src/mixin/common';
import GroupForm from 'src/components/UserManagement/GroupForm';

const emit = defineEmits(['close', 'updateList']);
const store = useStore();

const console = window.console;

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  groupNo: {
    type: String,
    required: true,
  },
});

const groupData = ref({
  groupNo: null,
  groupName: null,
  org: null,
  isSend: {
    val: false,
    isDisable: false,
  },
  isReceive: {
    val: false,
    isDisable: false,
  },
});

const groupInfo = ref({});

const fetchGroupInfoByGroupNo = async () => {
  const formData = new FormData();
  formData.append('GroupNo', props.groupNo);
  const res = await store.dispatch('group/getGroupByGroupNo', formData);
  if (res.Result) {
    groupInfo.value = res.Message;
    groupData.value.groupNo = res.Message.GroupNo;
    groupData.value.groupName = res.Message.GroupName;
    groupData.value.org = res.Message.OrganizationNo;
    groupData.value.isSend.val = res.Message.IsSend === '1';
    groupData.value.isReceive.val = res.Message.IsReceive === '1';
    return;
  }
  $_errorNotify('取得資料失敗');
};

const submit = async () => {
  if (!validate()) return;

  const formData = new FormData();
  formData.append('GroupName', groupData.value.groupName);
  formData.append('Status', groupInfo.value.Status);
  formData.append('IsSend', groupData.value.isSend.val ? '1' : '0');
  formData.append('IsReceive', groupData.value.isReceive.val ? '1' : '0');
  formData.append('GroupId', groupInfo.value.ID);
  formData.append('GroupNo', groupData.value.groupNo);
  formData.append('OrgNo', groupData.value.org.value ?? groupData.value.org);

  const res = await store.dispatch('group/postUpdateGroup', formData);
  if (res.Result) {
    $_successNotify('Successful Operation');
    emit('updateList');
    handleClose();
    return;
  }
  const error = Object.values(res.Errors).join(',');
  $_errorNotify(error);
};

const validate = () => {
  if (props.groupNo.includes('_UNASSIGNED')) {
    $_errorNotify('無法修改未分配群組');
    return false;
  }
  return true;
};

const handleClose = () => {
  emit('close');
};

watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      fetchGroupInfoByGroupNo();
    }
  },
);

watch(
  () => groupData,
  (newVal) => {
    console.log(newVal);
  },
);
</script>

<style lang="scss" scoped></style>
