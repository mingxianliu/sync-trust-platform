<template>
  <q-card>
    <q-card-section class="flex justify-between q-pt-md bg-indigo-1 box-border">
      <p class="q-mt-sm q-mb-none text-blue6 text-bold">數據檔上鏈</p>
      <div>
        <q-btn
          color="main-color"
          label="確認上鏈"
          class="col btn-height self-center q-ml-md q-mt-sm"
          unelevated
          @click="handleUpload"
        />
      </div>
    </q-card-section>
    <q-separator />
    <q-markup-table flat>
      <thead class="bg-indigo-1 text-blue6">
        <tr>
          <th class="text-left th-event-num">項目</th>
          <th class="text-left th-filename">檔名</th>
          <th class="text-left th-receiver">接收人</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(event, index) in fileSendEventList" :key="index">
          <td class="text-left vertical-top">
            <span class="event-num">{{ index + 1 }}</span>
          </td>
          <td class="text-left vertical-top">
            <div class="row">
              <span class="col-auto row items-end q-pr-xs text-blue6">
                {{ event.fileType === 'directory' ? '資料夾' : '檔案' }}
              </span>
              <FilePickerWithChip
                v-model="event.file"
                class="col"
                :is-directory="event.fileType === 'directory'"
                @remove-file-by-index="
                  (fileIndex) => removeFileByIndex(index, fileIndex)
                "
                @remove-all-file="removeAllFile(index)"
              />
            </div>
          </td>
          <td class="text-left">
            <div
              v-for="(receiver, rindex) in event.receiverList"
              :key="rindex"
              class="receiver"
            >
              <q-select
                v-model="receiver.organize"
                :options="organizeOptions"
                label="組織"
                class="q-pr-md"
                label-color="main-color"
                color="main-color"
                dropdown-icon="eva-chevron-down-outline"
                clearable
                outlined
                dense
                @update:model-value="
                  (value) => {
                    fetchOrganizeMember(value?.value ?? '', index, rindex);
                    fetchOrganizeGroupByOrgNo(
                      value?.value ?? '',
                      index,
                      rindex,
                    );
                  }
                "
              />
              <q-select
                v-model="receiver.group.value"
                :options="receiver.group.options"
                label="群組"
                class="q-pr-md"
                label-color="main-color"
                color="main-color"
                dropdown-icon="eva-chevron-down-outline"
                clearable
                outlined
                dense
                @update:model-value="
                  (value) =>
                    value?.value
                      ? fetchGroupMember(value?.value ?? '', index, rindex)
                      : fetchOrganizeMember(
                          receiver.organize?.value,
                          index,
                          rindex,
                        )
                "
              />
              <SelectWithChip
                :model-value="receiver.user.values"
                standout
                :label="receiver.user.values?.length > 0 ? undefined : '接收人'"
                class="q-pr-md"
                multiple
                :options="receiver.user.options"
                :chip-limit="3"
                clearable
                @update:model-value="
                  (value) => onReceiverSelect(value, receiver)
                "
              />
              <q-btn
                v-if="rindex === event.receiverList.length - 1"
                color="indigo-1"
                text-color="blue6"
                label="新增接收方"
                class="btn-height"
                unelevated
                icon="add"
                @click="addReceiver(index)"
              />
            </div>
          </td>
        </tr>
      </tbody>
    </q-markup-table>
    <q-btn
      icon="eva-plus-outline"
      color="main-color"
      label="新增項目"
      class="btn-height self-center"
      flat
    >
      <q-menu>
        <q-list dense style="min-width: 200px">
          <q-item v-close-popup clickable @click="addEvent('file')">
            <q-item-section>檔案</q-item-section>
          </q-item>
          <q-item v-close-popup clickable @click="addEvent('directory')">
            <q-item-section>資料夾</q-item-section>
          </q-item>
        </q-list>
      </q-menu>
    </q-btn>
  </q-card>
  <Progress
    v-model="dialogShow"
    :value="dialogShow"
    :progress-info="progress"
    :status="0"
    :public-key-verification="publicKeyVerification"
    @close="dialogShow = false"
    @update-progress-info="
      () => {
        progress = 0;
      }
    "
    @upload-stop="
      () => {
        isStop = true;
      }
    "
  />
