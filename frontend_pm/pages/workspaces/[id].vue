<template>
  <v-container fluid class="pa-4">
    <div v-if="workspaceStore.loading" class="d-flex justify-center pa-8">
      <v-progress-circular indeterminate color="primary" size="64" />
    </div>

    <div v-else-if="workspace">
      <!-- Workspace Header -->
      <div class="d-flex align-center justify-space-between mb-6">
        <div>
          <h1 class="text-h4 mb-2">{{ workspace.name }}</h1>
          <p v-if="workspace.description" class="text-body-1 text-grey">
            {{ workspace.description }}
          </p>
          <v-chip size="small" :color="getVisibilityColor(workspace.visibility)" class="mt-1">
            <v-icon start size="small">
              {{  getVisibilityIcon(workspace.visibility)  }}
            </v-icon>
              {{ getVisibilityLabel(workspace.visibility) }}
          </v-chip>
        </div>

        <div class="d-flex align-center" style="gap: 10px; height: 40px;">
          <v-btn
            color="primary"
            prepend-icon="mdi-plus"
            @click="boardDialog = true"
          >
            Create Board
          </v-btn>

          <v-btn
            v-if="canManageMembers"
            color="secondary"
            prepend-icon="mdi-account-plus"
            @click="addMembersDialog = true"
          >
            Add Members
          </v-btn>

          <v-menu v-if="canManageWorkspace">
            <template #activator="{ props }">
              <v-btn v-bind="props" icon="mdi-dots-vertical" />
            </template>
            
            <v-list>
              <v-list-item v-if="canManageWorkspace" @click="editDialog = true">
                <v-list-item-title>Edit Workspace</v-list-item-title>
              </v-list-item>
              <v-list-item @click="membersDialog = true">
                <v-list-item-title> {{ canManageMembers ? 'Manage Members' : 'View Members' }} </v-list-item-title>
              </v-list-item>
              <v-list-item  v-if="isWorkspaceOwner && canDeleteWorkspace" @click="handleDelete">
                <v-list-item-title class="text-error">Delete Workspace</v-list-item-title>
              </v-list-item>
              <v-list-item v-if="!isWorkspaceOwner && userStore.isAuthenticated" @click="handleLeaveWorkspace">
                <v-list-item-title class="text-error">Leave Workspace</v-list-item-title>
              </v-list-item>
            </v-list>
          </v-menu>
        </div>
      </div>

      <!-- Boards Grid -->
      <div v-if="visibleBoards.length" class="boards-grid">
        <v-card
          v-for="board in visibleBoards"
          :key="board.id"
          class="board-card"
          hover
          @click="navigateTo(`/boards/${board.id}`)"
        >
          <div
            class="board-card-cover"
            :style="{
              background: board.background_type === 'color'
                ? board.background_value
                : `url(${board.background_value})`,
              backgroundSize: 'cover'
            }"
          >
            <div class="board-card-overlay">
              <h3 class="text-h6 text-white font-weight-bold">
                {{ board.title }}
              </h3>
              <div class="visibility-badge">
                <v-chip size="small" :color="getVisibilityColor(board.visibility)" class="mt-1">
                  <v-icon start size="x-small">
                    {{  getVisibilityIcon(board.visibility)  }}
                  </v-icon>
                    {{ getVisibilityLabel(board.visibility) }}
                </v-chip>
              </div>
            </div>
          </div>
        </v-card>

        <!-- Create Board Card -->
        <v-card
          class="board-card create-board-card"
          @click="boardDialog = true"
        >
          <div class="d-flex flex-column align-center justify-center fill-height">
            <v-icon size="48" color="grey">mdi-plus</v-icon>
            <span class="text-body-1 text-grey mt-2">Create new board</span>
          </div>
        </v-card>
      </div>

      <div v-else-if="workspace?.boards?.length && visibleBoards.length === 0" class="empty-state">
        <v-card variant="outlined" class="pa-8 text-center" max-width="400">
          <v-icon size="72" color="grey-lighten-1" class="mb-4">mdi-lock</v-icon>
          <h3 class="text-h5 mb-2">No accessible boards</h3>
          <p class="text-body-2 text-grey mb-6">
            You don't have access to any boards in this workspace yet.
            <span v-if="canManageWorkspace">Create a board or ask to be invited to existing ones.</span>
            <span v-else>Ask a workspace admin to invite you to boards.</span>
          </p>
          <v-btn 
            v-if="canManageWorkspace"
            color="primary" 
            size="large"
            prepend-icon="mdi-plus"
            @click="boardDialog = true"
          >
            Create First Board
          </v-btn>
        </v-card>
      </div>

      <v-alert v-else type="info" variant="tonal" class="mb-4">
        No boards yet. Create your first board to get started!
      </v-alert>
    </div>

    <!-- Create Board Dialog -->
    <v-dialog v-model="boardDialog" max-width="500">
      <v-card>
        <v-card-title>Create Board</v-card-title>
        
        <v-card-text>
          <v-text-field
            v-model="newBoard.title"
            label="Board Title"
            variant="outlined"
            class="mb-3"
          />

          <v-textarea
            v-model="newBoard.description"
            label="Description (optional)"
            variant="outlined"
            rows="3"
            class="mb-3"
          />

          <div class="mb-3">
            <label class="text-subtitle-2 mb-2 d-block">Background</label>
            <div class="d-flex gap-2 flex-wrap">
              <div
                v-for="bg in BOARD_BACKGROUNDS.COLORS"
                :key="bg.value"
                class="color-option"
                :class="{ selected: newBoard.background_value === bg.value }"
                :style="{ backgroundColor: bg.value }"
                @click="selectBackground(bg.value)"
              />
            </div>
          </div>

          <v-select
            v-model="newBoard.visibility"
            :items="VISIBILITY_OPTIONS"
            item-title="label"
            item-value="value"
            label="Visibility"
            variant="outlined"
          />
        </v-card-text>
        
        <v-card-actions>
          <v-spacer />
          <v-btn @click="boardDialog = false">Cancel</v-btn>
          <v-btn color="primary" :loading="creating" @click="handleCreateBoard">
            Create
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Edit Workspace Dialog -->
    <v-dialog v-model="editDialog" max-width="500">
      <v-card>
        <v-card-title>Edit Workspace</v-card-title>
        
        <v-card-text>
          <v-text-field
            v-model="editForm.name"
            label="Workspace Name"
            variant="outlined"
            class="mb-3"
          />

          <v-textarea
            v-model="editForm.description"
            label="Description"
            variant="outlined"
            rows="3"
            class="mb-3"
          />

          <v-select
            v-model="editForm.visibility"
            :items="VISIBILITY_OPTIONS"
            item-title="label"
            item-value="value"
            label="Visibility"
            variant="outlined"
          />
        </v-card-text>
        
        <v-card-actions>
          <v-spacer />
          <v-btn @click="editDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="handleEdit">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="addMembersDialog" max-width="600">
      <CommonMemberSelector
        v-if="workspace"
        :workspace-id="workspaceId"
        :current-members="[...(workspace.members || [])]"
        @close="addMembersDialog = false"
        @members-added="handleMembersAdded"
        @refresh="fetchWorkspaceData"
      />
    </v-dialog>

    <!-- Members Dialog -->
    <v-dialog v-model="membersDialog" max-width="600">
      <v-card>
        <v-card-title>{{ canManageMembers ? 'Manage Members' : 'Workspace Members' }}</v-card-title>
        
        <v-card-text>
          <v-list>
            <v-list-item
              v-for="member in workspace?.members"
              :key="member.id"
            >
              <template #prepend>
                <v-avatar color="primary">
                  {{ getUserInitials(member.name || '') }}
                </v-avatar>
              </template>

              <v-list-item-title>{{ member.name }}</v-list-item-title>
              <v-list-item-subtitle>
                <v-chip size="x-small" :color="getRoleColor(member.pivot.role)">
                  {{ member.pivot.role }}
                </v-chip>
                <span v-if="member.pivot.role === 'owner'" class="ml-1">👑</span>
              </v-list-item-subtitle>

              <template #append>
                <v-btn
                  v-if="canManageMembers && member.pivot.role !== 'owner' && member.id !== userStore.user?.id"
                  icon="mdi-close"
                  size="small"
                  variant="text"
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
  </v-container>
