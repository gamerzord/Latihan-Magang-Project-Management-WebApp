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
            v-model="localFilters.showCompleted"
            label="Show only completed cards"
            density="compact"
            hide-details
            class="mb-2"
          />
          <v-checkbox
            v-model="localFilters.showOverdue"
            label="Show only overdue cards"
            density="compact"
            hide-details
            class="mb-2"
          />
          <!-- Show Only My Cards -->
          <v-checkbox
            v-model="localFilters.showOnlyMyCards"
            label="Show only my cards"
            density="compact"
            hide-details
            class="mb-2"
          />

          <!-- Labels Filter (if you have labels) -->
          <div v-if="availableLabels.length > 0" class="mb-3">
            <label class="text-caption text-grey d-block mb-2">Labels</label>
            <div class="d-flex flex-wrap gap-1">
              <v-chip
                v-for="label in availableLabels"
                :key="label.id"
                size="small"
                :color="label.color || 'primary'"
                :variant="isLabelSelected(label.id) ? 'flat' : 'outlined'"
                @click="toggleLabel(label.id)"
                class="cursor-pointer"
              >
                {{ label.name }}
              </v-chip>
            </div>
          </div>

          <!-- Members Filter (if you have members) -->
          <div v-if="availableMembers.length > 0" class="mb-3">
            <label class="text-caption text-grey d-block mb-2">Members</label>
            <div class="d-flex flex-wrap gap-1">
              <v-chip
                v-for="member in availableMembers"
                :key="member.id"
                size="small"
                variant="outlined"
                :class="{ 'selected-member': isMemberSelected(member.id) }"
                @click="toggleMember(member.id)"
                class="cursor-pointer"
              >
                <v-avatar size="20" class="mr-1">
                  <span class="text-caption">
                    {{ getUserInitials(member.name) }}
                  </span>
                </v-avatar>
                {{ member.name }}
              </v-chip>
            </div>
          </div>

          <!-- Clear Filters Button -->
          <div v-if="activeFilterCount > 0" class="mt-3">
            <v-btn
              variant="text"
              size="small"
              prepend-icon="mdi-filter-remove"
              @click="clearFilters"
              color="grey"
              block
            >
              Clear All Filters
            </v-btn>
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="closeFilters">Close</v-btn>
          <v-btn color="primary" @click="applyFilters">Apply</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
import type { CalendarFilter } from '~/types/models'

interface Props {
  currentMonth: Date
  eventsCount?: number
  isLoading?: boolean
  boardId?: number
  board?: any
  filters?: CalendarFilter
}

interface Emits {
  (event: 'prev-month'): void
  (event: 'next-month'): void
  (event: 'today'): void
  (event: 'refresh'): void
  (event: 'update:filters', filters: CalendarFilter): void
  (event: 'filter-change', filters: CalendarFilter): void
}

const props = withDefaults(defineProps<Props>(), {
  eventsCount: 0,
  isLoading: false,
  filters: () => ({
    showCompleted: false,
    showOverdue: false,
    showOnlyMyCards: false,
    labelIds: [],
    memberIds: []
  })
})

const emit = defineEmits<Emits>()

const route = useRoute()
const userStore = useUserStore()

const showFilters = ref(false)

const localFilters = reactive<CalendarFilter>({
  showCompleted: props.filters.showCompleted,
  showOverdue: props.filters.showOverdue,
  showOnlyMyCards: props.filters.showOnlyMyCards,
  labelIds: [...(props.filters.labelIds || [])],
  memberIds: [...(props.filters.memberIds || [])]
})

const availableLabels = computed(() => {
  return props.board?.labels || []
})

const availableMembers = computed(() => {
  return props.board?.members || []
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
  if (localFilters.showCompleted) count++
  if (localFilters.showOverdue) count++
  if (localFilters.showOnlyMyCards) count++
  if (localFilters.labelIds?.length) count++
  if (localFilters.memberIds?.length) count++
  return count
})

const getUserInitials = (name: string): string => {
  return name
    .split(' ')
    .map(part => part.charAt(0).toUpperCase())
    .join('')
    .slice(0, 2)
}

const isLabelSelected = (labelId: number) => {
  return localFilters.labelIds?.includes(labelId) || false
}

const isMemberSelected = (memberId: number) => {
  return localFilters.memberIds?.includes(memberId) || false
}

const toggleLabel = (labelId: number) => {
  if (!localFilters.labelIds) {
    localFilters.labelIds = []
  }
  
  const index = localFilters.labelIds.indexOf(labelId)
  if (index > -1) {
    localFilters.labelIds.splice(index, 1)
  } else {
    localFilters.labelIds.push(labelId)
  }
}

const toggleMember = (memberId: number) => {
  if (!localFilters.memberIds) {
    localFilters.memberIds = []
  }
  
  const index = localFilters.memberIds.indexOf(memberId)
  if (index > -1) {
    localFilters.memberIds.splice(index, 1)
  } else {
    localFilters.memberIds.push(memberId)
  }
}

const clearFilters = () => {
  localFilters.showCompleted = false
  localFilters.showOverdue = false
  localFilters.showOnlyMyCards = false
  localFilters.labelIds = []
  localFilters.memberIds = []
}

const closeFilters = () => {
  showFilters.value = false
  Object.assign(localFilters, props.filters)
}

const applyFilters = () => {
  emit('update:filters', { ...localFilters })
  emit('filter-change', { ...localFilters })
  showFilters.value = false
}

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

watch(() => props.filters, (newFilters) => {
  Object.assign(localFilters, newFilters)
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