</template>

<script setup>
import Progress from 'src/components/Progress';
import SelectWithChip from 'src/components/SelectWithChip.vue';
import FilePickerWithChip from 'src/components/FilePickerWithChip.vue';
import { $_handleFile, $_encryptChunks, $_sha512 } from 'src/mixin/key';
import { $_errorNotify, $_successNotify } from 'src/mixin/common';
import { toZip } from 'src/mixin/compress';
import { computed, onMounted, reactive, ref } from 'vue';
import { useStore } from 'vuex';

const store = useStore();

const nspoEncodePublicKey = ref(null);
const nspoEncodeMemberNo = ref(null);

const dialogShow = ref(false);
const progress = ref(0);
const isStop = ref(false);
const publicKeyVerification = ref('-');

const progressCounter = reactive({
  now: 0,
  all: 0,
});

const props = defineProps({
  organizeOptions: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['on-upload']);

const fileSendEventList = reactive([]);

const selfPrivateKey = computed(() => {
  return store.getters['app/getPrivateKey'] || '未設定';
});

const organizeOptions = computed(() => {
  return props.organizeOptions.filter((item) => item.value !== 'NSPOEncode');
});

const addEvent = (fileType) => {
  fileSendEventList.push({
    fileType,
    file: [],
    receiverList: [
      {
        organize: '',
        group: {
          options: [],
          value: '',
        },
        user: {
          options: [],
          values: [],
        },
      },
    ],
  });
};

const addReceiver = (index) => {
  fileSendEventList[index].receiverList.push({
    organize: '',
    group: {
      options: [],
      value: '',
    },
    user: {
      options: [],
      values: [],
    },
  });
};

const fetchOrganizeMember = async (OrgNo, index1, index2) => {
  const formData = new FormData();
  formData.append('OrgNo', OrgNo);
  formData.append('Exclude', '1');
  // Exclude: 1 代表不包含自己 0 代表包含自己
  const res = await store.dispatch('sentFile/getOrganizationMember', formData);
  if (res.Result) {
    const list = res.List.map((item) => ({
      label: item.MemberName,
      value: item.MemberNo,
    }));
    fileSendEventList[index1].receiverList[index2].user.options = list;
  }
};

const fetchOrganizeGroupByOrgNo = async (OrgNo, index1, index2) => {
  fileSendEventList[index1].receiverList[index2].group.value = '';

  const formData = new FormData();
  formData.append('Type', 'other');
  formData.append('Count', '99999');
  formData.append('Desc', '1');
  formData.append('OrgNo', OrgNo);
  const res = await store.dispatch('group/getGroupList', formData);
  if (res.Result) {
    const list = res.Message.filter(
      (item) => item.OrganizationNo === OrgNo,
    ).map((item) => ({
      label: item.GroupName,
      value: item.GroupNo,
    }));
    fileSendEventList[index1].receiverList[index2].group.options = list;
  }
};

const fetchGroupMember = async (groupNo, index1, index2) => {
  const formData = new FormData();
  formData.append('GroupNo', groupNo);
  formData.append('Exclude', 1);

  // Exclude: 1 代表不包含自己 0 代表包含自己
  const res = await store.dispatch('user/getMemberByGroupNo', formData);
  if (res.Result) {
    const list = res.List.map((item) => ({
      label: item.MemberName,
      value: item.MemberNo,
    }));
    fileSendEventList[index1].receiverList[index2].user.options = list;
  }
};

const removeFileByIndex = (index, fileIndex) => {
  fileSendEventList[index].file.splice(fileIndex, 1);
};

const removeAllFile = (index) => {
  fileSendEventList[index].file = [];
};

const getCurrentProgress = () => {
  const p = Math.round((progressCounter.now / progressCounter.all) * 100);
  if (!isFinite(p)) return 0;
  return p > 100 ? 100 : p;
};

// 以下處理上傳邏輯
const handleChunks = async (fileNo, file, otherFileNo = []) => {
  const fileSize = file.size;

  let nChunk = 12800;
  let nOutput = 0;
  let index = 0;

  while (nOutput < fileSize) {
    if (isStop.value) {
      fetchAddChunk('N/A', index, 2, fileNo, otherFileNo).then(() => {
        isStop.value = false;
        progress.value = 0;
        return;
      });
    }
    let n = fileSize - nOutput;
    if (n >= nChunk * 190) {
      n = nChunk * 190;
    } else {
      nChunk = Math.floor(n / 190);
      if (n % 190 > 0) nChunk++;
    }

    const chunks = await file.slice(nOutput, nOutput + n).arrayBuffer();
    const resultBytes = await $_encryptChunks(nChunk, chunks);

    nOutput += n;
    index += 1;

    progressCounter.now += n;

    const toBase64Data = window.btoa(
      resultBytes.reduce((data, byte) => data + String.fromCharCode(byte), ''),
    );

    await fetchAddChunk(toBase64Data, index, 0, fileNo, otherFileNo);

    progress.value = getCurrentProgress();
  }
  // end
  const res = await fetchAddChunk('N/A', index, 1, fileNo, otherFileNo);

  if (res?.Result) {
    return updateBlockchainHash(otherFileNo);
  }

  return { Result: false };
};

const fetchAddChunk = (chunk, index, merge, FileNo, otherFileNo = []) => {
  //merge 0:上傳中 1:上傳結束 2:刪除
  const formData = new FormData();
  formData.append('Files', chunk);
  formData.append('FileNo', FileNo);
  formData.append('merge', merge);
  formData.append('sort', index);
  formData.append('chunkCount', index);

  if (otherFileNo.length === 0) {
    formData.append('OtherFileNo[]', '');
  }
  otherFileNo.forEach((item) => {
    formData.append('OtherFileNo[]', item);
  });

  return store.dispatch('sentFile/postFileChunk', formData);
};

const updateBlockchainHash = (otherFileNo = []) => {
  const formData = new FormData();
  if (otherFileNo.length === 0) {
    formData.append('OtherFileNo[]', '');
  }
  otherFileNo.forEach((item) => {
    formData.append('OtherFileNo[]', item);
  });
  return store.dispatch('sentFile/updateBlockchainHash', formData);
};

const fetchAddFile = (receiveNo, otherReceiveNo = [], file, fileSize) => {
  const formData = new FormData();
  formData.append('MemberReceiveNo', receiveNo);
  formData.append('FileName', `${file.name}.enc`);
  formData.append('EncodeNo', nspoEncodeMemberNo.value);
  formData.append('FileSize', fileSize);

  if (otherReceiveNo.length === 0) {
    formData.append('OtherMemberReceiveList[]', '');
  }
  otherReceiveNo.forEach((item) => {
    formData.append('OtherMemberReceiveList[]', item);
  });

  return store
    .dispatch('sentFile/postFileUpload', formData)
    .then((resp) => {
      if (resp.Result) {
        return {
          fileNo: resp.fileNo,
          otherFileNo: resp?.otherFileNo ?? [],
        };
      }
      $_errorNotify('Fail To Operation');
    })
    .catch((err) => {
      $_errorNotify(err.message);
    });
};

const getPublickeyByMemberNo = async (memberNo) => {
  const formData = new FormData();
  formData.append('MemberNo', memberNo);
  const {
    Data: [memberKey],
  } = await store.dispatch('sentFile/getMemberKey', formData);
  return memberKey.PublicKey;
};

const getNspoEncodePublicKey = async () => {
  const formData = new FormData();
  formData.append('Page', 0);
  formData.append('Count', 99999);

  let { List } = await store.dispatch(
    'systemPublicKey/getSystemKeyInfoList',
    formData,
  );
  List = List.filter((item) => item.DisableTime === '');
  const randomIndex = Math.floor(Math.random() * List.length);
  const member = List[randomIndex];
  const publickeyHash = await $_sha512(member.PublicKey);

  if (member.Hash !== publickeyHash) {
    publicKeyVerification.value = '失敗';
    throw new Error('公鑰格式錯誤');
  }
  publicKeyVerification.value = '成功';
  nspoEncodePublicKey.value = member.PublicKey;
  nspoEncodeMemberNo.value = member.MemberNo;
  return member.PublicKey;
};

const onReceiverSelect = async (newVal, receiver) => {
  if (newVal.length > receiver.user.values.length) {
    const memberNo = newVal[newVal.length - 1].value;
    const publickey = await getPublickeyByMemberNo(memberNo);
    if (publickey === null) {
      return $_errorNotify('請接收人先建立私鑰。');
    }
  }
  receiver.user.values = newVal;
};

const getDirectoryName = (files) => {
  if (files.length === 0) return '';
  const file = files[0];
  const path = file.webkitRelativePath;
  const pathSplit = path.split('/');
  return pathSplit[0];
};

const handleUpload = async () => {
  if (selfPrivateKey.value === '未設定') {
    return $_errorNotify('請先建立私鑰');
  }
  isStop.value = false;
  dialogShow.value = true;
  publicKeyVerification.value = '-';

  progressCounter.all = 0;
  progressCounter.now = 0;

  try {
    const publickey = await getNspoEncodePublicKey();
    await $_handleFile(null, 0, publickey);
  } catch (error) {
    isStop.value = false;
    progress.value = 0;
    return $_errorNotify('公鑰格式錯誤');
  }
  let fileSize = 0;

  fileSendEventList.forEach((event) => {
    const files = event.file.flat();
    fileSize += files.reduce((prev, file) => prev + file.size, 0);
  });

  progressCounter.all = fileSize;

  for (let i = 0; i < fileSendEventList.length; i++) {
    const event = fileSendEventList[i];
    const receiveNoList = event.receiverList
      .map((item) => item.user.values.map((valueItem) => valueItem.value))
      .flat();

    let files = event.file.flat();
    if (event.fileType === 'directory') {
      const directoryName = getDirectoryName(files);
      const compressFile = await toZip(files, `${directoryName}.zip`);
      files = [compressFile];
    }

    for (let j = 0; j < files.length; j++) {
      const file = files[j];
      const receiveNo = receiveNoList[0];
      const otherReceiveNo = receiveNoList.slice(1) ?? [];
      const { fileNo, otherFileNo } = await fetchAddFile(
        receiveNo,
        otherReceiveNo,
        file,
        fileSize,
      );

      try {
        const res = await handleChunks(fileNo, file, otherFileNo);
        if (!res?.Result) {
          $_errorNotify('上傳失敗，請重新上傳');
        }
      } catch (error) {
        $_errorNotify('上傳失敗，請重新上傳');
      }
    }
  }
  setTimeout(() => {
    dialogShow.value = false;
    isStop.value = false;
    progress.value = 0;
    $_successNotify('上傳完成');
    emit('on-upload');
    fileSendEventList.splice(0, fileSendEventList.length);
  }, 1000);
};
</script>

<style lang="scss" scoped>
.th-receiver {
  width: 64%;
}
.th-filename {
  width: 35%;
}
.th-event-num {
  width: 1%;
}
.height-100 {
  height: 100%;
}
.receiver {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 140px;
  padding-bottom: 12px;
}
.event-num {
  line-height: 40px;
}
</style>
