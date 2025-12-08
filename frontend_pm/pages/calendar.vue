<template>
  <div class="global-calendar-page">
    <!-- Header -->
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
              @click="prevMonth"
              :disabled="loading"
            />
            
            <v-btn
              icon="mdi-chevron-right"
              variant="text"
              size="small"
              @click="nextMonth"
              :disabled="loading"
            />
          </div>

          <!-- Month/Year Display -->
          <div class="month-display">
            <h2 class="text-h5 font-weight-bold mb-0">
              {{ monthName }} {{ year }}
            </h2>
            <p class="text-caption text-grey mb-0">
              {{ eventsCount }} events across {{ workspacesCount }} workspaces
            </p>
          </div>

          <!-- Quick Actions -->
          <v-btn
            variant="outlined"
            size="small"
            prepend-icon="mdi-calendar-today"
            @click="goToToday"
            :disabled="loading"
          >
            Today
          </v-btn>
        </div>

        <!-- Right Section: Actions -->
        <div class="d-flex align-center gap-2">
          <!-- Loading Indicator -->
          <v-progress-circular
            v-if="loading"
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

          <!-- Refresh Button -->
          <v-btn
            icon="mdi-refresh"
            variant="text"
            size="small"
            @click="fetchAllEvents"
            :loading="loading"
          />
        </div>
      </div>

      <!-- Filter Dialog -->
      <v-dialog v-model="showFilters" max-width="400">
        <v-card>
          <v-card-title>Calendar Filters</v-card-title>
          <v-card-text>
            <v-checkbox
              v-model="tempFilters.showCompleted"
              label="Show only completed cards"
              density="compact"
              hide-details
              class="mb-2"
            />
            <v-checkbox
              v-model="tempFilters.showOverdue"
              label="Show only overdue cards"
              density="compact"
              hide-details
              class="mb-2"
            />
            <v-checkbox
              v-model="tempFilters.showOnlyMyCards"
              label="Show only my cards"
              density="compact"
              hide-details
              class="mb-2"
            />

            <!-- Workspaces Filter -->
            <div v-if="workspaces.length > 0" class="mb-3">
              <label class="text-caption text-grey d-block mb-2">Workspaces</label>
              <div class="d-flex flex-wrap gap-1">
                <v-chip
                  v-for="workspace in workspaces"
                  :key="workspace.id"
                  size="small"
                  :variant="isWorkspaceSelected(workspace.id) ? 'flat' : 'outlined'"
                  :color="isWorkspaceSelected(workspace.id) ? 'primary' : 'default'"
                  @click="toggleWorkspace(workspace.id)"
                  class="cursor-pointer"
                >
                  {{ workspace.name }}
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

    <!-- Empty State -->
    <div v-if="!loading && events.length === 0" class="empty-state">
      <v-card variant="outlined" class="pa-8 text-center" max-width="400">
        <v-icon size="72" color="grey-lighten-1" class="mb-4">mdi-calendar-blank</v-icon>
        <h3 class="text-h5 mb-2">No events found</h3>
        <p class="text-body-2 text-grey mb-4">
          There are no cards with due dates in your workspaces yet.
        </p>
        <v-btn color="primary" to="/">Go to Workspaces</v-btn>
      </v-card>
    </div>

    <!-- Calendar Grid -->
    <div v-else-if="!loading" class="calendar-container">
      <!-- Day headers -->
      <div class="calendar-header-row">
        <div
          v-for="day in dayHeaders"
          :key="day"
          class="calendar-header-cell"
        >
          {{ day }}
        </div>
      </div>

      <!-- Calendar grid -->
      <div class="calendar-grid">
        <div
          v-for="day in calendarDays"
          :key="day.date.toISOString()"
          class="calendar-day"
          :class="{
            'today': day.isToday,
            'other-month': day.isOtherMonth,
            'has-events': day.events.length > 0,
            'weekend': day.isWeekend
          }"
          @click="handleDayClick(day)"
        >
          <div class="day-header">
            <div class="day-number">{{ day.date.getDate() }}</div>
            <div v-if="day.events.length > 0" class="event-count">
              {{ day.events.length }}
            </div>
          </div>
          
          <div class="day-events">
            <div
              v-for="event in day.events.slice(0, 3)"
              :key="event.id"
              class="calendar-event"
              :class="{
                'completed': event.card.due_date_completed,
                'overdue': isEventOverdue(event),
                'due-today': isEventDueToday(event)
              }"
              :style="{ 
                backgroundColor: getEventColor(event),
                borderLeft: `3px solid ${getEventBorderColor(event)}`
              }"
              @click.stop="handleEventClick(event)"
            >
              <div class="event-content">
                <v-icon 
                  v-if="event.card.due_date_completed" 
                  size="x-small" 
                  color="white"
                  class="event-icon"
                >
                  mdi-check-circle
                </v-icon>
                <v-icon 
                  v-else-if="isEventOverdue(event)" 
                  size="x-small" 
                  color="white"
                  class="event-icon"
                >
                  mdi-alert-circle
                </v-icon>
                <span class="event-title">{{ event.title }}</span>
              </div>
              
              <!-- Workspace badge -->
              <div class="event-workspace">
                {{ event.workspaceName }}
              </div>
            </div>
            
            <!-- More events indicator -->
            <div 
              v-if="day.events.length > 3" 
              class="more-events"
              @click.stop="handleMoreEventsClick(day)"
            >
              +{{ day.events.length - 3 }} more
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="d-flex justify-center pa-8">
      <v-progress-circular indeterminate color="primary" size="64" />
    </div>

    <!-- Day Events Modal -->
    <v-dialog v-model="showDayEventsModal" max-width="600">
      <v-card>
        <v-card-title>
          Events for {{ selectedDayDate ? formatFullDate(selectedDayDate) : '' }}
        </v-card-title>
        <v-card-text>
          <v-list>
            <v-list-item
              v-for="event in selectedDayEvents"
              :key="event.id"
              class="event-list-item"
              @click="handleEventClick(event)"
            >
              <template #prepend>
                <div
                  class="event-color-indicator"
                  :style="{ backgroundColor: getEventColor(event) }"
                />
              </template>
              
              <v-list-item-title>{{ event.title }}</v-list-item-title>
              <v-list-item-subtitle>
                <div class="d-flex align-center gap-2 flex-wrap">
                  <span>{{ event.workspaceName }} • {{ event.boardName }}</span>
                  <v-chip
                    v-if="event.card.due_date_completed"
                    size="x-small"
                    color="success"
                    variant="flat"
                  >
                    Completed
                  </v-chip>
                  <v-chip
                    v-else-if="isEventOverdue(event)"
                    size="x-small"
                    color="error"
                    variant="flat"
                  >
                    Overdue
                  </v-chip>
                </div>
              </v-list-item-subtitle>
            </v-list-item>
          </v-list>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="showDayEventsModal = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Card Modal -->
    <CardModal
      v-if="uiStore.isCardModalOpen && uiStore.currentCardModalId"
      :card-id="uiStore.currentCardModalId"
      @close="uiStore.closeCardModal()"
    />
  </div>
