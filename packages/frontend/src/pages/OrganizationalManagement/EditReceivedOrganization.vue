<template>
  <div>
    <div>
      <q-btn
        icon="eva-arrow-back-outline"
        size="md"
        color="blue7"
        flat
        round
        @click="handleBack"
      />
      {{ orgName }}
    </div>
    <q-card>
      <q-card-section
        class="flex justify-between q-pt-md bg-indigo-1 box-border"
      >
        <p class="q-mt-sm q-mb-none text-blue6 text-bold">編輯接收方</p>

        <div>
          <q-btn
            color="grey-4"
            text-color="blue6"
            label="儲存"
            class="btn-height q-ml-md"
            unelevated
            icon="add"
            @click="handleSave"
          />
        </div>
      </q-card-section>
      <q-card-section class="row q-pt-lg">
        <div class="col-5">
          <q-list bordered padding>
            <q-item-label header top>
              組織列表
              <q-checkbox
                :model-value="noReceiveOrgNoCheckbox"
                @update:model-value="noReceiveOrgNoCheckboxChange"
              />
            </q-item-label>
            <q-item
              v-for="org in noReceiveOrgNo"
              :key="org.OrganizationNo"
              v-ripple
              tag="label"
            >
              <q-item-section>
                <q-item-label>{{ org.OrganizationName }}</q-item-label>
              </q-item-section>
              <q-item-section side top>
                <q-checkbox v-model="org.checked" />
              </q-item-section>
            </q-item>
          </q-list>
        </div>
        <div class="col-2">
          <div class="btn-box">
            <q-btn
              icon="keyboard_double_arrow_right"
              color="grey-4"
              text-color="blue6"
              class="btn-height"
              unelevated
              label="加入"
              @click="addReceive"
            />
            <q-btn
              icon="keyboard_double_arrow_left"
              color="grey-4"
              text-color="blue6"
              class="btn-height q-mt-md"
              unelevated
              label="移出"
              @click="cancelReceive"
            />
          </div>
        </div>
        <div class="col-5">
          <q-list bordered padding>
            <q-item-label header>可發送組織</q-item-label>

            <q-item
              v-for="receiver in receivedOrg"
              :key="receiver.OrganizationNo"
              v-ripple
              tag="label"
            >
              <q-item-section>
                <q-item-label>{{ receiver.OrganizationName }}</q-item-label>
              </q-item-section>
              <q-item-section
                v-if="
                  receiver.OrganizationNo !== 'TASA' &&
                  receiver.OrganizationNo !== orgNo
                "
                side
                top
              >
                <q-checkbox v-model="receiver.checked" />
              </q-item-section>
            </q-item>
          </q-list>
        </div>
      </q-card-section>
    </q-card>
  </div>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router';
import { ref, computed, onMounted } from 'vue';
import { useStore } from 'vuex';
import { $_successNotify } from 'src/mixin/common';

const route = useRoute();
const router = useRouter();
const store = useStore();

const orgNo = computed(() => route.params.OrganizationNo);
const orgName = computed(() => {
  return (
    allOrg.value.find((x) => x.OrganizationNo === orgNo.value)
      ?.OrganizationName ?? ''
  );
});

const noReceiveOrgNoCheckbox = computed(() => {
  if (noReceiveOrgNo.value.length === 0) return false;
  if (noReceiveOrgNo.value.every((x) => x.checked)) return true;
  if (noReceiveOrgNo.value.every((x) => !x.checked)) return false;
  return null;
});

const allOrg = ref([]);
const receivedOrg = ref([]);
const noReceiveOrgNo = ref([]);

const handleBack = () => {
  router.go(-1);
};

const cancelReceive = () => {
  const selectedOrg = receivedOrg.value
    .filter((x) => x.checked)
    .map((x) => ({
      ...x,
      checked: false,
    }));
  noReceiveOrgNo.value = [...selectedOrg, ...noReceiveOrgNo.value];
  receivedOrg.value = receivedOrg.value.filter((x) => !x.checked);
};

