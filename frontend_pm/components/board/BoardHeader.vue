<template>
  <v-app-bar flat color="rgba(0,0,0,0.3)" class="board-header">
    <v-btn
      icon="mdi-arrow-left"
      variant="text"
      color="white"
      @click="navigateTo(`/workspaces/${board.workspace_id}`)"
    />

    <!-- Board Title -->
    <v-text-field
      v-if="editing"
      v-model="boardTitle"
      density="compact"
      variant="plain"
      hide-details
      class="board-title-input"
      dark
      autofocus
      @blur="handleUpdateTitle"
      @keyup.enter="handleUpdateTitle"
      @keyup.esc="cancelEdit"
    />
    <v-btn
      v-else-if="canEditBoard"
      variant="text"
      color="white"
      class="text-h6 font-weight-bold text-white"
      @click="editing = true"
    >
      {{ board.title }}
    </v-btn>

    <div
      v-else
      class="text-h6 font-weight-bold text-white"
    >
      {{ board.title }}
    </div>

    <v-chip
      :prepend-icon="visibilityIcon"
      size="small"
      variant="outlined"
      color="white"
      class="ml-2"
    >
      {{ visibilityText }}
    </v-chip>

    <v-spacer />

    <!-- Search Button -->
    <v-menu
      v-model="searchOpen"
      :close-on-content-click="false"
      location="bottom"
      max-width="400"
    >
      <template #activator="{ props }">
        <v-btn
          v-bind="props"
          icon="mdi-magnify"
          variant="text"
          color="white"
          class="mr-1"
        />
      </template>
      
      <v-card class="search-card">
        <v-card-text class="pa-3">
          <v-text-field
            v-model="searchQuery"
            placeholder="Search cards..."
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
            density="compact"
            hide-details
            autofocus
            clearable
            @keyup.esc="searchOpen = false"
          />
          
          <!-- Search Results -->
          <div v-if="searchQuery.trim()" class="search-results mt-3">
            <div v-if="searchResults.length === 0" class="text-center py-4 text-grey">
              <v-icon size="48" class="mb-2">mdi-card-search-outline</v-icon>
              <div class="text-body-2">No cards found</div>
            </div>
            
            <v-list v-else density="compact" class="pa-0">
              <v-list-item
                v-for="result in searchResults"
                :key="result.card.id"
                class="search-result-item"
                @click="handleCardClick(result)"
              >
                <template #prepend>
                  <v-icon size="20" color="grey-darken-1">mdi-card-text-outline</v-icon>
                </template>
                
                <v-list-item-title class="text-body-2 font-weight-medium">
                  {{ result.card.title }}
                </v-list-item-title>
                
                <v-list-item-subtitle class="text-caption">
                  in {{ result.listName }}
                </v-list-item-subtitle>
                
                <!-- Labels if any -->
                <template #append>
                  <div v-if="result.card.labels && result.card.labels.length > 0" class="d-flex gap-1">
                    <v-chip
                      v-for="label in result.card.labels.slice(0, 2)"
                      :key="label.id"
                      :color="label.color"
                      size="x-small"
                      class="label-chip"
                    >
                      {{ label.name }}
                    </v-chip>
                    <v-chip
                      v-if="result.card.labels.length > 2"
                      size="x-small"
                      variant="text"
                    >
                      +{{ result.card.labels.length - 2 }}
                    </v-chip>
                  </div>
                </template>
              </v-list-item>
            </v-list>
          </div>
          
          <div v-else class="text-center py-6 text-grey-lighten-1">
            <v-icon size="40" class="mb-2">mdi-magnify</v-icon>
            <div class="text-body-2">Type to search cards</div>
            <div class="text-caption">Search by title, description, or labels</div>
          </div>
        </v-card-text>
      </v-card>
    </v-menu>

    <!-- Board Actions -->
    <v-btn
      v-if="canManageBoard"
      prepend-icon="mdi-account-plus"
      variant="text"
      color="white"
      @click="addMembersDialog = true"
      size="small"
      class="mr-1"
    >
      Add Members
    </v-btn>

    <v-btn
      prepend-icon="mdi-account-multiple"
      variant="text"
      color="white"
      @click="membersDialog = true"
      size="small"
    >
      Members
      <span class="ml-1">({{ board.members?.length || 0 }})</span>
    </v-btn>

    <v-menu>
      <template #activator="{ props }">
        <v-btn
          v-bind="props"
          icon="mdi-dots-horizontal"
          variant="text"
          color="white"
        />
      </template>
      
      <v-list density="compact">
        <v-list-item v-if="canManageBoard"  @click="settingsDialog = true">
          <template #prepend>
            <v-icon>mdi-cog</v-icon>
          </template>
          <v-list-item-title>Settings</v-list-item-title>
        </v-list-item>
        
        <v-list-item @click="calendarView">
          <template #prepend>
            <v-icon>mdi-calendar</v-icon>
          </template>
          <v-list-item-title>Calendar View</v-list-item-title>
        </v-list-item>

        <v-list-item @click="backgroundDialog = true">
          <template #prepend>
            <v-icon>mdi-image</v-icon>
          </template>
          <v-list-item-title>Change Background</v-list-item-title>
        </v-list-item>

        <v-list-item @click="labelsDialog = true">
          <template #prepend>
            <v-icon>mdi-label</v-icon>
          </template>
          <v-list-item-title>Manage Labels</v-list-item-title>
        </v-list-item>

        <v-divider />

        <v-list-item
          v-if="!isBoardOwner"
          @click="handleLeaveBoard"
        >
          <template #prepend>
            <v-icon color="warning">mdi-exit-to-app</v-icon>
          </template>
          <v-list-item-title class="text-warning">Leave Board</v-list-item-title>
        </v-list-item>

        <v-list-item v-if="canManageBoard" @click="handleArchive">
          <template #prepend>
            <v-icon>mdi-archive</v-icon>
          </template>
          <v-list-item-title>Archive Board</v-list-item-title>
        </v-list-item>

        <v-list-item
          v-if="isBoardOwner"
          @click="handleDeleteBoard"
        >
          <template #prepend>
            <v-icon color="error">mdi-delete</v-icon>
          </template>
          <v-list-item-title class="text-error">Delete Board</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-menu>

    <v-dialog v-model="addMembersDialog" max-width="600">
      <CommonMemberSelector
        v-if="board"
        context="board"
        :board-id="board.id"
        :current-members="[...(board.members || [])]"
        :visibility="board.visibility"
        @close="addMembersDialog = false"
        @members-added="handleMembersAdded"
        @refresh="$emit('refresh')"
      />
    </v-dialog>

    <!-- Members Dialog -->
    <v-dialog v-model="membersDialog" max-width="500">
      <v-card>
        <v-card-title>{{ canManageBoard ? 'Manage Members' : 'Board Members' }}</v-card-title>
        
        <v-card-text>
          <v-list>
            <v-list-item
              v-for="member in board.members || []"
              :key="member.id"
            >
              <template #prepend>
                <v-avatar color="primary" size="40">
                  <v-img v-if="member.avatar_url" :src="member.avatar_url" />
                  <span v-else class="text-caption font-weight-medium">{{ getUserInitials(member.name || '') }}</span>
                </v-avatar>
              </template>

              <v-list-item-title>{{ member.name }}</v-list-item-title>
              <v-list-item-subtitle>
                <v-chip size="x-small" :color="getRoleColor(member.pivot.role)">
                  {{ member.pivot.role }}
                </v-chip>
                <span v-if="member.id === board.creator?.id" class="ml-1">👑</span>
                <span v-if="member.id === userStore.user?.id" class="ml-1">(You)</span>
              </v-list-item-subtitle>

              <template #append>
                <v-btn
                  v-if="canManageBoard && member.id !== board.creator?.id && member.id !== userStore.user?.id"
                  icon="mdi-close"
                  variant="text"
                  size="small"
                  @click.stop="handleRemoveMember(member.pivot.user_id)"
                />
              </template>
            </v-list-item>
          </v-list>
        </v-card-text>
        
        <v-card-actions>
          <v-spacer />
          <v-btn @click="membersDialog = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Background Dialog -->
    <v-dialog v-model="backgroundDialog" max-width="500">
      <v-card>
        <v-card-title>Change Background</v-card-title>
        
        <v-card-text>
          <div class="background-colors">
            <div
              v-for="bg in BOARD_BACKGROUNDS.COLORS"
              :key="bg.value"
              class="background-option"
              :class="{ selected: board.background_value === bg.value }"
              :style="{ backgroundColor: bg.value }"
              @click="handleChangeBackground(bg.value)"
            />
          </div>
        </v-card-text>
        
        <v-card-actions>
          <v-spacer />
          <v-btn @click="backgroundDialog = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Labels Dialog -->
    <v-dialog v-model="labelsDialog" max-width="500">
      <CommonLabelManager
        :board-id="board.id"
        @close="labelsDialog = false"
      />
    </v-dialog>

    <!-- Settings Dialog -->
    <v-dialog v-model="settingsDialog" max-width="500">
      <v-card>
        <v-card-title>Board Settings</v-card-title>
        
        <v-card-text>
          <v-text-field
            v-model="settingsForm.title"
            label="Board Title"
            variant="outlined"
            class="mb-3"
          />

          <v-textarea
            v-model="settingsForm.description"
            label="Description"
            variant="outlined"
            rows="3"
            class="mb-3"
          />

          <v-select
            v-model="settingsForm.visibility"
            :items="VISIBILITY_OPTIONS"
            item-title="label"
            item-value="value"
            label="Visibility"
            variant="outlined"
            :hint="getVisibilityHint(settingsForm.visibility)"
            persistent-hint
          />
        </v-card-text>
        
        <v-card-actions>
          <v-spacer />
          <v-btn @click="settingsDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="handleSaveSettings">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-app-bar>