</template>

<script setup lang="ts">
import type { CalendarEvent, Workspace, Board } from '~/types/models'

interface ExtendedCalendarEvent extends CalendarEvent {
  workspaceName: string
  boardName: string
  workspaceId: number
  boardId: number
}

interface CalendarDay {
  date: Date
  isToday: boolean
  isOtherMonth: boolean
  isWeekend: boolean
  events: ExtendedCalendarEvent[]
}

const uiStore = useUiStore()
const userStore = useUserStore()
const workspaceStore = useWorkspaceStore()
const config = useRuntimeConfig()

const loading = ref(false)
const currentMonth = ref(new Date())
const events = ref<ExtendedCalendarEvent[]>([])
const workspaces = ref<Workspace[]>([])

const showFilters = ref(false)
const showDayEventsModal = ref(false)
const selectedDayDate = ref<Date | null>(null)
const selectedDayEvents = ref<ExtendedCalendarEvent[]>([])

const filters = reactive({
  showCompleted: false,
  showOverdue: false,
  showOnlyMyCards: false,
  workspaceIds: [] as number[]
})

const tempFilters = reactive({
  showCompleted: false,
  showOverdue: false,
  showOnlyMyCards: false,
  workspaceIds: [] as number[]
})

const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

const monthName = computed(() => {
  const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ]
  return months[currentMonth.value.getMonth()]
})

const year = computed(() => currentMonth.value.getFullYear())

const workspacesCount = computed(() => {
  const uniqueWorkspaces = new Set(events.value.map(e => e.workspaceId))
  return uniqueWorkspaces.size
})

const eventsCount = computed(() => filteredEvents.value.length)

const activeFilterCount = computed(() => {
  let count = 0
  if (filters.showCompleted) count++
  if (filters.showOverdue) count++
  if (filters.showOnlyMyCards) count++
  if (filters.workspaceIds.length > 0) count++
  return count
})

const filteredEvents = computed(() => {
  return events.value.filter(event => {
    // Completed filter
    const isCompleted = event.card.archived || event.card.due_date_completed
    if (filters.showCompleted) {
      if (!isCompleted) return false
    } else {
      if (isCompleted) return false
    }

    // Overdue filter
    if (filters.showOverdue) {
      if (!isEventOverdue(event)) return false
    }

    // My cards filter
    if (filters.showOnlyMyCards && userStore.user) {
      const isMyCard = event.card.members?.some(m => m.id === userStore.user?.id) || 
                      event.card.creator?.id === userStore.user?.id
      if (!isMyCard) return false
    }

    // Workspace filter
    if (filters.workspaceIds.length > 0) {
      if (!filters.workspaceIds.includes(event.workspaceId)) return false
    }

    return true
  })
})

