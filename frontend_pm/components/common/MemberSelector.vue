<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      <v-icon class="mr-2">mdi-account-multiple-plus</v-icon>
      Add Members
      <v-spacer />
      <v-btn
        icon="mdi-close"
        variant="text"
        size="small"
        @click="$emit('close')"
      />
    </v-card-title>

    <v-card-text class="pa-4">
      <v-text-field
        v-model="search"
        placeholder="Search members by name or email..."
        prepend-inner-icon="mdi-magnify"
        variant="outlined"
        density="compact"
        hide-details
        class="mb-4"
        @input="handleSearch"
      />

      <div v-if="loading" class="text-center py-4">
        <v-progress-circular indeterminate color="primary" size="24" />
        <p class="text-caption text-grey mt-2">Loading members...</p>
      </div>

      <div v-else-if="error" class="text-center py-4">
        <v-alert type="error" variant="tonal" density="compact">
          {{ error }}
        </v-alert>
        <v-btn color="primary" variant="text" @click="loadAvailableMembers" class="mt-2">
          Retry
        </v-btn>
      </div>

      <v-list v-else class="member-list">
        <v-list-item
          v-for="user in filteredUsers"
          :key="user.id"
          class="member-item"
          :class="{ 'member-item--selected': isMember(user.id) }"
          @click="handleToggleMember(user)"
        >
          <template #prepend>
            <v-avatar color="primary" size="40">
              <v-img v-if="user.avatar_url" :src="user.avatar_url" />
              <span v-else class="text-caption font-weight-medium">
                {{ getUserInitials(user.name) }}
              </span>
            </v-avatar>
          </template>

          <v-list-item-title class="text-body-2 font-weight-medium">
            {{ user.name }}
          </v-list-item-title>
          <v-list-item-subtitle class="text-caption">
            {{ user.email }}
          </v-list-item-subtitle>

          <template #append>
            <v-icon 
              v-if="isMember(user.id)" 
              color="success"
              size="small"
            >
              mdi-check-circle
            </v-icon>
            <v-icon 
              v-else 
              color="grey"
              size="small"
            >
              mdi-account-plus
            </v-icon>
          </template>
        </v-list-item>

        <div v-if="!filteredUsers.length && search" class="text-center py-4">
          <v-icon size="48" color="grey-lighten-2" class="mb-2">mdi-account-search</v-icon>
          <p class="text-body-2 text-grey">No members found</p>
          <p class="text-caption text-grey">Try a different search term</p>
        </div>

        <div v-else-if="!filteredUsers.length" class="text-center py-4">
          <v-icon size="48" color="grey-lighten-2" class="mb-2">mdi-account-group</v-icon>
          <p class="text-body-2 text-grey">No members available</p>
          <p class="text-caption text-grey">All workspace members are already added</p>
        </div>
      </v-list>

      <!-- Selected Members Summary -->
      <div v-if="selectedMembers.length > 0" class="selected-members mt-4">
        <v-divider class="mb-3" />
        <p class="text-caption font-weight-medium text-grey mb-2">
          Selected ({{ selectedMembers.length }})
        </p>
        <div class="d-flex flex-wrap gap-2">
          <v-chip
            v-for="member in selectedMembers"
            :key="member.id"
            size="small"
            variant="outlined"
            closable
            @click:close="handleRemoveMember(member.id)"
          >
            <v-avatar size="20" class="mr-1">
              <v-img v-if="member.avatar_url" :src="member.avatar_url" />
              <span v-else class="text-caption">
                {{ getUserInitials(member.name) }}
              </span>
            </v-avatar>
            {{ member.name }}
          </v-chip>
        </div>
      </div>
    </v-card-text>

    <v-card-actions class="pa-4">
      <v-spacer />
      <v-btn 
        variant="text" 
        @click="$emit('close')"
        :disabled="addingMembers"
      >
        Cancel
      </v-btn>
      <v-btn 
        color="primary" 
        @click="handleSaveMembers"
        :loading="addingMembers"
        :disabled="selectedMembers.length === 0"
      >
        {{ actionButtonText }}
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
import type { User } from '~/types/models'

interface Props {
  cardId?: number
  currentMembers: User[]
  workspaceId?: number
  boardId?: number

  title?: string
  actionButtonText?: string
  context?: 'card' | 'workspace' | 'board'
}

interface Emits {
  (event: 'close'): void
  (event: 'refresh'): void
  (event: 'members-added', members: User[]): void
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Add Members',
  actionButtonText: 'Add Selected Members',
  context: 'workspace',
})


const emit = defineEmits<Emits>()

const userStore = useUserStore()
const cardStore = useCardStore()
const uiStore = useUiStore()
const workspaceStore = useWorkspaceStore()
const boardStore = useBoardStore()

