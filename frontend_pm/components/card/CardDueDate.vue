<template>
  <v-chip
    :color="dueDateStatus.color"
    size="small"
    variant="flat"
    @click="$emit('edit')"
    class="due-date-chip"
    :class="{ 'completed': card.due_date_completed }"
  >
    <v-icon start size="small">
      {{ card.due_date_completed ? 'mdi-check-circle' : dueDateStatus.icon }}
    </v-icon>
    {{ formatDate(card.due_date!, 'medium') }}
  </v-chip>
</template>

<script setup lang="ts">
import type { Card } from '~/types/models'

interface Props {
  card: Card
}

interface Emits {
  (event: 'refresh'): void
  (event: 'edit'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const dueDateStatus = computed(() => {
  if (!props.card.due_date) return { color: 'grey', icon: 'mdi-clock-outline' }
  
  const dueDate = new Date(props.card.due_date)
  const today = new Date()
  const timeDiff = dueDate.getTime() - today.getTime()
  const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24))

  if (props.card.due_date_completed) {
    return { color: 'success', icon: 'mdi-check-circle' }
  } else if (daysDiff < 0) {
    return { color: 'error', icon: 'mdi-alert-circle' }
  } else if (daysDiff === 0) {
    return { color: 'warning', icon: 'mdi-clock-alert' }
  } else if (daysDiff <= 2) {
    return { color: 'warning', icon: 'mdi-clock-fast' }
  } else {
    return { color: 'primary', icon: 'mdi-clock-outline' }
  }
})

const formatDate = (dateString: string, format: 'short' | 'medium' | 'long' = 'medium'): string => {
  const date = new Date(dateString)
  
  switch (format) {
    case 'short':
      return date.toLocaleDateString()
    case 'medium':
      return date.toLocaleDateString(undefined, { 
        weekday: 'short', 
        month: 'short', 
        day: 'numeric' 
      })
    case 'long':
      return date.toLocaleDateString(undefined, { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      })
    default:
      return date.toLocaleDateString()
  }
}
</script>

<style scoped>
.due-date-chip {
  cursor: pointer;
  transition: all 0.2s ease;
  font-weight: 500;
}

.due-date-chip:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.due-date-chip.completed {
  text-decoration: line-through;
  opacity: 0.8;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .due-date-chip {
    font-size: 0.75rem;
  }
}
</style>