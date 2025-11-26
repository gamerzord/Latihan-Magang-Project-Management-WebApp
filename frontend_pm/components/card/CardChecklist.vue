<template>
  <div class="checklist mb-4">
    <div class="d-flex align-center justify-space-between mb-2">
      <div class="d-flex align-center">
        <v-icon class="mr-2">mdi-checkbox-marked-outline</v-icon>
        <h3 class="text-subtitle-1 font-weight-medium">{{ checklist.title }}</h3>
      </div>

      <v-menu>
        <template #activator="{ props }">
          <v-btn 
            v-bind="props" 
            icon="mdi-dots-horizontal" 
            size="small" 
            variant="text" 
          />
        </template>
        <v-list density="compact">
          <v-list-item @click="handleDelete">
            <template #prepend>
              <v-icon color="error">mdi-delete</v-icon>
            </template>
            <v-list-item-title class="text-error">Delete Checklist</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>
    </div>

    <!-- Progress -->
    <div v-if="progress.total > 0" class="d-flex align-center gap-2 mb-3">
      <span class="text-caption text-grey">{{ progress.completed }}/{{ progress.total }}</span>
      <v-progress-linear
        :model-value="progress.percentage"
        color="success"
        height="8"
        rounded
        class="flex-grow-1"
      />
      <span class="text-caption text-grey">{{ progress.percentage }}%</span>
    </div>

    <!-- Items -->
    <v-list density="compact" class="checklist-items">
      <v-list-item
        v-for="item in sortedItems"
        :key="item.id"
        class="px-0 checklist-item"
        :class="{ 'completed': item.completed }"
      >
        <template #prepend>
          <v-checkbox
            :model-value="item.completed"
            hide-details
            density="compact"
            @update:model-value="toggleItem(item.id, $event ?? false)"
          />
        </template>

        <v-list-item-title
          class="checklist-item-text"
          :class="{ 
            'text-decoration-line-through text-grey': item.completed 
          }"
        >
          {{ item.text }}
        </v-list-item-title>

        <template #append>
          <v-btn
            icon="mdi-delete-outline"
            size="small"
            variant="text"
            color="grey"
            @click="deleteItem(item.id)"
          />
        </template>
      </v-list-item>
    </v-list>

    <!-- Add Item -->
    <v-btn
      v-if="!addingItem"
      variant="text"
      size="small"
      prepend-icon="mdi-plus"
      @click="addingItem = true"
      class="add-item-btn"
    >
      Add Item
    </v-btn>

    <div v-else class="mt-2 add-item-form">
      <v-text-field
        v-model="newItemText"
        placeholder="Add an item..."
        variant="outlined"
        density="compact"
        hide-details
        autofocus
        ref="itemInputRef"
        @keyup.enter="handleAddItem"
        @keyup.esc="cancelAddItem"
      />
      <div class="d-flex gap-2 mt-2">
        <v-btn 
          color="primary" 
          size="small" 
          @click="handleAddItem"
          :disabled="!newItemText.trim()"
          :loading="cardStore.loading"
        >
          Add
        </v-btn>
        <v-btn 
          size="small" 
          variant="text" 
          @click="cancelAddItem"
          :disabled="cardStore.loading"
        >
          Cancel
        </v-btn>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Checklist, ChecklistItem } from '~/types/models'

interface Props {
  checklist: Checklist
}

interface Emits {
  (event: 'refresh'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const cardStore = useCardStore()
const uiStore = useUiStore()

const addingItem = ref(false)
const newItemText = ref('')
const itemInputRef = ref()

const sortedItems = computed(() => {
  if (!props.checklist.items?.length) return []
  return [...props.checklist.items].sort((a: ChecklistItem, b: ChecklistItem) => a.position - b.position)
})

const progress = computed(() => {
  const items = props.checklist.items || []
  const total = items.length
  const completed = items.filter(item => item.completed).length
  const percentage = total > 0 ? Math.round((completed / total) * 100) : 0
  
  return { total, completed, percentage }
})

const toggleItem = async (itemId: number, completed: boolean) => {
  try {
    await cardStore.updateChecklistItem(itemId, { completed })
    emit('refresh')
  } catch (error) {
  }
}

const deleteItem = async (itemId: number) => {
  try {
    await cardStore.deleteChecklistItem(itemId)
    emit('refresh')
  } catch (error) {
  }
}

const handleAddItem = async () => {
  if (!newItemText.value.trim()) return

  try {
    await cardStore.createChecklistItem({
      checklist_id: props.checklist.id,
      text: newItemText.value.trim()
    })
    newItemText.value = ''
    addingItem.value = false
    emit('refresh')
  } catch (error) {
  }
}

const cancelAddItem = () => {
  addingItem.value = false
  newItemText.value = ''
}

const handleDelete = async () => {
  if (!confirm('Are you sure you want to delete this checklist and all its items?')) return

  try {
    await cardStore.deleteChecklist(props.checklist.id)
    emit('refresh')
  } catch (error) {
  }
}

watch(addingItem, (newValue) => {
  if (newValue) {
    nextTick(() => {
      itemInputRef.value?.focus()
    })
  }
})
</script>

<style scoped>
.checklist {
  border: 1px solid #e1e4e8;
  border-radius: 8px;
  padding: 16px;
  background: white;
}

.checklist-items {
  background: transparent;
}

.checklist-item {
  border-radius: 4px;
  transition: background-color 0.2s ease;
}

.checklist-item:hover {
  background-color: #f8f9fa;
}

.checklist-item.completed {
  opacity: 0.7;
}

.checklist-item-text {
  font-size: 0.875rem;
  line-height: 1.4;
}

.add-item-btn {
  margin-top: 8px;
}

.add-item-form {
  border-top: 1px solid #e1e4e8;
  padding-top: 12px;
}

.gap-2 {
  gap: 8px;
}

@media (max-width: 768px) {
  .checklist {
    padding: 12px;
  }
  
  .checklist-item-text {
    font-size: 0.8rem;
  }
}
</style>