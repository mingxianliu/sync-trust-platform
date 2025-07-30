<template>
  <q-tree
    class="tree-view"
    :nodes="props.nodes"
    :node-key="props.nodeKey"
    selected-color="main-color"
  >
    <template #header-group="scope">
      <div
        class="row items-center justify-between cursor-pointer group-item"
        :class="{ 'active-group': selectRow === scope.key }"
        @click="handleNodeClick(scope)"
      >
        <span>
          {{ scope.node.label }}
        </span>
        <div
          v-if="selectRow === scope.key"
          class="edit-btn"
          @click="handleEdit(scope.node)"
        >
          <q-icon name="edit" />
        </div>
      </div>
    </template>
    <template #header-root="scope">
      <div
        class="row items-center justify-between cursor-pointer group-item"
        @click.stop="handleRootClick(scope)"
      >
        <span>
          {{ scope.node.label }}
        </span>
      </div>
    </template>
  </q-tree>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  nodes: {
    type: Array,
    default: () => [],
  },
  nodeKey: {
    type: String,
    default: 'label',
  },
});

const emit = defineEmits([
  'onNodeClick',
  'onEditButtonClick',
  'onRootNodeClick',
]);

const selectRow = ref(null);

const handleNodeClick = (scope) => {
  selectRow.value = scope.key;
  emit('onNodeClick', scope.node);
};

const handleRootClick = (scope) => {
  scope.expanded = true;
  emit('onRootNodeClick', scope.node);
};

const handleEdit = (node) => emit('onEditButtonClick', node);
</script>

<style scoped lang="scss">
.tree-view {
  & :deep(.q-tree__node-header-content) {
    height: 40px;
  }
  & :deep(.q-tree__node-header:before) {
    border-bottom: none;
    height: 100%;
  }
  & :deep(.q-tree__node-header:before),
  & :deep(.q-tree__node:after) {
    border-left: 3px solid #f0f0f0;
  }
}

.group-item {
  width: 100%;
  height: 40px;
  position: relative;
  padding-right: 20px;
}

.group-item::after {
  background-color: transparent;
  transition: background-color 500ms linear;
}

.active-group.group-item::after {
  content: '';
  position: absolute;
  background-color: rgba(59, 69, 150, 0.09);
  left: -20px;
  width: calc(100% + 20px);
  height: 100%;
}

.active-group {
  color: #3a4595;
}

.edit-btn {
  z-index: 2;
}
</style>
