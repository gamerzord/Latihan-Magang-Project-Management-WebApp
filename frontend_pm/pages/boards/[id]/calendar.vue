<template>
  <div class="calendar-page">
    <CalendarHeader
      :current-month="currentMonth"
      @prev-month="prevMonth"
      @next-month="nextMonth"
      @today="goToToday"
    />

    <div class="calendar-toolbar">
      <CalendarMenu
        :filters="{
          ...filters,
          labelIds: filters.labelIds ? [...filters.labelIds] : filters.labelIds,
          memberIds: filters.memberIds ? [...filters.memberIds] : filters.memberIds
        }"
        :board="board"
        @update:filters="updateFilters"
      />
    </div>

    <CalendarGrid
      v-if="!loading"
      :events="getEventsForMonth"
      :current-month="currentMonth"
      @event-click="handleEventClick"
    />

    <div v-else class="d-flex justify-center pa-8">
      <v-progress-circular indeterminate color="primary" />
    </div>

    <CardModal
      v-if="uiStore.isCardModalOpen && uiStore.currentCardModalId"
      :card-id="uiStore.currentCardModalId"
      @close="uiStore.closeCardModal()"
    />
  </div>
</template>

<script setup lang="ts">
import type { CalendarEvent, CalendarFilter } from '~/types/models'

const route = useRoute()
const uiStore = useUiStore()
const boardStore = useBoardStore()

const boardId = computed(() => {
  const id = route.params.id
  const parsedId = typeof id === 'string' ? parseInt(id, 10) : NaN

  if (isNaN(parsedId) || parsedId <= 0) {
    return null
  }
  
  return parsedId
})

const board = computed(() => boardStore.currentBoard)

const {
  loading,
  currentMonth,
  filters,
  getEventsForMonth,
  fetchCalendarEvents,
  nextMonth,
  prevMonth,
  goToToday,
  updateFilters
} = useCalendar()

const fetchData = async () => {
  if (boardId.value !== null) {
    await boardStore.fetchBoard(boardId.value)
    await fetchCalendarEvents(boardId.value)
  }
}

useAutoRefresh(async () => {
  await fetchData()
})

watch(filters, async () => {
  if (boardId.value !== null) {
    await fetchCalendarEvents(boardId.value)
  }
}, { deep: true })

watch(boardId, (newId) => {
  if (newId !== null) {
    fetchData()
  }
})

watch(boardId, (newId) => {
  if (newId === null) {
    throw createError({
      statusCode: 404,
      statusMessage: 'Board not found'
    })
  }
})

watch(board, (newBoard) => {
  if (newBoard) {
    useSeoMeta({
      title: `${newBoard.title} - Calendar - Project Management`,
      ogTitle: `${newBoard.title} - Calendar - Project Management`
    })
  }
})

const handleEventClick = (event: CalendarEvent) => {
  uiStore.openCardModal(event.id)
}
</script>

<style scoped>
.calendar-page {
  padding: 16px;
  height: 100vh;
  display: flex;
  flex-direction: column;
  background-color: #f8f9fa;
}

.calendar-toolbar {
  margin: 16px 0;
  display: flex;
  align-items: center;
  gap: 16px;
}

@media (max-width: 768px) {
  .calendar-page {
    padding: 8px;
    height: 100vh;
  }

  .calendar-toolbar {
    margin: 12px 0;
    flex-wrap: wrap;
    gap: 8px;
  }
}

@media print {
  .calendar-page {
    height: auto;
    padding: 0;
  }
}
</style>