</template>

<script setup lang="ts">
import type { Board, Card } from '~/types/models'

interface Props {
  board: Board
}

interface Emits {
  (event: 'refresh'): void
  (event: 'scrollToCard', cardId: number): void
}

interface SearchResult {
  card: Card
  listName: string
  listId: number
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const boardStore = useBoardStore()
const uiStore = useUiStore()
const userStore = useUserStore()

const editing = ref(false)
const boardTitle = ref(props.board.title)
const membersDialog = ref(false)
const backgroundDialog = ref(false)
const labelsDialog = ref(false)
const settingsDialog = ref(false)
const addMembersDialog = ref(false)
const searchOpen = ref(false)
const searchQuery = ref('')

const settingsForm = reactive({
  title: props.board.title,
  description: props.board.description || '',
  visibility: props.board.visibility
})

const BOARD_BACKGROUNDS = {
  COLORS: [
    { value: '#0079bf', name: 'Blue' },
    { value: '#d29034', name: 'Orange' },
    { value: '#519839', name: 'Green' },
    { value: '#b04632', name: 'Red' },
    { value: '#89609e', name: 'Purple' },
    { value: '#cd5a91', name: 'Pink' },
    { value: '#4bbf6b', name: 'Lime' },
    { value: '#00aecc', name: 'Sky' },
    { value: '#838c91', name: 'Grey' }
  ]
}

const VISIBILITY_OPTIONS = [
  { label: 'Private', value: 'private' },
  { label: 'Workspace', value: 'workspace' },
  { label: 'Public', value: 'public' }
]

const searchResults = computed(() => {
  if (!searchQuery.value.trim() || !props.board.lists) return []
  
  const query = searchQuery.value.toLowerCase().trim()
  const results: SearchResult[] = []
  
  props.board.lists.forEach(list => {
    if (!list.cards || list.archived) return
    
    list.cards
      .filter(card => !card.archived)
      .forEach(card => {
        const titleMatch = card.title.toLowerCase().includes(query)
        const descMatch = card.description?.toLowerCase().includes(query)
        const labelMatch = card.labels?.some(label => 
          label.name.toLowerCase().includes(query)
        )
        
        if (titleMatch || descMatch || labelMatch) {
          results.push({
            card,
            listName: list.title,
            listId: list.id
          })
        }
      })
  })
  
  return results.slice(0, 10) // Limit to 10 results
})

const currentUserRole = computed(() => {
  if (!props.board || !userStore.user) return null
  const member = props.board.members?.find(m => m.id === userStore.user?.id)
  return member?.pivot?.role || null
})

const isBoardOwner = computed(() => {
  return props.board?.creator?.id === userStore.user?.id
})

const isBoardAdmin = computed(() => {
  return currentUserRole.value === 'admin' || isBoardOwner.value
})

const canManageBoard = computed(() => {
  return isBoardAdmin.value
})

const canEditBoard = computed(() => {
  return isBoardAdmin.value
})

const visibilityIcon = computed(() => {
  const icons = {
    private: 'mdi-lock',
    workspace: 'mdi-account-group',
    public: 'mdi-earth'
  }
  return icons[props.board.visibility]
})

const visibilityText = computed(() => {
  const texts = {
    private: 'Private',
    workspace: 'Workspace',
    public: 'Public'
  }
  return texts[props.board.visibility]
})

const getVisibilityHint = (visibility: string) => {
  switch (visibility) {
    case 'private': return 'Only invited members can access'
    case 'workspace': return 'Visible to workspace members, requires invitation to access'
    case 'public': return 'Visible to everyone'
    default: return ''
  }
}

const getRoleColor = (role: string) => {
  switch (role) {
    case 'owner': return 'amber'
    case 'admin': return 'blue'
    case 'member': return 'grey'
    default: return 'grey-lighten-1'
  }
}

const getUserInitials = (name: string): string => {
  return name
    .split(' ')
    .map(part => part.charAt(0).toUpperCase())
    .join('')
    .slice(0, 2)
}

const handleCardClick = (result: SearchResult) => {
  searchOpen.value = false
  searchQuery.value = ''
  
  // Emit event to scroll to card and highlight it
  emit('scrollToCard', result.card.id)
  
  // Optionally open the card modal after a brief delay
  setTimeout(() => {
    uiStore.openCardModal(result.card.id)
  }, 600)
}

const handleUpdateTitle = async () => {
  if (!boardTitle.value.trim()) {
    boardTitle.value = props.board.title
    editing.value = false
    return
  }

  if (boardTitle.value === props.board.title) {
    editing.value = false
    return
  }

  try {
    await boardStore.updateBoard(props.board.id, { title: boardTitle.value.trim() })
    editing.value = false
    uiStore.showSnackbar('Board title updated', 'success')
  } catch (error) {
    uiStore.showSnackbar('Failed to update title', 'error')
    boardTitle.value = props.board.title
    editing.value = false
  }
}

const cancelEdit = () => {
  boardTitle.value = props.board.title
  editing.value = false
}

const handleChangeBackground = async (color: string) => {
  try {
    await boardStore.updateBoard(props.board.id, {
      background_type: 'color',
      background_value: color
    })
    backgroundDialog.value = false
    uiStore.showSnackbar('Background updated', 'success')
  } catch (error) {
    uiStore.showSnackbar('Failed to update background', 'error')
  }
}

const handleSaveSettings = async () => {
  if (!settingsForm.title.trim()) return

  try {
    await boardStore.updateBoard(props.board.id, settingsForm)
    settingsDialog.value = false
    uiStore.showSnackbar('Settings saved', 'success')
  } catch (error) {
    uiStore.showSnackbar('Failed to save settings', 'error')
  }
}

const handleArchive = async () => {
  if (!confirm('Are you sure you want to archive this board? You can restore it later from the workspace.')) return

  try {
    await boardStore.updateBoard(props.board.id, { archived: true })
    uiStore.showSnackbar('Board archived', 'success')
    navigateTo(`/workspaces/${props.board.workspace_id}`)
  } catch (error) {
    uiStore.showSnackbar('Failed to archive board', 'error')
  }
}

const handleDeleteBoard = async () => {
  if (!confirm('Are you sure you want to delete this board and all its lists/cards? This action cannot be undone.')) return

  try {
    await boardStore.deleteBoard(props.board.id)
    uiStore.showSnackbar('Board deleted', 'success')
    navigateTo(`/workspaces/${props.board.workspace_id}`)
  } catch (error) {
    uiStore.showSnackbar('Failed to delete board', 'error')
  }
}

const handleLeaveBoard = async () => {
  if (!confirm(`Are you sure you want to leave "${props.board.title}"?`)) return
  
  try {
    await boardStore.leaveBoard(props.board.id)
    uiStore.showSnackbar(`Left ${props.board.title}`, 'success')
    navigateTo(`/workspaces/${props.board.workspace_id}`)
  } catch (error) {
    uiStore.showSnackbar('Failed to leave board', 'error')
  }
}

const handleRemoveMember = async (userId: number) => {
  if (!confirm('Are you sure you want to remove this member from the board?')) return
  
  try {
    await boardStore.removeMember(props.board.id, userId)
    uiStore.showSnackbar('Member removed', 'success')
    emit('refresh')
  } catch (error) {
    uiStore.showSnackbar('Failed to remove member', 'error')
  }
}

const handleMembersAdded = (newMembers: any[]) => {
  uiStore.showSnackbar(`Added ${newMembers.length} member(s) to the board`, 'success')
  emit('refresh')
}

const calendarView = () => {
  navigateTo(`/boards/${props.board.id}/calendar`)
}

watch(() => props.board, (newBoard) => {
  boardTitle.value = newBoard.title
  settingsForm.title = newBoard.title
  settingsForm.description = newBoard.description || ''
  settingsForm.visibility = newBoard.visibility
})

watch(settingsDialog, (open) => {
  if (!open) {
    settingsForm.title = props.board.title
    settingsForm.description = props.board.description || ''
    settingsForm.visibility = props.board.visibility
  }
})
</script>

<style scoped>
.board-header {
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.board-title-input :deep(.v-field__input) {
  color: white !important;
  font-size: 1.25rem;
  font-weight: 600;
}

.board-title-input :deep(.v-field__field) {
  padding: 0 8px;
}

.v-btn.text-white,
.text-white {
  color: white !important;
}

.search-card {
  min-width: 400px;
  max-height: 500px;
  overflow-y: auto;
}

.search-results {
  max-height: 350px;
  overflow-y: auto;
}

.search-result-item {
  border-radius: 8px;
  margin-bottom: 4px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.search-result-item:hover {
  background-color: rgba(0, 0, 0, 0.04);
}

.label-chip {
  font-size: 10px !important;
  height: 18px !important;
}

.background-colors {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.background-option {
  height: 80px;
  border-radius: 8px;
  cursor: pointer;
  border: 3px solid transparent;
  transition: all 0.2s ease;
}

.background-option:hover {
  opacity: 0.8;
  transform: scale(1.05);
}

.background-option.selected {
  border-color: #0079bf;
  box-shadow: 0 0 0 2px white, 0 0 0 4px #0079bf;
}

@media (max-width: 768px) {
  .board-header {
    padding: 0 8px;
  }

  .search-card {
    min-width: 300px;
  }

  .background-colors {
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
  }

  .background-option {
    height: 60px;
  }
}
</style>