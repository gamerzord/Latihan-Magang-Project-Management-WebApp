<template>
  <div class="calendar-header">
    <div class="d-flex align-center justify-space-between flex-wrap">
      <!-- Left Section: Navigation -->
      <div class="d-flex align-center gap-3">
        <!-- Navigation Buttons -->
        <div class="d-flex align-center gap-1">
          <v-btn
            icon="mdi-chevron-left"
            variant="text"
            size="small"
            @click="$emit('prev-month')"
            :disabled="isLoading"
          />
          
          <v-btn
            icon="mdi-chevron-right"
            variant="text"
            size="small"
            @click="$emit('next-month')"
            :disabled="isLoading"
          />
        </div>

        <!-- Month/Year Display -->
        <div class="month-display">
          <h2 class="text-h5 font-weight-bold mb-0">
            {{ monthName }} {{ year }}
          </h2>
          <p class="text-caption text-grey mb-0">
            {{ eventsCount }} events
          </p>
        </div>

        <!-- Quick Actions -->
        <v-btn
          variant="outlined"
          size="small"
          prepend-icon="mdi-calendar-today"
          @click="$emit('today')"
          :disabled="isLoading"
        >
          Today
        </v-btn>

        <!-- View Toggle -->
        <v-btn-toggle
          v-model="viewMode"
          divided
          variant="outlined"
          density="compact"
        >
          <v-btn value="month" size="small">
            <v-icon size="small" class="mr-1">mdi-view-module</v-icon>
            Month
          </v-btn>
          <v-btn value="week" size="small">
            <v-icon size="small" class="mr-1">mdi-view-week</v-icon>
            Week
          </v-btn>
        </v-btn-toggle>
      </div>

      <!-- Right Section: Actions -->
      <div class="d-flex align-center gap-2">
        <!-- Loading Indicator -->
        <v-progress-circular
          v-if="isLoading"
          indeterminate
          size="20"
          width="2"
          color="primary"
        />

        <!-- Filter Button -->
        <v-btn
          variant="text"
          size="small"
          prepend-icon="mdi-filter"
          @click="showFilters = true"
        >
          Filters
          <v-badge
            v-if="activeFilterCount > 0"
            :content="activeFilterCount"
            color="primary"
            inline
            class="ml-1"
          />
        </v-btn>

        <!-- Board Navigation -->
        <v-btn
          prepend-icon="mdi-view-dashboard"
          variant="outlined"
          size="small"
          @click="navigateToBoard"
        >
          Board View
        </v-btn>

        <!-- Refresh Button -->
        <v-btn
          icon="mdi-refresh"
          variant="text"
          size="small"
          @click="$emit('refresh')"
          :loading="isLoading"
        />
      </div>
    </div>

    <!-- Filter Dialog -->
    <v-dialog v-model="showFilters" max-width="400">
      <v-card>
        <v-card-title>Calendar Filters</v-card-title>
        <v-card-text>
          <v-checkbox
            v-model="filters.showCompleted"
            label="Show completed cards"
            density="compact"
          />
          <v-checkbox
            v-model="filters.showOverdue"
            label="Show overdue cards"
            density="compact"
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showFilters = false">Close</v-btn>
          <v-btn color="primary" @click="applyFilters">Apply</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
interface Props {
  currentMonth: Date
  eventsCount?: number
  isLoading?: boolean
  boardId?: number
}

interface Emits {
  (event: 'prev-month'): void
  (event: 'next-month'): void
  (event: 'today'): void
  (event: 'refresh'): void
  (event: 'view-change', view: string): void
  (event: 'filter-change', filters: any): void
}

const props = withDefaults(defineProps<Props>(), {
  eventsCount: 0,
  isLoading: false
})

const emit = defineEmits<Emits>()

const route = useRoute()
const uiStore = useUiStore()

const viewMode = ref<'month' | 'week'>('month')
const showFilters = ref(false)
const filters = reactive({
  showCompleted: false,
  showOverdue: true
})

const monthName = computed(() => {
  const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ]
  return months[props.currentMonth.getMonth()]
})

const year = computed(() => props.currentMonth.getFullYear())

const activeFilterCount = computed(() => {
  let count = 0
  if (filters.showCompleted) count++
  if (filters.showOverdue) count++
  return count
})

const isCurrentMonth = computed(() => {
  const now = new Date()
  return props.currentMonth.getMonth() === now.getMonth() && 
         props.currentMonth.getFullYear() === now.getFullYear()
})

const navigateToBoard = () => {
  if (props.boardId) {
    navigateTo(`/boards/${props.boardId}`)
  } else {
    const boardId = route.params.id
    if (boardId && typeof boardId === 'string') {
      navigateTo(`/boards/${boardId}`)
    } else {
      navigateTo('/')
    }
  }
}

const applyFilters = () => {
  emit('filter-change', { ...filters })
  showFilters.value = false
}

watch(viewMode, (newView) => {
  emit('view-change', newView)
})

watch(filters, (newFilters) => {
}, { deep: true })

const handleKeydown = (event: KeyboardEvent) => {
  if (event.ctrlKey || event.metaKey) {
    switch (event.key) {
      case 'ArrowLeft':
        event.preventDefault()
        emit('prev-month')
        break
      case 'ArrowRight':
        event.preventDefault()
        emit('next-month')
        break
      case 't':
        event.preventDefault()
        emit('today')
        break
      case 'r':
        event.preventDefault()
        emit('refresh')
        break
    }
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
.calendar-header {
  padding: 20px 24px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin-bottom: 24px;
  border: 1px solid rgba(0, 0, 0, 0.08);
}

.month-display {
  min-width: 200px;
}

.gap-1 {
  gap: 4px;
}

.gap-2 {
  gap: 8px;
}

.gap-3 {
  gap: 12px;
}

:deep(.v-btn-toggle .v-btn) {
  border-radius: 6px;
  margin: 0 2px;
}

:deep(.v-btn-toggle .v-btn--active) {
  background-color: rgba(25, 118, 210, 0.08);
  color: rgb(25, 118, 210);
}

@media (max-width: 768px) {
  .calendar-header {
    padding: 16px;
  }

  .month-display {
    min-width: auto;
  }

  .month-display h2 {
    font-size: 1.25rem;
  }

  .d-flex.flex-wrap {
    gap: 12px;
  }

  .v-btn {
    font-size: 0.75rem;
  }

  .v-btn--size-small {
    min-width: auto;
    padding: 0 8px;
  }
}

@media (max-width: 600px) {
  .calendar-header {
    padding: 12px;
  }

  .month-display h2 {
    font-size: 1.125rem;
  }

  .v-btn-toggle {
    flex-direction: column;
    gap: 4px;
  }

  .v-btn-toggle .v-btn {
    margin: 2px 0;
  }
}

.month-display h2 {
  transition: all 0.3s ease;
}

.v-btn:hover {
  transform: translateY(-1px);
  transition: transform 0.2s ease;
}

.calendar-header--loading {
  opacity: 0.7;
  pointer-events: none;
}
</style>