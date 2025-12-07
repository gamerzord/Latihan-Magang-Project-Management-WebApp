<template>
  <div class="card-labels">
    <div class="d-flex flex-wrap gap-1">
      <v-chip
        v-for="label in card.labels || []"
        :key="label.id"
        :color="label.color"
        size="small"
        variant="flat"
        closable
        @click:close="handleRemove(label.id)"
        class="label-chip"
      >
        <span class="label-text" :style="{ color: getTextColor(label.color) }">
          {{ label.name || 'No name' }}
        </span>
      </v-chip>
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

const handleRemove = async (labelId: number) => {
  try {
    await cardStore.removeLabel(props.card.id, labelId)
    emit('refresh')
  } catch (error) {
    uiStore.showSnackbar('Failed to remove label from card.', 'error')
  }
}

const getTextColor = (backgroundColor: string): string => {
  const hex = backgroundColor.replace('#', '')
  const r = parseInt(hex.substr(0, 2), 16)
  const g = parseInt(hex.substr(2, 2), 16)
  const b = parseInt(hex.substr(4, 2), 16)
  
  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255
  
  return luminance > 0.5 ? '#000000' : '#FFFFFF'
}
</script>

<style scoped>
.card-labels {
  min-height: 24px;
}

.gap-1 {
  gap: 4px;
}

.label-chip {
  font-weight: 500;
  min-width: 40px;
  transition: all 0.2s ease;
}

.label-chip:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.label-text {
  font-size: 0.75rem;
  font-weight: 600;
  text-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
}

@media (max-width: 768px) {
  .label-chip {
    font-size: 0.7rem;
    min-width: 36px;
  }
  
  .label-text {
    font-size: 0.7rem;
  }
}
</style>