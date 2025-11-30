<template>
  <div class="calendar-container">
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
          'weekend': day.isWeekend,
          'selected': isDateSelected(day.date)
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
            v-for="event in day.events.slice(0, maxEventsPerDay)"
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
            @click.stop="$emit('event-click', event)"
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
            
            <!-- Event time -->
            <div v-if="showEventTime && event.start" class="event-time">
              {{ formatEventTime(event.start) }}
            </div>
          </div>
          
          <!-- More events indicator -->
          <div 
            v-if="day.events.length > maxEventsPerDay" 
            class="more-events"
            @click.stop="handleMoreEventsClick(day)"
          >
            +{{ day.events.length - maxEventsPerDay }} more
          </div>

          <!-- Empty state -->
          <div 
            v-else-if="day.events.length === 0 && !day.isOtherMonth" 
            class="empty-day"
            @click.stop="handleEmptyDayClick(day)"
          >
            <v-icon size="small" color="grey-lighten-2">mdi-plus</v-icon>
          </div>
        </div>
      </div>
    </div>

    <!-- Day Events Modal -->
    <v-dialog v-model="showDayEventsModal" max-width="500">
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
              @click="$emit('event-click', event)"
            >
              <template #prepend>
                <div
                  class="event-color-indicator"
                  :style="{ backgroundColor: getEventColor(event) }"
                />
              </template>
              
              <v-list-item-title>{{ event.title }}</v-list-item-title>
              <v-list-item-subtitle>
                {{ formatEventTime(event.start) }}
                <v-chip
                  v-if="event.card.due_date_completed"
                  size="x-small"
                  color="success"
                  variant="flat"
                  class="ml-2"
                >
                  Completed
                </v-chip>
                <v-chip
                  v-else-if="isEventOverdue(event)"
                  size="x-small"
                  color="error"
                  variant="flat"
                  class="ml-2"
                >
                  Overdue
                </v-chip>
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
  </div>
</template>

<script setup lang="ts">
import type { CalendarEvent } from '~/types/models'

interface Props {
  events: CalendarEvent[]
  currentMonth: Date
  selectedDate?: Date
  showEventTime?: boolean
  maxEventsPerDay?: number
}

interface Emits {
  (event: 'event-click', calendarEvent: CalendarEvent): void
  (event: 'day-click', date: Date): void
  (event: 'empty-day-click', date: Date): void
}

const props = withDefaults(defineProps<Props>(), {
  showEventTime: true,
  maxEventsPerDay: 3,
  selectedDate: undefined
})

const emit = defineEmits<Emits>()

const showDayEventsModal = ref(false)
const selectedDayDate = ref<Date | null>(null)
const selectedDayEvents = ref<CalendarEvent[]>([])

const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

interface CalendarDay {
  date: Date
  isToday: boolean
  isOtherMonth: boolean
  isWeekend: boolean
  events: CalendarEvent[]
}

const calendarDays = computed(() => {
  const year = props.currentMonth.getFullYear()
  const month = props.currentMonth.getMonth()
  
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
    
    const dayEvents = props.events.filter(event => {
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

const isDateSelected = (date: Date): boolean => {
  if (!props.selectedDate) return false
  return date.toDateString() === props.selectedDate.toDateString()
}

const isEventOverdue = (event: CalendarEvent): boolean => {
  if (event.card.due_date_completed) return false
  const eventDate = new Date(event.start)
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  eventDate.setHours(0, 0, 0, 0)
  return eventDate < today
}

const isEventDueToday = (event: CalendarEvent): boolean => {
  if (event.card.due_date_completed) return false
  const eventDate = new Date(event.start)
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  eventDate.setHours(0, 0, 0, 0)
  return eventDate.getTime() === today.getTime()
}

const getEventColor = (event: CalendarEvent): string => {
  if (event.card.due_date_completed) return '#4caf50'
  if (isEventOverdue(event)) return '#f44336'
  if (isEventDueToday(event)) return '#ff9800'
  return event.color || '#0079bf'
}

const getEventBorderColor = (event: CalendarEvent): string => {
  const color = getEventColor(event)
  if (color === '#4caf50') return '#388e3c'
  if (color === '#f44336') return '#d32f2f'
  if (color === '#ff9800') return '#f57c00'
  return '#005a8f'
}

const formatEventTime = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  })
}

const formatFullDate = (date: Date): string => {
  return date.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const handleDayClick = (day: CalendarDay) => {
  emit('day-click', day.date)
}

const handleEmptyDayClick = (day: CalendarDay) => {
  if (!day.isOtherMonth) {
    emit('empty-day-click', day.date)
  }
}

const handleMoreEventsClick = (day: CalendarDay) => {
  selectedDayDate.value = day.date
  selectedDayEvents.value = day.events
  showDayEventsModal.value = true
}

const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Escape' && showDayEventsModal.value) {
    showDayEventsModal.value = false
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

.calendar-day.selected {
  background: #e3f2fd;
  border: 2px solid #2196f3;
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

.event-time {
  font-size: 0.625rem;
  opacity: 0.9;
  font-weight: 500;
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

.empty-day {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 60px;
  opacity: 0.3;
  transition: opacity 0.2s ease;
  cursor: pointer;
}

.empty-day:hover {
  opacity: 0.6;
}

/* Modal styles */
.event-list-item {
  border-radius: 8px;
  margin-bottom: 4px;
  transition: background 0.2s ease;
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

@media (max-width: 1024px) {
  .calendar-day {
    min-height: 120px;
    padding: 8px 4px;
  }
  
  .day-events {
    min-height: 50px;
  }
}

@media (max-width: 768px) {
  .calendar-header-cell {
    padding: 12px 4px;
    font-size: 0.75rem;
  }
  
  .calendar-day {
    min-height: 100px;
  }
  
  .calendar-event {
    padding: 4px 6px;
    font-size: 0.7rem;
  }
  
  .event-title {
    font-size: 0.7rem;
  }
}

@media (max-width: 480px) {
  .calendar-container {
    border-radius: 8px;
  }
  
  .calendar-day {
    min-height: 80px;
    padding: 6px 2px;
  }
  
  .day-header {
    margin-bottom: 4px;
  }
  
  .day-number {
    font-size: 0.75rem;
  }
  
  .calendar-event {
    padding: 2px 4px;
  }
  
  .event-title {
    display: none;
  }
  
  .event-content {
    justify-content: center;
  }
}
</style>