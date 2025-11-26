<template>
  <div class="card-description mb-4">
    <div class="d-flex align-center justify-space-between mb-2">
      <div class="d-flex align-center">
        <v-icon class="mr-2">mdi-text-box-outline</v-icon>
        <h3 class="text-subtitle-1 font-weight-medium">Description</h3>
      </div>
      <v-btn
        v-if="!editing && card.description"
        size="small"
        variant="outlined"
        @click="startEditing"
      >
        Edit
      </v-btn>
      <v-btn
        v-else-if="!card.description"
        size="small"
        variant="outlined"
        @click="startEditing"
      >
        Add
      </v-btn>
    </div>

    <div v-if="editing">
      <v-textarea
        v-model="description"
        placeholder="Add a more detailed description..."
        variant="outlined"
        rows="3"
        auto-grow
        hide-details
        ref="textareaRef"
        @keydown.ctrl.enter="handleSave"
        @keydown.meta.enter="handleSave"
      />
      <div class="d-flex gap-2 mt-2">
        <v-btn 
          color="primary" 
          size="small" 
          @click="handleSave"
          :loading="cardStore.loading"
        >
          Save
        </v-btn>
        <v-btn 
          size="small" 
          variant="text" 
          @click="cancel"
          :disabled="cardStore.loading"
        >
          Cancel
        </v-btn>
      </div>
    </div>

    <div
      v-else-if="card.description"
      class="description-text cursor-pointer"
      @click="startEditing"
      v-html="formattedDescription"
    />
    
    <div
      v-else
      class="no-description cursor-pointer text-grey"
      @click="startEditing"
    >
      Add a more detailed description...
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Card } from '~/types/models'

interface Props {
  card: Card
}

interface Emits {
  (event: 'refresh'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const cardStore = useCardStore()
const uiStore = useUiStore()

const editing = ref(false)
const description = ref(props.card.description || '')
const textareaRef = ref()

const formattedDescription = computed(() => {
  if (!props.card.description) return ''
  return props.card.description.replace(/\n/g, '<br>')
})

const startEditing = () => {
  editing.value = true
  description.value = props.card.description || ''
  
  nextTick(() => {
    textareaRef.value?.focus()
  })
}

const handleSave = async () => {
  if (!description.value.trim() && props.card.description) {
    if (!confirm('Remove description?')) return
  }

  try {
    await cardStore.updateCard(props.card.id, { 
      description: description.value.trim() ?? null
    })
    editing.value = false
    emit('refresh')
  } catch (error) {
  }
}

const cancel = () => {
  editing.value = false
  description.value = props.card.description || ''
}

watch(() => props.card.description, (newDesc) => {
  description.value = newDesc || ''
  editing.value = false
})

watch(editing, (newValue) => {
  if (newValue) {
    nextTick(() => {
      textareaRef.value?.focus()
    })
  }
})
</script>

<style scoped>
.card-description {
  min-height: 60px;
}

.description-text {
  padding: 12px;
  background: #f8f9fa;
  border-radius: 8px;
  white-space: pre-wrap;
  line-height: 1.5;
  border: 1px solid transparent;
  transition: all 0.2s ease;
}

.description-text:hover {
  background: #f1f2f4;
  border-color: #e1e4e8;
}

.no-description {
  padding: 12px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px dashed #dfe1e6;
  transition: all 0.2s ease;
}

.no-description:hover {
  background: #f1f2f4;
  border-color: #c1c7d0;
}

.cursor-pointer {
  cursor: pointer;
}

.gap-2 {
  gap: 8px;
}

@media (max-width: 768px) {
  .description-text,
  .no-description {
    padding: 8px;
  }
}
</style>