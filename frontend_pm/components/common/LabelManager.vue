<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      <v-icon class="mr-2">mdi-label</v-icon>
      {{ cardId ? 'Card Labels' : 'Manage Labels' }}
      <v-spacer />
      <v-btn
        icon="mdi-close"
        variant="text"
        size="small"
        @click="$emit('close')"
      />
    </v-card-title>

    <v-card-text class="pa-4">
      <!-- Search for card context -->
      <v-text-field
        v-if="cardId"
        v-model="search"
        placeholder="Search labels..."
        prepend-inner-icon="mdi-magnify"
        variant="outlined"
        density="compact"
        hide-details
        class="mb-4"
      />

      <!-- Labels List -->
      <div v-if="labelStore.loading" class="text-center py-4">
        <v-progress-circular indeterminate color="primary" size="24" />
        <p class="text-caption text-grey mt-2">Loading labels...</p>
      </div>

      <div v-else-if="labelStore.error" class="text-center py-4">
        <v-alert type="error" variant="tonal" density="compact">
          {{ labelStore.error }}
        </v-alert>
        <v-btn color="primary" variant="text" @click="loadLabels" class="mt-2">
          Retry
        </v-btn>
      </div>

      <v-list v-else class="labels-list">
        <v-list-item
          v-for="label in filteredLabels"
          :key="label.id"
          class="label-item"
          :class="{ 'label-item--selected': isLabelSelected(label.id) }"
          @click="cardId ? handleToggleLabel(label) : null"
        >
          <template #prepend>
            <div
              class="label-preview"
              :style="{ 
                backgroundColor: label.color,
                border: `2px solid ${label.color}`
              }"
              :title="label.name || 'Unnamed label'"
            />
          </template>

          <v-list-item-title class="text-body-2 font-weight-medium">
            {{ label.name || 'Unnamed label' }}
          </v-list-item-title>
          
          <v-list-item-subtitle v-if="label.cards_count !== undefined" class="text-caption">
            {{ label.cards_count }} cards
          </v-list-item-subtitle>

          <template #append>
            <div class="d-flex align-center gap-1">
              <v-icon 
                v-if="cardId && isLabelSelected(label.id)" 
                color="success"
                size="small"
              >
                mdi-check-circle
              </v-icon>
              
              <v-menu v-if="!cardId" location="bottom">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    icon="mdi-dots-vertical"
                    size="small"
                    variant="text"
                    class="label-menu-btn"
                  />
                </template>
                <v-list density="compact">
                  <v-list-item @click="startEdit(label)">
                    <template #prepend>
                      <v-icon size="small">mdi-pencil</v-icon>
                    </template>
                    <v-list-item-title>Edit</v-list-item-title>
                  </v-list-item>
                  <v-list-item @click="handleDelete(label.id)">
                    <template #prepend>
                      <v-icon size="small" color="error">mdi-delete</v-icon>
                    </template>
                    <v-list-item-title class="text-error">Delete</v-list-item-title>
                  </v-list-item>
                </v-list>
              </v-menu>
            </div>
          </template>
        </v-list-item>

        <div v-if="!filteredLabels.length && search" class="text-center py-4">
          <v-icon size="48" color="grey-lighten-2" class="mb-2">mdi-label-off</v-icon>
          <p class="text-body-2 text-grey">No labels found</p>
          <p class="text-caption text-grey">Try a different search term</p>
        </div>

        <div v-else-if="!filteredLabels.length" class="text-center py-4">
          <v-icon size="48" color="grey-lighten-2" class="mb-2">mdi-label-outline</v-icon>
          <p class="text-body-2 text-grey">No labels created yet</p>
          <p class="text-caption text-grey">Create your first label to get started</p>
        </div>
      </v-list>

      <!-- Create/Edit Label Form -->
      <v-divider class="my-4" />

      <div v-if="creating || editing" class="label-form">
        <h4 class="text-subtitle-1 mb-3">
          {{ editing ? 'Edit Label' : 'Create New Label' }}
        </h4>

        <v-text-field
          v-model="labelForm.name"
          label="Label Name"
          variant="outlined"
          density="compact"
          class="mb-3"
          placeholder="Enter label name..."
          :rules="[nameRules.required]"
        />

        <div class="mb-3">
          <p class="text-caption font-weight-medium mb-2">Color</p>
          <div class="label-colors">
            <div
              v-for="color in LABEL_COLORS"
              :key="color.value"
              class="color-option"
              :class="{ 
                selected: labelForm.color === color.value,
                'color-option--named': color.name
              }"
              :style="{ backgroundColor: color.value }"
              @click="labelForm.color = color.value"
              :title="color.name"
            />
          </div>
        </div>

        <div class="d-flex gap-2 mt-4">
          <v-btn
            color="primary"
            size="small"
            :loading="labelStore.loading"
            :disabled="!labelForm.name.trim()"
            @click="editing ? handleSaveEdit() : handleCreate()"
          >
            {{ editing ? 'Save Changes' : 'Create Label' }}
          </v-btn>
          <v-btn 
            size="small" 
            variant="text"
            @click="cancelForm"
            :disabled="labelStore.loading"
          >
            Cancel
          </v-btn>
        </div>
      </div>

      <!-- Create Button -->
      <v-btn
        v-else-if="!cardId"
        prepend-icon="mdi-plus"
        variant="outlined"
        block
        @click="creating = true"
      >
        Create New Label
      </v-btn>
    </v-card-text>

    <v-card-actions class="pa-4">
      <v-spacer />
      <v-btn 
        variant="text" 
        @click="$emit('close')"
        :disabled="labelStore.loading"
      >
        Close
      </v-btn>
      <v-btn 
        v-if="cardId && selectedLabels.length > 0"
        color="primary" 
        @click="handleSaveLabels"
        :loading="savingLabels"
      >
        Apply {{ selectedLabels.length }} Label(s)
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
import type { Label } from '~/types/models'

