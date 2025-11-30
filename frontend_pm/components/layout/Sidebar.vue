
<template>
  <v-navigation-drawer
    v-model="uiStore.sidebarOpen"
    :permanent="mdAndUp"
    :temporary="smAndDown"
    location="left"
    class="sidebar"
  >
    <!-- Header -->
    <div class="sidebar-header pa-4">
      <div class="d-flex align-center mb-4">
        <v-icon color="primary" size="28" class="mr-2">mdi-view-dashboard</v-icon>
        <h3 class="text-h6">Workspaces</h3>
      </div>
      
      <v-btn
        color="primary"
        variant="flat"
        block
        @click="uiStore.workspaceCreateDialogOpen = true"
        class="mb-4"
      >
        <v-icon start>mdi-plus</v-icon>
        New Workspace
      </v-btn>
    </div>

    <v-divider />

    <!-- Workspaces List -->
    <div class="workspaces-list">
      <div v-if="workspaceStore.loading" class="text-center py-4">
        <v-progress-circular indeterminate size="24" />
      </div>

      <div v-else-if="workspaceStore.workspaces.length === 0" class="empty-state pa-4 text-center">
        <v-icon size="48" color="grey-lighten-1" class="mb-2">mdi-folder-outline</v-icon>
        <p class="text-body-2 text-medium-emphasis">No workspaces yet</p>
        <v-btn
          variant="text"
          color="primary"
          size="small"
          @click="uiStore.workspaceCreateDialogOpen = true"
        >
          Create one
        </v-btn>
      </div>

      <v-list v-else density="compact" class="pa-0">
        <v-list-item
          v-for="workspace in workspaceStore.workspaces"
          :key="workspace.id"
          :active="workspaceStore.currentWorkspace?.id === workspace.id"
          @click="selectWorkspace(workspace)"
          class="workspace-item"
          :class="{ 'active-workspace': workspaceStore.currentWorkspace?.id === workspace.id }"
        >
          <template #prepend>
            <v-avatar :color="getWorkspaceColor(workspace.id)" size="32" class="workspace-avatar">
              <span class="text-caption font-weight-bold text-white">
                {{ getWorkspaceInitials(workspace.name) }}
              </span>
            </v-avatar>
          </template>

          <v-list-item-title class="text-body-2 font-weight-medium">
            {{ workspace.name }}
          </v-list-item-title>
          
          <v-list-item-subtitle class="text-caption">
            {{ workspace.boards_count || 0 }} boards
          </v-list-item-subtitle>

          <template #append>
            <v-badge
              v-if="workspace.visibility !== 'private'"
              :color="getVisibilityColor(workspace.visibility)"
              dot
              inline
            />
          </template>
        </v-list-item>
      </v-list>
    </div>

    <!-- Quick Actions -->
    <template #append>
      <v-divider />
      <div class="quick-actions pa-2">
        <v-list density="compact" class="pa-0">
          <v-list-item
            v-for="action in quickActions"
            :key="action.title"
            :to="action.to"
            :prepend-icon="action.icon"
            :title="action.title"
            class="quick-action-item"
          />
        </v-list>
      </div>
    </template>
  </v-navigation-drawer>
</template>

<script setup lang="ts">
import type { Workspace } from '~/types/models'
import { useDisplay } from 'vuetify'

const workspaceStore = useWorkspaceStore()
const uiStore = useUiStore()
const display = useDisplay()
const { mdAndUp, smAndDown } = display
const router = useRouter()

const quickActions = [
  { title: 'Calendar View', icon: 'mdi-calendar', to: '/calendar' },
  { title: 'Templates', icon: 'mdi-view-grid', to: '/templates' },
]

const workspaceColors = [
  '#0079bf', '#d29034', '#519839', '#b04632', 
  '#89609e', '#cd5a91', '#4bbf6b', '#00aecc'
]

const getWorkspaceColor = (workspaceId: number): string => {
  const index = workspaceId % workspaceColors.length
  return workspaceColors[index] ?? '#0079bf'
}

const getWorkspaceInitials = (name: string): string => {
  return name
    .split(' ')
    .map(part => part.charAt(0).toUpperCase())
    .join('')
    .slice(0, 2)
}

const getVisibilityColor = (visibility: string): string => {
  switch (visibility) {
    case 'public': return 'success'
    case 'workspace': return 'warning'
    case 'private': return 'grey'
    default: return 'grey'
  }
}

const selectWorkspace = (workspace: Workspace) => {
  workspaceStore.setCurrentWorkspace(workspace)
  router.push(`/workspaces/${workspace.id}`)
  
  if (uiStore.sidebarOpen && !display.mdAndUp) {
    uiStore.sidebarOpen = false
  }
}

watch(() => uiStore.sidebarOpen, async (isOpen) => {
  if (
    isOpen &&
    !workspaceStore.loading &&
    workspaceStore.workspaces.length === 0
  ) {
    await workspaceStore.fetchWorkspaces()
  }
})
</script>

<style scoped>
.sidebar {
  border-right: 1px solid rgba(0, 0, 0, 0.12);
}

.sidebar-header {
  background-color: rgba(0, 0, 0, 0.02);
}

.workspace-item {
  border-radius: 8px;
  margin: 2px 8px;
  transition: all 0.2s ease;
}

.workspace-item:hover {
  background-color: rgba(0, 0, 0, 0.04);
}

.active-workspace {
  background-color: rgba(0, 121, 191, 0.1) !important;
  border-left: 3px solid #0079bf;
}

.workspace-avatar {
  border-radius: 8px;
}

.empty-state {
  opacity: 0.7;
}

.quick-action-item {
  border-radius: 6px;
  margin: 2px 4px;
}

.quick-action-item:hover {
  background-color: rgba(0, 0, 0, 0.04);
}

@media (max-width: 960px) {
  .sidebar-header {
    padding: 16px;
  }
  
  .workspace-item {
    margin: 1px 4px;
  }
}
</style>