</template>

<script setup lang="ts">
const route = useRoute()
const uiStore = useUiStore()
const workspaceStore = useWorkspaceStore()
const boardStore = useBoardStore()
const userStore = useUserStore()

const workspaceId = computed(() => {
  const id = route.params.id
  return typeof id === 'string' ? parseInt(id, 10) : NaN
})

const workspace = computed(() => workspaceStore.currentWorkspace)

const boardDialog = ref(false)
const editDialog = ref(false)
const membersDialog = ref(false)
const creating = ref(false)
const addMembersDialog = ref(false)

const currentUserRole = computed(() => {
  if (!workspace.value || !userStore.user) return null
  const member = workspace.value.members?.find(m => m.id === userStore.user?.id)
  return member?.pivot?.role || null
})

const isWorkspaceOwner = computed(() => {
  return currentUserRole.value === 'owner'
})

const isWorkspaceAdmin = computed(() => {
  return currentUserRole.value === 'admin' || isWorkspaceOwner.value
})

const canManageWorkspace = computed(() => {
  return isWorkspaceAdmin.value
})

const canManageMembers = computed(() => {
  return isWorkspaceAdmin.value
})

const canDeleteWorkspace = computed(() => {
  return isWorkspaceOwner.value
})

const getVisibilityColor = (visibility: string) => {
  switch (visibility) {
    case 'private': return 'error'
    case 'workspace': return 'warning'
    case 'public': return 'success'
    default: return 'grey'
  }
}