const addReceive = () => {
  const selectedOrg = noReceiveOrgNo.value
    .filter((x) => x.checked)
    .map((x) => ({
      ...x,
      checked: false,
    }));
  receivedOrg.value = [...selectedOrg, ...receivedOrg.value];
  noReceiveOrgNo.value = noReceiveOrgNo.value.filter((x) => !x.checked);
  pinTopTasaAndSelf();
};

const noReceiveOrgNoCheckboxChange = (val) => {
  if (val) {
    noReceiveOrgNo.value = noReceiveOrgNo.value.map((x) => ({
      ...x,
      checked: true,
    }));
    return;
  }

  noReceiveOrgNo.value = noReceiveOrgNo.value.map((x) => ({
    ...x,
    checked: false,
  }));
};

const fetchAllOrganizationList = async () => {
  const formData = new FormData();
  formData.append('Type', 'all');
  formData.append('Count', '99999');
  formData.append('Desc', 1);

  const res = await store.dispatch(
    'organizational/getOrganizationList',
    formData,
  );

  allOrg.value = res.Message.filter(
    (x) => x.OrganizationNo !== 'NSPOEncode',
  ).map((x) => ({
    OrganizationNo: x.OrganizationNo,
    OrganizationName: x.OrganizationName,
    checked: false,
  }));
};

const fetchOrganizationMappingList = async () => {
  const formData = new FormData();
  formData.append('OrgNo', orgNo.value);
  formData.append('Type', 'sender');

  const res = await store.dispatch(
    'organizational/getOrganizationMappingList',
    formData,
  );
  receivedOrg.value = res.Message.map((x) => ({
    OrganizationNo: x.receiverOrgNo,
    OrganizationName: x.receiverOrgName,
    checked: false,
  }));
};

const moveTasaAndSelfToReceivedOrg = () => {
  const tasa = noReceiveOrgNo.value.find((x) => x.OrganizationNo === 'TASA');
  if (tasa) {
    receivedOrg.value.push(tasa);
    noReceiveOrgNo.value = noReceiveOrgNo.value.filter(
      (x) => x.OrganizationNo !== 'TASA',
    );
  }

  const self = noReceiveOrgNo.value.find(
    (x) => x.OrganizationNo === orgNo.value,
  );
  if (self) {
    receivedOrg.value.push(self);
    noReceiveOrgNo.value = noReceiveOrgNo.value.filter(
      (x) => x.OrganizationNo !== orgNo.value,
    );
  }
};

const pinTopTasaAndSelf = () => {
  const self = receivedOrg.value.find((x) => x.OrganizationNo === orgNo.value);
  if (self) {
    receivedOrg.value = receivedOrg.value.filter(
      (x) => x.OrganizationNo !== orgNo.value,
    );
    receivedOrg.value.unshift(self);
  }

  const tasa = receivedOrg.value.find((x) => x.OrganizationNo === 'TASA');
  if (tasa) {
    receivedOrg.value = receivedOrg.value.filter(
      (x) => x.OrganizationNo !== 'TASA',
    );
    receivedOrg.value.unshift(tasa);
  }
};

const fetchAll = async () => {
  await fetchAllOrganizationList();
  await fetchOrganizationMappingList();

  noReceiveOrgNo.value = allOrg.value.filter((x) => {
    return !receivedOrg.value.some(
      (y) => y.OrganizationNo === x.OrganizationNo,
    );
  });

  moveTasaAndSelfToReceivedOrg();
  pinTopTasaAndSelf();
};

const handleSave = async () => {
  const formData = new FormData();
  formData.append('Sender', orgNo.value);
  receivedOrg.value.forEach((x) => {
    formData.append('Receivers[]', x.OrganizationNo);
  });
  const res = await store.dispatch(
    'organizational/addOrganizationMapping',
    formData,
  );
  if (res.Result) {
    $_successNotify('儲存成功');
    router.go(-1);
    return;
  }
};

onMounted(() => {
  fetchAll();
});
</script>

<style lang="scss" scoped>
.btn-box {
  display: flex;
  flex-direction: column;
  align-items: center;

  & > button {
    width: 120px;
    max-width: 100%;
  }
}
</style>
