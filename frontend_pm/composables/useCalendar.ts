import { useUiStore } from '~/stores/ui'
import { useBoardStore } from '~/stores/board'
import type { CalendarEvent, CalendarFilter } from '~/types/models'

export const useCalendar = () => {
  const events = ref<CalendarEvent[]>([])
  const loading = ref(false)
  const currentMonth = ref(new Date())
  const filters = ref<CalendarFilter>({
    showCompleted: false,
  })

  const fetchCalendarEvents = async (boardId: number) => {
    loading.value = true
    try {
      const boardStore = useBoardStore()
      
      await boardStore.fetchBoard(boardId)
      
      if (!boardStore.currentBoard) {
        throw new Error('Board not found')
      }

      const calendarEvents: CalendarEvent[] = []

      boardStore.currentBoard.lists?.forEach(list => {
        list.cards?.forEach(card => {
          const shouldInclude = card.due_date && 
            (!card.archived || filters.value.showCompleted) &&
            (!filters.value.workspaceId || boardStore.currentBoard?.workspace_id === filters.value.workspaceId) &&
            (!filters.value.labelIds?.length || card.labels?.some(label => filters.value.labelIds?.includes(label.id))) &&
            (!filters.value.memberIds?.length || card.members?.some(member => filters.value.memberIds?.includes(member.id)))

          if (shouldInclude) {
            calendarEvents.push({
              id: card.id,
              title: card.title,
              start: card.due_date,
              card,
              color: card.labels?.[0]?.color,
            })
          }
        })
      })

      events.value = calendarEvents
    } catch (error) {
      console.error('Failed to fetch calendar events:', error)
      const uiStore = useUiStore()
      uiStore.showSnackbar('Failed to load calendar events', 'error')
    } finally {
      loading.value = false
    }
  }

  const setMonth = (date: Date) => {
    currentMonth.value = date
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

  const updateFilters = (newFilters: Partial<CalendarFilter>) => {
    filters.value = { ...filters.value, ...newFilters }
  }

  const getEventsForMonth = computed(() => {
    const year = currentMonth.value.getFullYear()
    const month = currentMonth.value.getMonth()
    
    return events.value.filter(event => {
      const eventDate = new Date(event.start)
      return eventDate.getFullYear() === year && eventDate.getMonth() === month
    })
  })

  const getEventsForDate = (date: Date) => {
    const dateString = date.toISOString().split('T')[0]
    return events.value.filter(event => 
      event.start.split('T')[0] === dateString
    )
  }

  return {
    events: readonly(events),
    loading: readonly(loading),
    currentMonth: readonly(currentMonth),
    filters: readonly(filters),
    getEventsForMonth,
    getEventsForDate,
    fetchCalendarEvents,
    setMonth,
    nextMonth,
    prevMonth,
    goToToday,
    updateFilters,
  }
}