const getVisibilityIcon = (visibility: string) => {
  switch (visibility) {
    case 'private': return 'mdi-lock'
    case 'workspace': return 'mdi-account-group'
    case 'public': return 'mdi-earth'
    default: return 'mdi-eye'
  }
}

const getVisibilityLabel = (visibility: string) => {
  switch (visibility) {
    case 'private': return 'Private'
    case 'workspace': return 'Workspace'
    case 'public': return 'Public'
    default: return visibility
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

const handleLeaveWorkspace = async () => {
  if (!confirm(`Are you sure you want to leave "${workspace.value?.name}"?`)) return
  
  try {
    await workspaceStore.leaveWorkspace(workspaceId.value)
    uiStore.showSnackbar(`Left ${workspace.value?.name}`, 'success')
    navigateTo('/')
  } catch (error) {
    uiStore.showSnackbar('Failed to leave workspace', 'error')
  }
}

const newBoard = reactive({
  title: '',
  description: '',
  background_type: 'color' as 'color' | 'image',
  background_value: '#0079bf',
  visibility: 'workspace' as 'private' | 'workspace' | 'public'
})

const editForm = reactive({
  name: '',
  description: '',
  visibility: 'private' as 'private' | 'workspace' | 'public'
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

const selectBackground = (color: string) => {
  newBoard.background_type = 'color'
  newBoard.background_value = color
}

const getUserInitials = (name: string): string => {
  return name
    .split(' ')
    .map(part => part.charAt(0).toUpperCase())
    .join('')
    .slice(0, 2)
}

const handleCreateBoard = async () => {
  if (!newBoard.title.trim()) return
  
  creating.value = true
  try {
    const board = await boardStore.createBoard({
      workspace_id: workspaceId.value,
      ...newBoard
    })
    boardDialog.value = false
    uiStore.showSnackbar('Board created!', 'success')
    navigateTo(`/boards/${board.id}`)
  } catch (error) {
    uiStore.showSnackbar('Failed to create board', 'error')
  } finally {
    creating.value = false
  }
}

const visibleBoards = computed(() => {
  if (!workspace.value?.boards) return []
  
  return workspace.value.boards.filter(board => {
    if (isWorkspaceAdmin.value) return true
    
    switch (board.visibility) {
      case 'public':
        return true
        
      case 'workspace':
        return true
        
      case 'private':
        const isBoardMember = board.members?.some(m => m.id === userStore.user?.id)
        return isBoardMember || false
        
      default:
        return false
    }
  })
})

const handleMembersAdded = (newMembers: any[]) => {
  console.log('New members added:', newMembers)
}

const handleEdit = async () => {
  if (!editForm.name.trim()) return
  
  try {
    await workspaceStore.updateWorkspace(workspaceId.value, editForm)
    editDialog.value = false
    uiStore.showSnackbar('Workspace updated!', 'success')
  } catch (error) {
    uiStore.showSnackbar('Failed to update workspace', 'error')
  }
}

const handleDelete = async () => {
  if (!confirm('Are you sure you want to delete this workspace and all its boards? This action cannot be undone.')) return
  
  try {
    await workspaceStore.deleteWorkspace(workspaceId.value)
    uiStore.showSnackbar('Workspace deleted', 'success')
    navigateTo('/')
  } catch (error) {
    uiStore.showSnackbar('Failed to delete workspace', 'error')
  }
}

const handleRemoveMember = async (userId: number) => {
  if (!confirm('Are you sure you want to remove this member from the workspace?')) return
  
  try {
    await workspaceStore.removeMember(workspaceId.value, userId)
    uiStore.showSnackbar('Member removed', 'success')
  } catch (error) {
    uiStore.showSnackbar('Failed to remove member', 'error')
  }
}

const fetchWorkspaceData = async () => {
  if (!isNaN(workspaceId.value)) {
    await workspaceStore.fetchWorkspace(workspaceId.value)
  }
}

useAutoRefresh(async () => {
  await fetchWorkspaceData()
})

watch(workspaceId, (newId) => {
  if (!isNaN(newId)) {
    fetchWorkspaceData()
  }
}, { immediate: true })

watch(workspaceId, (newId) => {
  if (isNaN(newId)) {
    throw createError({
      statusCode: 404,
      statusMessage: 'Workspace not found'
    })
  }
})

watch(workspace, (newWorkspace) => {
  if (newWorkspace) {
    editForm.name = newWorkspace.name
    editForm.description = newWorkspace.description || ''
    editForm.visibility = newWorkspace.visibility
    
    useSeoMeta({
      title: `${newWorkspace.name} - Project Management`,
      ogTitle: `${newWorkspace.name} - Project Management`
    })
  }
})

watch(boardDialog, (open) => {
  if (!open) {
    newBoard.title = ''
    newBoard.description = ''
    newBoard.background_value = '#0079bf'
    newBoard.visibility = 'workspace'
  }
})
</script>

<style scoped>
.empty-state {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 300px;
}

.visibility-badge {
  position: absolute;
  top: 8px;
  right: 8px;
} 

.v-chip--size-x-small {
  height: 18px;
  font-size: 10px;
}

.boards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 16px;
}

.board-card {
  height: 120px;
  cursor: pointer;
  transition: transform 0.2s;
}

.board-card:hover {
  transform: translateY(-4px);
}

.board-card-cover {
  height: 100%;
  position: relative;
  border-radius: 4px;
}

.board-card-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, transparent, rgba(0,0,0,0.4));
  display: flex;
  align-items: flex-end;
  padding: 12px;
  border-radius: 4px;
}

.create-board-card {
  background: #f4f5f7;
  border: 2px dashed #dfe1e6;
}

.color-option {
  width: 50px;
  height: 40px;
  border-radius: 4px;
  cursor: pointer;
  border: 3px solid transparent;
  transition: all 0.2s;
}

.color-option:hover {
  opacity: 0.8;
}

.color-option.selected {
  border-color: #0079bf;
  box-shadow: 0 0 0 2px white;
}

.gap-2 {
  gap: 8px;
}

.fill-height {
  height: 100%;
}

@media (max-width: 768px) {
  .boards-grid {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
  }
  
  .board-card {
    height: 100px;
  }
}
</style>