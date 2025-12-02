<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      <v-icon class="mr-2">mdi-calendar</v-icon>
      Due Date
      <v-spacer />
      <v-btn
        icon="mdi-close"
        variant="text"
        size="small"
        @click="$emit('close')"
      />
    </v-card-title>

    <v-card-text class="pa-4">
      <!-- Date Selection -->
      <div class="date-selection mb-4">
        <v-menu location="bottom">
          <template #activator="{ props }">
            <v-text-field
              v-bind="props"
              v-model="displayDate"
              label="Select Date"
              variant="outlined"
              density="compact"
              prepend-inner-icon="mdi-calendar"
              readonly
              hide-details
              class="mb-2"
            />
          </template>
          <v-date-picker
            v-model="selectedDate"
            :min="minDate"
            color="primary"
          />
        </v-menu>

        <v-menu location="bottom">
          <template #activator="{ props }">
            <v-text-field
              v-bind="props"
              v-model="selectedTime"
              label="Select Time"
              variant="outlined"
              density="compact"
              prepend-inner-icon="mdi-clock-outline"
              readonly
              hide-details
            />
          </template>
          <v-time-picker
            v-model="selectedTime"
            format="24hr"
            color="primary"
          />
        </v-menu>
      </div>

      <!-- Quick Date Options -->
      <div class="quick-dates mb-4">
        <p class="text-caption font-weight-medium text-grey mb-2">Quick Select</p>
        <div class="d-flex flex-wrap gap-2">
          <v-chip
            v-for="option in quickDateOptions"
            :key="option.label"
            size="small"
            variant="outlined"
            :color="isQuickDateSelected(option.value) ? 'primary' : undefined"
            @click="setQuickDate(option.value)"
          >
            {{ option.label }}
          </v-chip>
        </div>
      </div>

      <!-- Completion Status -->
      <v-checkbox
        v-model="completed"
        label="Mark as completed"
        color="success"
        density="compact"
        hide-details
        class="mb-4"
      />

      <!-- Due Date Status -->
      <div v-if="dueDateStatus" class="due-date-status mb-4">
        <v-alert
          :type="dueDateStatus.color"
          variant="tonal"
          density="compact"
          class="text-caption"
        >
          <div class="d-flex align-center">
            <v-icon size="small" class="mr-2">{{ dueDateStatus.icon }}</v-icon>
            <span>{{ dueDateStatus.text }}</span>
          </div>
        </v-alert>
      </div>

      <!-- Remove Button -->
      <v-btn
        v-if="hasDueDate"
        color="error"
        variant="outlined"
        block
        prepend-icon="mdi-calendar-remove"
        @click="handleRemove"
        class="mb-2"
      >
        Remove Due Date
      </v-btn>
    </v-card-text>

    <v-card-actions class="pa-4">
      <v-spacer />
      <v-btn 
        variant="text" 
        @click="$emit('close')"
        :disabled="cardStore.loading"
      >
        Cancel
      </v-btn>
      <v-btn 
        color="primary" 
        @click="handleSave"
        :loading="cardStore.loading"
        :disabled="!isDateValid"
      >
        {{ hasDueDate ? 'Update' : 'Set Due Date' }}
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
import type { Card } from '~/types/models'

interface Props {
  card: Card | null
}

