import type { CalendarEvent, CalendarFilter } from '~/types/models'

export const useCalendar = () => {
  const events = ref<CalendarEvent[]>([])
  const loading = ref(false)
  const currentMonth = ref(new Date())
  const filters = ref<CalendarFilter>({
    showCompleted: false,
    showOverdue: false,
    showOnlyMyCards: false,
    labelIds: [],
    memberIds: []
  })

  const fetchCalendarEvents = async (boardId: number) => {
    loading.value = true
    try {
      const boardStore = useBoardStore()
      const userStore = useUserStore()
      
      await boardStore.fetchBoard(boardId)
      
      if (!boardStore.currentBoard) {
        throw new Error('Board not found')
      }

      const calendarEvents: CalendarEvent[] = []
      const now = new Date()
      const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())

      boardStore.currentBoard.lists?.forEach(list => {
        list.cards?.forEach(card => {
          if (!card.due_date) return
          
          const isCompleted = card.archived || card.due_date_completed

          const isOverdue = () => {
            const dueDate = new Date(card.due_date)
            const today = new Date()
            
            const dueDateOnly = new Date(dueDate.getFullYear(), dueDate.getMonth(), dueDate.getDate())
            const todayOnly = new Date(today.getFullYear(), today.getMonth(), today.getDate())
            
            return dueDateOnly < todayOnly && !isCompleted
          }
          
          let shouldInclude = true
          
          if (filters.value.showCompleted) {
            if (!isCompleted) {
              shouldInclude = false
            }
          } else {
            if (isCompleted) {
              shouldInclude = false
            }
          }
          
          if (filters.value.showOverdue) {
            if (!isOverdue()) {
              shouldInclude = false
            }
          }
          
          if (filters.value.showOnlyMyCards && userStore.user) {
            const isMyCard = card.members?.some(m => m.id === userStore.user?.id) || 
                            card.creator?.id === userStore.user?.id
            if (!isMyCard) {
              shouldInclude = false
            }
          }
          
          if (filters.value.labelIds?.length) {
            const hasMatchingLabel = card.labels?.some(label => 
              filters.value.labelIds?.includes(label.id)
            )
            if (!hasMatchingLabel) {
              shouldInclude = false
            }
          }
          
          if (filters.value.memberIds?.length) {
            const hasMatchingMember = card.members?.some(member => 
              filters.value.memberIds?.includes(member.id)
            )
            if (!hasMatchingMember) {
              shouldInclude = false
            }
          }
          
          if (filters.value.workspaceId && 
              boardStore.currentBoard?.workspace_id !== filters.value.workspaceId) {
            shouldInclude = false
          }

          if (shouldInclude) {
            calendarEvents.push({
              id: card.id,
              title: card.title,
              start: card.due_date,
              end: card.due_date,
              card,
              color: card.labels?.[0]?.color,
              isOverdue: isOverdue(),
              isCompleted
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

  const clearFilters = () => {
    filters.value = {
      showCompleted: false,
      showOverdue: false,
      showOnlyMyCards: false,
      labelIds: [],
      memberIds: []
    }
  }

  const hasActiveFilters = computed(() => {
    return filters.value.showCompleted || 
           filters.value.showOverdue || 
           filters.value.showOnlyMyCards ||
           (filters.value.labelIds?.length || 0) > 0 ||
           (filters.value.memberIds?.length || 0) > 0
  })

  return {
    events: events,
    loading: loading,
    currentMonth: currentMonth,
    filters: filters,
    getEventsForMonth,
    getEventsForDate,
    fetchCalendarEvents,
    setMonth,
    nextMonth,
    prevMonth,
    goToToday,
    updateFilters,
    clearFilters,
    hasActiveFilters
  }
}