const calendarDays = computed(() => {
  const year = currentMonth.value.getFullYear()
  const month = currentMonth.value.getMonth()
  
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)
  
  const startDate = new Date(firstDay)
  startDate.setDate(startDate.getDate() - startDate.getDay())
  
  const endDate = new Date(lastDay)
  endDate.setDate(endDate.getDate() + (6 - endDate.getDay()))
  
  const days: CalendarDay[] = []
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  
  const currentDate = new Date(startDate)
  
  while (currentDate <= endDate) {
    const dateStr = currentDate.toISOString().split('T')[0]
    
    const dayEvents = filteredEvents.value.filter(event => {
      const eventDate = new Date(event.start)
      eventDate.setHours(0, 0, 0, 0)
      return eventDate.toISOString().split('T')[0] === dateStr
    })
    
    const isWeekend = currentDate.getDay() === 0 || currentDate.getDay() === 6
    
    days.push({
      date: new Date(currentDate),
      isToday: currentDate.getTime() === today.getTime(),
      isOtherMonth: currentDate.getMonth() !== month,
      isWeekend,
      events: dayEvents
    })
    
    currentDate.setDate(currentDate.getDate() + 1)
  }
  
  return days
})

const isEventOverdue = (event: ExtendedCalendarEvent): boolean => {
  if (event.card.due_date_completed) return false
  const eventDate = new Date(event.start)
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  eventDate.setHours(0, 0, 0, 0)
  return eventDate < today
}

const isEventDueToday = (event: ExtendedCalendarEvent): boolean => {
  if (event.card.due_date_completed) return false
  const eventDate = new Date(event.start)
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  eventDate.setHours(0, 0, 0, 0)
  return eventDate.getTime() === today.getTime()
}

const getEventColor = (event: ExtendedCalendarEvent): string => {
  if (event.card.due_date_completed) return '#4caf50'
  if (isEventOverdue(event)) return '#f44336'
  if (isEventDueToday(event)) return '#ff9800'
  return event.color || '#0079bf'
}

const getEventBorderColor = (event: ExtendedCalendarEvent): string => {
  const color = getEventColor(event)
  if (color === '#4caf50') return '#388e3c'
  if (color === '#f44336') return '#d32f2f'
  if (color === '#ff9800') return '#f57c00'
  return '#005a8f'
}

