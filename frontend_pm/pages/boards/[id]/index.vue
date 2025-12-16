<template>
  <div 
    class="board-layout"
    :style="boardBackground"
  >
    <BoardHeader 
      v-if="board && canAccessBoard" 
      :board="board" 
      @refresh="fetchBoardData" 
      @scroll-to-card="scrollToCard"
      class="board-header-fixed" 
    />

    <div class="header-spacer"></div>

    <!-- Loading State -->
    <div v-if="boardStore.loading" class="d-flex align-center justify-center fill-height">
      <v-progress-circular indeterminate color="white" size="64" />
    </div>

    <!-- Access Denied State -->
    <div v-else-if="board && !canAccessBoard" class="access-denied">
      <v-card class="pa-6 text-center" max-width="500" variant="elevated">
        <v-icon size="64" color="warning" class="mb-4">mdi-lock-alert</v-icon>
        <h2 class="text-h5 mb-2">Access Restricted</h2>
        <p class="text-body-1 mb-4">
          This board is set to <strong>{{ board.visibility }}</strong> visibility.
          You need to be invited to access it.
        </p>
        
        <div v-if="board.creator" class="creator-info mb-4">
          <p class="text-body-2">Please contact the board creator:</p>
          <v-card variant="outlined" class="pa-3">
            <div class="d-flex align-center">
              <v-avatar color="primary" size="40" class="mr-3">
                <span class="text-caption font-weight-medium">
                  {{ getUserInitials(board.creator.name) }}
                </span>
              </v-avatar>
              <div class="text-left">
                <div class="text-body-2 font-weight-bold">{{ board.creator.name }}</div>
                <div class="text-caption text-grey">{{ board.creator.email }}</div>
              </div>
            </div>
          </v-card>
        </div>

        <div class="d-flex justify-center gap-2">
          <v-btn
            color="primary"
            @click="navigateTo(`/workspaces/${board.workspace_id}`)"
          >
            Back to Workspace
          </v-btn>
        </div>
      </v-card>
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
    <div v-else-if="board && canAccessBoard" ref="boardContent" class="board-content">
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
const userStore = useUserStore()
const workspaceStore = useWorkspaceStore()
const boardContent = ref<HTMLElement | null>(null)

const boardId = computed(() => {
  const id = route.params.id
  return typeof id === 'string' ? parseInt(id, 10) : NaN
})

const board = computed(() => boardStore.currentBoard)

const canAccessBoard = computed(() => {
  if (!board.value || !userStore.user) return false;

  const workspace = workspaceStore.currentWorkspace

  const workspaceMember = workspace?.members?.find(
    m => m.id === userStore.user?.id
  );

  if (workspaceMember?.pivot?.role === 'owner' || 
      workspaceMember?.pivot?.role === 'admin') {
    return true;
  }

  switch (board.value.visibility) {
    case 'public':
      return true;

    case 'workspace':
      const isWorkspaceMember = !!workspaceMember;
      const isBoardMember = board.value.members?.some(
        m => m.id === userStore.user?.id
      );
      return isWorkspaceMember && isBoardMember;

    case 'private':
      return board.value.members?.some(m => m.id === userStore.user?.id) || false;

    default:
      return false;
  }
});


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

const getUserInitials = (name: string): string => {
  return name
    .split(' ')
    .map(part => part.charAt(0).toUpperCase())
    .join('')
    .slice(0, 2)
}

const scrollToCard = (cardId: number) => {
  // Find the card element
  const cardElement = document.querySelector(`[data-card-id="${cardId}"]`)
  
  if (cardElement) {
    // Scroll the card into view
    cardElement.scrollIntoView({ 
      behavior: 'smooth', 
      block: 'center',
      inline: 'center'
    })
    
    // Add highlight animation
    cardElement.classList.add('card-highlight')
    
    // Remove highlight after animation
    setTimeout(() => {
      cardElement.classList.remove('card-highlight')
    }, 2500)
  }
}

const fetchBoardData = async () => {
  if (!isNaN(boardId.value)) {
    await boardStore.fetchBoard(boardId.value);

    if (board.value?.workspace_id) {
      await workspaceStore.fetchWorkspace(board.value.workspace_id);
    }
  }
};

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
.access-denied {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  padding: 20px;
}

.creator-info {
  max-width: 300px;
  margin: 0 auto;
}

.board-header-fixed {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  background: rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(10px);
}

.header-spacer {
  height: 64px;
}

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

/* Card highlight animation */
:deep(.card-highlight) {
  animation: cardPulse 2.5s ease-in-out;
  position: relative;
  z-index: 10;
}

@keyframes cardPulse {
  0%, 100% {
    box-shadow: 0 0 0 0 rgba(33, 150, 243, 0);
    transform: scale(1);
  }
  25% {
    box-shadow: 0 0 0 8px rgba(33, 150, 243, 0.4);
    transform: scale(1.02);
  }
  50% {
    box-shadow: 0 0 0 4px rgba(33, 150, 243, 0.2);
    transform: scale(1);
  }
  75% {
    box-shadow: 0 0 0 8px rgba(33, 150, 243, 0.4);
    transform: scale(1.02);
  }
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