interface Props {
  boardId: number
  cardId?: number
  currentCardLabels?: Label[]
}

interface Emits {
  (event: 'close'): void
  (event: 'refresh'): void
  (event: 'labels-updated', labels: Label[]): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const labelStore = useLabelStore()
const cardStore = useCardStore()
const uiStore = useUiStore()

const LABEL_COLORS = [
  { name: 'Green', value: '#61bd4f' },
  { name: 'Yellow', value: '#f2d600' },
  { name: 'Orange', value: '#ff9f1a' },
  { name: 'Red', value: '#eb5a46' },
  { name: 'Purple', value: '#c377e0' },
  { name: 'Blue', value: '#0079bf' },
  { name: 'Sky', value: '#00c2e0' },
  { name: 'Lime', value: '#51e898' },
  { name: 'Pink', value: '#ff78cb' },
  { name: 'Black', value: '#344563' },
]

const search = ref('')
const creating = ref(false)
const editing = ref(false)
const editingId = ref<number | null>(null)
const savingLabels = ref(false)
const selectedLabels = ref<Label[]>([])

const labelForm = reactive({
  name: '',
  color: LABEL_COLORS[0]?.value || ''
})

const nameRules = {
  required: (value: string) => !!value?.trim() || 'Label name is required'
}

const filteredLabels = computed(() => {
  if (!search.value) return labelStore.labels
  
  const searchTerm = search.value.toLowerCase()
  return labelStore.labels.filter(label =>
    (label.name?.toLowerCase().includes(searchTerm) ?? false)
  )
})

const isLabelSelected = (labelId: number): boolean => {
  if (props.cardId) {
    return selectedLabels.value.some(l => l.id === labelId)
  }
  return false
}

const loadLabels = async () => {
  await labelStore.getBoardLabels(props.boardId)
}

const handleToggleLabel = (label: Label) => {
  if (isLabelSelected(label.id)) {
    selectedLabels.value = selectedLabels.value.filter(l => l.id !== label.id)
  } else {
    selectedLabels.value.push(label)
  }
}

const handleCreate = async () => {
  if (!labelForm.name.trim()) return

  try {
    await labelStore.createLabel({
      board_id: props.boardId,
      name: labelForm.name.trim(),
      color: labelForm.color
    })
    cancelForm()
    uiStore.showSnackbar('Label created successfully', 'success')
  } catch (error) {
  }
}

const startEdit = (label: Label) => {
  editing.value = true
  editingId.value = label.id
  labelForm.name = label.name || ''
  labelForm.color = label.color
}

const handleSaveEdit = async () => {
  if (!editingId.value || !labelForm.name.trim()) return

  try {
    await labelStore.updateLabel(editingId.value, {
      name: labelForm.name.trim(),
      color: labelForm.color
    })
    cancelForm()
    uiStore.showSnackbar('Label updated successfully', 'success')
  } catch (error) {
  }
}

const handleDelete = async (labelId: number) => {
  if (!confirm('Are you sure you want to delete this label? This action cannot be undone.')) return

  try {
    await labelStore.deleteLabel(labelId)
    uiStore.showSnackbar('Label deleted', 'success')
  } catch (error) {
    uiStore.showSnackbar('Failed to delete label', 'error')
  }
}

const handleSaveLabels = async () => {
  if (!props.cardId || selectedLabels.value.length === 0) return

  savingLabels.value = true
  try {
    const currentLabelIds = props.currentCardLabels?.map(l => l.id) || []
    for (const labelId of currentLabelIds) {
      await cardStore.removeLabel(props.cardId, labelId)
    }

    for (const label of selectedLabels.value) {
      await cardStore.addLabel(props.cardId, { label_id: label.id } )
    }

    emit('labels-updated', selectedLabels.value)
    emit('refresh')
    uiStore.showSnackbar('Labels updated successfully', 'success')
    emit('close')
  } catch (error) {
    uiStore.showSnackbar('Failed to update labels', 'error')
  } finally {
    savingLabels.value = false
  }
}

const cancelForm = () => {
  creating.value = false
  editing.value = false
  editingId.value = null
  labelForm.name = ''
  labelForm.color = LABEL_COLORS[0]?.value || ''
}

watch(() => props.currentCardLabels, (newLabels) => {
  if (props.cardId && newLabels) {
    selectedLabels.value = [...newLabels]
  }
}, { immediate: true })

onMounted(async () => {
  await loadLabels()
})
</script>

<style scoped>
.labels-list {
  max-height: 300px;
  overflow-y: auto;
}

.label-item {
  border-radius: 8px;
  margin-bottom: 4px;
  transition: all 0.2s ease;
  cursor: pointer;
}

.label-item:hover {
  background-color: rgba(0, 0, 0, 0.04);
}

.label-item--selected {
  background-color: rgba(25, 118, 210, 0.08);
  border-left: 3px solid rgb(25, 118, 210);
}

.label-preview {
  width: 40px;
  height: 24px;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.label-item:hover .label-preview {
  transform: scale(1.1);
}

.label-colors {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 8px;
}

.color-option {
  height: 40px;
  border-radius: 4px;
  cursor: pointer;
  border: 3px solid transparent;
  transition: all 0.2s ease;
  position: relative;
}

.color-option:hover {
  opacity: 0.8;
  transform: scale(1.05);
}

.color-option.selected {
  border-color: #000;
  box-shadow: 0 0 0 2px white, 0 0 0 4px #000;
}

.color-option--named::after {
  content: '';
  position: absolute;
  top: -2px;
  right: -2px;
  width: 8px;
  height: 8px;
  background: rgba(0, 0, 0, 0.3);
  border-radius: 50%;
}

.label-menu-btn {
  opacity: 0;
  transition: opacity 0.2s ease;
}

.label-item:hover .label-menu-btn {
  opacity: 1;
}

.label-form {
  background: rgba(0, 0, 0, 0.02);
  padding: 16px;
  border-radius: 8px;
  border: 1px solid rgba(0, 0, 0, 0.08);
}

.gap-1 {
  gap: 4px;
}

.gap-2 {
  gap: 8px;
}

.labels-list::-webkit-scrollbar {
  width: 6px;
}

.labels-list::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.04);
  border-radius: 3px;
}

.labels-list::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 3px;
}

.labels-list::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.3);
}

@media (max-width: 768px) {
  .labels-list {
    max-height: 250px;
  }
  
  .label-colors {
    grid-template-columns: repeat(4, 1fr);
  }
  
  .label-menu-btn {
    opacity: 1;
  }
}
</style>