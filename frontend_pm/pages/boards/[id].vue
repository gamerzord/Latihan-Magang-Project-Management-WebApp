<template>
  <div 
    class="board-layout"
    :style="boardBackground"
  >
    <BoardHeader v-if="board" :board="board" />

    <!-- Loading State -->
    <div v-if="boardStore.loading" class="d-flex align-center justify-center fill-height">
      <v-progress-circular indeterminate color="white" size="64" />
    </div>

    <!-- Error State -->
    <div v-else-if="boardStore.error" class="d-flex align-center justify-center fill-height">
      <v-card variant="elevated" class="pa-4">
        <v-alert type="error" title="Error Loading Board">
          {{ boardStore.error }}
        </v-alert>
        <v-btn color="primary" @click="fetchBoardData" class="mt-4">
          Retry
        </v-btn>
      </v-card>
    </div>

    <!-- Board Content -->
    <div v-else-if="board" class="board-content">
      <div class="lists-container">
        <ListContainer
          v-for="list in activeLists"
          :key="list.id"
          :list="list"
        />

        <ListAddList :board-id="board.id" />
      </div>
    </div>

    <!-- Card Modal -->
    <CardModal
      v-if="uiStore.isCardModalOpen && uiStore.currentCardModalId"
      :card-id="uiStore.currentCardModalId"
      @close="uiStore.closeCardModal()"
    />
  </div>
</template>

<script setup lang="ts">
import type { List } from '~/types/models'

const route = useRoute()
const uiStore = useUiStore()
const boardStore = useBoardStore()

const boardId = computed(() => {
  const id = route.params.id
  return typeof id === 'string' ? parseInt(id, 10) : NaN
})

const board = computed(() => boardStore.currentBoard)

const activeLists = computed(() => {
  return boardStore.currentBoardLists.filter((list: List) => !list.archived)
})

const boardBackground = computed(() => {
  if (!board.value) return {}
  
  if (board.value.background_type === 'color') {
    return {
      background: board.value.background_value,
      backgroundSize: 'cover'
    }
  } else if (board.value.background_type === 'image' && board.value.background_value) {
    return {
      background: `url(${board.value.background_value})`,
      backgroundSize: 'cover',
      backgroundPosition: 'center',
      backgroundRepeat: 'no-repeat'
    }
  }
  
  return {
    background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
  }
})

const fetchBoardData = async () => {
  if (!isNaN(boardId.value)) {
    await boardStore.fetchBoard(boardId.value)
  }
}

useAutoRefresh(async () => {
  if (!isNaN(boardId.value)) {
    await fetchBoardData()
  }
})

watch(board, (newBoard) => {
  if (newBoard) {
    useSeoMeta({
      title: `${newBoard.title} - Project Management`,
      ogTitle: `${newBoard.title} - Project Management`
    })
  }
})

watch(boardId, (newId) => {
  if (isNaN(newId)) {
    throw createError({
      statusCode: 404,
      statusMessage: 'Board not found'
    })
  }
})

watch(boardId, (newId) => {
  if (!isNaN(newId)) {
    fetchBoardData()
  }
}, { immediate: true })
</script>

<style scoped>
.board-layout {
  height: 100vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  position: relative;
}

.board-layout::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.1);
  z-index: 0;
}

.board-content {
  flex: 1;
  overflow-x: auto;
  overflow-y: hidden;
  padding: 8px;
  position: relative;
  z-index: 1;
}

.lists-container {
  display: flex;
  gap: 12px;
  height: 100%;
  padding-bottom: 8px;
  align-items: flex-start;
}

.fill-height {
  height: 100%;
}

.lists-container::-webkit-scrollbar {
  height: 12px;
}

.lists-container::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 6px;
}

.lists-container::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.3);
  border-radius: 6px;
}

.lists-container::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.5);
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .board-content {
    padding: 4px;
  }
  
  .lists-container {
    gap: 8px;
    padding-bottom: 4px;
  }
}

/* Print styles */
@media print {
  .board-layout {
    height: auto;
    overflow: visible;
  }
  
  .board-content {
    overflow: visible;
  }
  
  .lists-container {
    flex-wrap: wrap;
    height: auto;
  }
}
</style>