interface Emits {
  (event: 'close'): void
  (event: 'refresh'): void
  (event: 'date-updated', date: string | null): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const cardStore = useCardStore()
const uiStore = useUiStore()

const selectedDate = ref('')
const selectedTime = ref('12:00')
const completed = ref(false)

const hasDueDate = computed(() => {
  return !!props.card?.due_date
})

const isDateValid = computed(() => {
  return !!selectedDate.value && !!selectedTime.value
})

const displayDate = computed(() => {
  if (!selectedDate.value) return ''
  const date = new Date(selectedDate.value)
  return date.toLocaleDateString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
})

const fullDateTime = computed(() => {
  if (!selectedDate.value || !selectedTime.value) return null

  const date = new Date(selectedDate.value)
  if (date instanceof Date) {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}T${selectedTime.value}:00`
  }

  return `${selectedDate.value}T${selectedTime.value}:00`
})

const dueDateStatus = computed(() => {
  if (!fullDateTime.value) return null

  const dueDate = new Date(fullDateTime.value)
  const now = new Date()
  const timeDiff = dueDate.getTime() - now.getTime()
  const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24))

  if (completed.value) {
    return {
      color: 'success' as const,
      icon: 'mdi-check-circle',
      text: 'Completed'
    }
  } else if (timeDiff < 0) {
    return {
      color: 'error' as const,
      icon: 'mdi-alert-circle',
      text: 'Overdue'
    }
  } else if (daysDiff === 0) {
    return {
      color: 'warning' as const,
      icon: 'mdi-clock-alert',
      text: 'Due today'
    }
  } else if (daysDiff === 1) {
    return {
      color: 'warning' as const,
      icon: 'mdi-clock',
      text: 'Due tomorrow'
    }
  } else if (daysDiff <= 7) {
    return {
      color: 'info' as const,
      icon: 'mdi-clock',
      text: `Due in ${daysDiff} days`
    }
  } else {
    return {
      color: 'info' as const,
      icon: 'mdi-clock-outline',
      text: 'Upcoming'
    }
  }
})

const minDate = new Date().toISOString().split('T')[0]!

const quickDateOptions = [
  { label: 'Today', value: getDateString(0) },
  { label: 'Tomorrow', value: getDateString(1) },
  { label: 'Next Week', value: getDateString(7) },
  { label: 'In 2 Weeks', value: getDateString(14) },
  { label: 'In 1 Month', value: getDateString(30) },
]

function getDateString(daysFromNow: number): string {
  const date = new Date()
  date.setDate(date.getDate() + daysFromNow)
  return date.toISOString().split('T')[0]!
}

function isQuickDateSelected(dateString: string): boolean {
  return selectedDate.value === dateString
}

function setQuickDate(dateString: string): void {
  selectedDate.value = dateString
  selectedTime.value = '17:00'
}

function parseExistingDate(dateString: string | null): void {
  if (!dateString) {
    selectedDate.value = ''
    selectedTime.value = '12:00'
    return
  }

  try {
    const date = new Date(dateString)

    if (isNaN(date.getTime())) {
      selectedDate.value = ''
      selectedTime.value = '12:00'
      return
    }

    selectedDate.value = date.toISOString().split('T')[0]!
    selectedTime.value = date.toISOString().split('T')[1]!.slice(0, 5)
  } catch (error) {
    selectedDate.value = ''
    selectedTime.value = '12:00'
  }
}

const handleSave = async () => {
  if (!props.card || !fullDateTime.value) return

  try {
    const date = new Date(fullDateTime.value) 
    
    if (isNaN(date.getTime())) {
      uiStore.showSnackbar('Invalid date or time selected', 'error')
      return
    }

    const isoDateString = date.toISOString()

    await cardStore.updateCard(props.card.id, {
      due_date: isoDateString,
      due_date_completed: completed.value
    })
    emit('date-updated', isoDateString)
    emit('refresh')
    uiStore.showSnackbar('Due date updated successfully', 'success')
    emit('close')
  } catch (error: any) {
    console.error('Date update error:', error)
  
    if (error.data?.message) {
        uiStore.showSnackbar(error.data.message, 'error')
      } else if (error.data?.errors?.due_date) {
        uiStore.showSnackbar(error.data.errors.due_date[0], 'error')
      } else {
        uiStore.showSnackbar('Failed to update due date', 'error')
      }
    }
}

const handleRemove = async () => {
  if (!props.card) return

  try {
    await cardStore.updateCard(props.card.id, {
      due_date: null,
      due_date_completed: false
    })
    emit('date-updated', null)
    emit('refresh')
    uiStore.showSnackbar('Due date removed', 'success')
    emit('close')
  } catch (error) {
    uiStore.showSnackbar('Failed to remove due date', 'error')
  }
}

watch(() => props.card, (newCard) => {
  if (newCard) {
    parseExistingDate(newCard.due_date)
    completed.value = newCard.due_date_completed || false
  }
}, { immediate: true })

watch([selectedDate, selectedTime], () => {
  if (fullDateTime.value) {
    const dueDate = new Date(fullDateTime.value)
    const now = new Date()
    
    if (dueDate < now && !completed.value) {
    }
  }
})
</script>

<style scoped>
.date-selection {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.quick-dates {
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 8px;
  padding: 12px;
  background: rgba(0, 0, 0, 0.02);
}

.due-date-status {
  border-radius: 8px;
}

.gap-2 {
  gap: 8px;
}

:deep(.v-date-picker) {
  border-radius: 8px;
}

:deep(.v-time-picker) {
  border-radius: 8px;
}

@media (max-width: 768px) {
  .date-selection {
    gap: 8px;
  }
  
  .quick-dates {
    padding: 8px;
  }
}
</style>