const formatFullDate = (date: Date): string => {
  return date.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const fetchAllEvents = async () => {
  loading.value = true
  try {
    await workspaceStore.fetchWorkspaces()
    workspaces.value = workspaceStore.workspaces || []

    const allEvents: ExtendedCalendarEvent[] = []

    for (const workspace of workspaces.value) {
      if (!workspace.boards) continue

      for (const board of workspace.boards) {
        const response = await $fetch<{ board: Board }>(`${config.public.apiBase}/boards/${board.id}`)
        const fullBoard = response.board

        if (!fullBoard.lists) continue

        fullBoard.lists.forEach((list: any) => {
          if (!list.cards) return

          list.cards.forEach((card: any) => {
            if (!card.due_date) return

            const isCompleted = card.archived || card.due_date_completed
            const isOverdue = () => {
              const dueDate = new Date(card.due_date)
              const today = new Date()
              const dueDateOnly = new Date(dueDate.getFullYear(), dueDate.getMonth(), dueDate.getDate())
              const todayOnly = new Date(today.getFullYear(), today.getMonth(), today.getDate())
              return dueDateOnly < todayOnly && !isCompleted
            }

            allEvents.push({
              id: card.id,
              title: card.title,
              start: card.due_date,
              end: card.due_date,
              card,
              color: card.labels?.[0]?.color,
              isOverdue: isOverdue(),
              isCompleted,
              workspaceName: workspace.name,
              boardName: fullBoard.title,
              workspaceId: workspace.id,
              boardId: fullBoard.id
            })
          })
        })
      }
    }

    events.value = allEvents
  } catch (error) {
    console.error('Failed to fetch calendar events:', error)
    uiStore.showSnackbar('Failed to load calendar events', 'error')
  } finally {
    loading.value = false
  }
}

const nextMonth = () => {
  const next = new Date(currentMonth.value)
  next.setMonth(next.getMonth() + 1)
  currentMonth.value = next
}

const prevMonth = () => {
  const prev = new Date(currentMonth.value)
  prev.setMonth(prev.getMonth() - 1)
  currentMonth.value = prev
}

const goToToday = () => {
  currentMonth.value = new Date()
}

const handleDayClick = (day: CalendarDay) => {
  if (day.events.length > 3) {
    handleMoreEventsClick(day)
  }
}

const handleEventClick = (event: ExtendedCalendarEvent) => {
  uiStore.openCardModal(event.id)
}

const handleMoreEventsClick = (day: CalendarDay) => {
  selectedDayDate.value = day.date
  selectedDayEvents.value = day.events
  showDayEventsModal.value = true
}

const isWorkspaceSelected = (workspaceId: number) => {
  return tempFilters.workspaceIds.includes(workspaceId)
}

const toggleWorkspace = (workspaceId: number) => {
  const index = tempFilters.workspaceIds.indexOf(workspaceId)
  if (index > -1) {
    tempFilters.workspaceIds.splice(index, 1)
  } else {
    tempFilters.workspaceIds.push(workspaceId)
  }
}

const clearFilters = () => {
  tempFilters.showCompleted = false
  tempFilters.showOverdue = false
  tempFilters.showOnlyMyCards = false
  tempFilters.workspaceIds = []
}

const closeFilters = () => {
  showFilters.value = false
}

const applyFilters = () => {
  filters.showCompleted = tempFilters.showCompleted
  filters.showOverdue = tempFilters.showOverdue
  filters.showOnlyMyCards = tempFilters.showOnlyMyCards
  filters.workspaceIds = [...tempFilters.workspaceIds]
  showFilters.value = false
}

watch(showFilters, (open) => {
  if (open) {
    Object.assign(tempFilters, filters)
  }
})

useAutoRefresh(async () => {
  await fetchAllEvents()
})

onMounted(() => {
  fetchAllEvents()
})

useSeoMeta({
  title: 'Global Calendar - Project Management',
  ogTitle: 'Global Calendar - Project Management'
})
</script>

<style scoped>
.global-calendar-page {
  padding: 16px;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background-color: #f8f9fa;
  gap: 24px;
}

.calendar-header {
  padding: 20px 24px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  border: 1px solid rgba(0, 0, 0, 0.08);
}

.month-display {
  min-width: 250px;
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

.calendar-container {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  border: 1px solid rgba(0, 0, 0, 0.08);
}

.calendar-header-row {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  background: #f8f9fa;
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

.calendar-header-cell {
  padding: 16px 12px;
  text-align: center;
  font-weight: 600;
  color: #5e6c84;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 1px;
  background: rgba(0, 0, 0, 0.08);
}

.calendar-day {
  background: white;
  min-height: 140px;
  padding: 12px 8px;
  position: relative;
  transition: all 0.2s ease;
  cursor: pointer;
}

.calendar-day:hover {
  background: #fafbfc;
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  z-index: 1;
}

.calendar-day.today {
  background: #fff8e1;
}

.calendar-day.other-month {
  background: #fafbfc;
  opacity: 0.5;
}

.calendar-day.weekend {
  background: #f9f9f9;
}

.day-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.day-number {
  font-weight: 600;
  color: #172b4d;
  font-size: 0.875rem;
}

.calendar-day.today .day-number {
  background: #2196f3;
  color: white;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
}

.event-count {
  background: #e3f2fd;
  color: #1976d2;
  font-size: 0.75rem;
  padding: 2px 6px;
  border-radius: 12px;
  font-weight: 600;
}

.day-events {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-height: 60px;
}

.calendar-event {
  padding: 6px 8px;
  border-radius: 6px;
  font-size: 0.75rem;
  color: white;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.calendar-event:hover {
  transform: translateX(2px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.event-content {
  display: flex;
  align-items: center;
  gap: 4px;
}

.event-icon {
  flex-shrink: 0;
}

.event-title {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
  font-weight: 500;
}

.event-workspace {
  font-size: 0.625rem;
  opacity: 0.9;
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.more-events {
  padding: 4px 8px;
  font-size: 0.75rem;
  color: #5e6c84;
  cursor: pointer;
  text-align: center;
  border-radius: 4px;
  transition: background 0.2s ease;
}

.more-events:hover {
  background: rgba(0, 0, 0, 0.04);
  text-decoration: underline;
}

.event-list-item {
  border-radius: 8px;
  margin-bottom: 4px;
  transition: background 0.2s ease;
  cursor: pointer;
}

.event-list-item:hover {
  background: rgba(0, 0, 0, 0.04);
}

.event-color-indicator {
  width: 4px;
  height: 32px;
  border-radius: 2px;
  margin-right: 12px;
}

.cursor-pointer {
  cursor: pointer;
}

.empty-state {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 400px;
}

@media (max-width: 768px) {
  .global-calendar-page {
    padding: 8px;
  }

  .calendar-header {
    padding: 16px;
  }

  .month-display {
    min-width: auto;
  }

  .calendar-day {
    min-height: 100px;
    padding: 8px 4px;
  }
}
</style>