const search = ref('')
const availableUsers = ref<User[]>([])
const selectedMembers = ref<User[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const addingMembers = ref(false)
const searchTimeout = ref<ReturnType<typeof setTimeout>>()

const filteredUsers = computed(() => {
  if (!search.value) return availableUsers.value
  
  const searchTerm = search.value.toLowerCase()
  return availableUsers.value.filter(user =>
    user.name.toLowerCase().includes(searchTerm) ||
    user.email.toLowerCase().includes(searchTerm)
  )
})

const getUserInitials = (name: string): string => {
  return name
    .split(' ')
    .map(part => part.charAt(0).toUpperCase())
    .join('')
    .slice(0, 2)
}

const isMember = (userId: number): boolean => {
  return props.currentMembers.some(m => m.id === userId) ||
         selectedMembers.value.some(m => m.id === userId)
}

const loadAvailableMembers = async () => {
  loading.value = true
  error.value = null
  try {
    const currentUserId = userStore.currentUser?.id
    const excludeIds = [...props.currentMembers.map(m => m.id), ...selectedMembers.value.map(m => m.id)]
    
    if (currentUserId && !excludeIds.includes(currentUserId)) {
      excludeIds.push(currentUserId)
    }

    if (props.workspaceId) {
      availableUsers.value = await userStore.getWorkspaceMembers(props.workspaceId, excludeIds)
    } else if (props.boardId) {
      availableUsers.value = await userStore.getBoardMembers(props.boardId, excludeIds)
    } else {
      availableUsers.value = await userStore.searchUsers('', excludeIds)
    }
  } catch (err: any) {
    error.value = err.data?.message || 'Failed to load available members'
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  
  searchTimeout.value = setTimeout(async () => {
    if (search.value.trim()) {
      await performSearch()
    } else {
      await loadAvailableMembers()
    }
  }, 300)
}

const performSearch = async () => {
  loading.value = true
  try {
    const excludeIds = [...props.currentMembers.map(m => m.id), ...selectedMembers.value.map(m => m.id)]
    availableUsers.value = await userStore.searchUsers(search.value, excludeIds)
  } catch (err: any) {
    error.value = err.data?.message || 'Failed to search members'
  } finally {
    loading.value = false
  }
}

const handleToggleMember = (user: User) => {
  if (isMember(user.id)) {
    selectedMembers.value = selectedMembers.value.filter(m => m.id !== user.id)
  } else {
    selectedMembers.value.push(user)
  }
}

const handleRemoveMember = (userId: number) => {
  selectedMembers.value = selectedMembers.value.filter(m => m.id !== userId)
}

const handleSaveMembers = async () => {
  if (selectedMembers.value.length === 0) return

  addingMembers.value = true
  try {

    if (props.context === 'card' && props.cardId) {
      for (const member of selectedMembers.value) {
        await cardStore.addMember(props.cardId, { user_id: member.id })
      }
      uiStore.showSnackbar(`Added ${selectedMembers.value.length} member(s) to the card`, 'success')
    } else if (props.context === 'workspace' && props.workspaceId) {
      for (const member of selectedMembers.value) {
        await workspaceStore.addMember(props.workspaceId, { user_id: member.id, role: 'member' })
      }
      uiStore.showSnackbar(`Added ${selectedMembers.value.length} member(s) to the workspace`, 'success')
    } else if (props.context === 'board' && props.boardId) {
      for (const member of selectedMembers.value) {
        await boardStore.addMember(props.boardId, { user_id: member.id, role: 'member' })
      }
      uiStore.showSnackbar(`Added ${selectedMembers.value.length} member(s) to the board`, 'success')
    } else {
      uiStore.showSnackbar('Context not specified for adding members', 'error')
      return
    }
    emit('members-added', selectedMembers.value)
    emit('refresh')
    uiStore.showSnackbar(`Added ${selectedMembers.value.length} member(s)`, 'success')
    emit('close')
  } catch (err: any) {
    uiStore.showSnackbar('Failed to add members', 'error')
  } finally {
    addingMembers.value = false
  }
}

onMounted(async () => {
  await loadAvailableMembers()
})

watch(() => props.currentMembers, () => {
  loadAvailableMembers()
})

onUnmounted(() => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
})
</script>

<style scoped>
.member-list {
  max-height: 300px;
  overflow-y: auto;
}

.member-item {
  border-radius: 8px;
  margin-bottom: 4px;
  transition: all 0.2s ease;
  cursor: pointer;
}

.member-item:hover {
  background-color: rgba(0, 0, 0, 0.04);
}

.member-item--selected {
  background-color: rgba(25, 118, 210, 0.08);
  border-left: 3px solid rgb(25, 118, 210);
}

.selected-members {
  border-top: 1px solid rgba(0, 0, 0, 0.12);
  padding-top: 16px;
}

/* Custom scrollbar */
.member-list::-webkit-scrollbar {
  width: 6px;
}

.member-list::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.04);
  border-radius: 3px;
}

.member-list::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 3px;
}

.member-list::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.3);
}

.gap-2 {
  gap: 8px;
}

@media (max-width: 768px) {
  .member-list {
    max-height: 250px;
  }
}
</style>