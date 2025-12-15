<template>
  <v-container fluid class="pa-0 fill-height">
    <!-- Unauthenticated state -->
    <v-row v-if="!userStore.isAuthenticated" class="unauthenticated" align="center" justify="center">
      <v-col cols="12" sm="8" md="6" lg="4">
        <v-card elevation="8" class="pa-6">
          <v-card-title class="text-h4 text-center py-6 text-primary text-wrap">
            Welcome to Project Management Web App
          </v-card-title>
          <v-card-text class="text-center pb-6">
            <p class="text-body-1 text-medium-emphasis mb-6">
              A project management tool to organize your tasks and collaborate with your team.
            </p>
            <v-btn
              color="primary"
              size="large"
              class="mr-2"
              to="/login"
              variant="flat"
            >
              Login
            </v-btn>
            <v-btn
              color="secondary"
              size="large"
              to="/register"
              variant="outlined"
            >
              Register
            </v-btn>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Authenticated state -->
    <template v-else>
      <!-- Main Content Area -->
      <v-main>
        <v-container fluid class="main-content">
          <!-- Header with Create Workspace button -->
          <div class="d-flex align-center justify-space-between mb-6 flex-wrap">
            <div>
              <h1 class="text-h4">My Workspaces</h1>
              <p class="text-body-1 text-grey">Manage all your projects and teams</p>
            </div>
            <v-btn
              color="primary"
              prepend-icon="mdi-plus"
              @click="uiStore.workspaceCreateDialogOpen = true"
            >
              New Workspace
            </v-btn>
          </div>
          
          <!-- Loading State -->
          <div v-if="workspaceStore.loading" class="text-center py-12">
            <v-progress-circular indeterminate color="primary" size="64" />
            <p class="text-body-1 mt-4">Loading your workspaces...</p>
          </div>
          
          <!-- Empty State -->
          <div v-else-if="workspaceStore.workspaces.length === 0" class="text-center py-12">
            <v-card variant="outlined" class="pa-8 mx-auto" max-width="400">
              <v-icon size="72" color="grey-lighten-1" class="mb-4">mdi-folder-plus-outline</v-icon>
              <h3 class="text-h5 mb-2">No workspaces yet</h3>
              <p class="text-body-2 text-grey mb-6">
                Create your first workspace to organize your projects and collaborate with your team.
              </p>
              <v-btn 
                color="primary" 
                size="large"
                prepend-icon="mdi-plus"
                @click="uiStore.workspaceCreateDialogOpen = true"
              >
                Create First Workspace
              </v-btn>
            </v-card>
          </div>
          
          <!-- Workspaces Grid -->
          <div v-else class="workspaces-grid">
            <!-- Workspace Cards -->
            <v-card
              v-for="workspace in workspaceStore.workspaces"
              :key="workspace.id"
              class="workspace-card"
              hover
              @click="navigateTo(`/workspaces/${workspace.id}`)"
            >
              <v-card-text class="pa-4">
                <div class="d-flex align-start">
                  <!-- Workspace Icon/Color -->
                  <div class="workspace-icon mr-3">
                    <v-avatar
                      :color="getWorkspaceColor(workspace.id)"
                      size="48"
                    >
                      <span class="text-h6 text-white">
                        {{ workspace.name.charAt(0).toUpperCase() }}
                      </span>
                    </v-avatar>
                  </div>
                  
                  <!-- Workspace Info -->
                  <div class="flex-grow-1">
                    <h3 class="text-h6 font-weight-bold mb-1">
                      {{ workspace.name }}
                    </h3>
                    <p v-if="workspace.description" class="text-body-2 text-grey mb-2">
                      {{ truncateDescription(workspace.description) }}
                    </p>
                    
                    <!-- Stats -->
                    <div class="d-flex align-center text-caption text-grey mt-3 flex-wrap">
                      <div class="d-flex align-center mr-4">
                        <v-icon size="16" class="mr-1">mdi-account-group</v-icon>
                        <span>{{ workspace.members?.length || 0 }} members</span>
                      </div>
                      <div class="d-flex align-center mr-4">
                        <v-icon size="16" class="mr-1">mdi-view-column</v-icon>
                        <span>{{ workspace.boards_count || 0 }} boards</span>
                      </div>
                      <div class="d-flex align-center">
                        <v-icon size="16" class="mr-1">mdi-earth</v-icon>
                        <span class="text-capitalize">{{ workspace.visibility }}</span>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Quick Actions Menu -->
                  <v-menu>
                    <template #activator="{ props }">
                      <v-btn
                        v-bind="props"
                        icon
                        size="small"
                        variant="text"
                        @click.stop
                      >
                        <v-icon>mdi-dots-vertical</v-icon>
                      </v-btn>
                    </template>
                    <v-list density="compact">
                      <v-list-item @click.stop="editWorkspace(workspace)">
                        <v-list-item-title>Edit</v-list-item-title>
                      </v-list-item>
                      <v-list-item @click.stop="shareWorkspace(workspace)">
                        <v-list-item-title>Share</v-list-item-title>
                      </v-list-item>
                      <v-list-item 
                        @click.stop="leaveWorkspace(workspace)"
                        v-if="workspace.creator?.id !== userStore.user?.id"
                      >
                        <v-list-item-title>Leave</v-list-item-title>
                      </v-list-item>
                      <v-divider v-if="workspace.creator?.id === userStore.user?.id" />
                      <v-list-item 
                        v-if="workspace.creator?.id === userStore.user?.id"
                        @click.stop="deleteWorkspace(workspace)"
                      >
                        <v-list-item-title class="text-error">Delete</v-list-item-title>
                      </v-list-item>
                    </v-list>
                  </v-menu>
                </div>
              </v-card-text>
              
              <!-- Footer with last activity -->
              <v-divider />
              <v-card-actions class="pa-3">
                <div class="text-caption text-grey">
                  <v-icon size="14" class="mr-1">mdi-clock-outline</v-icon>
                  Last updated {{ formatRelativeTime(workspace.updated_at) }}
                </div>
              </v-card-actions>
            </v-card>
            
            <!-- Create New Workspace Card -->
            <v-card
              class="workspace-card create-workspace-card"
              @click="uiStore.workspaceCreateDialogOpen = true"
            >
              <v-card-text class="d-flex flex-column align-center justify-center fill-height pa-6">
                <v-icon size="48" color="grey">mdi-plus</v-icon>
                <span class="text-body-1 text-grey mt-4">Create new workspace</span>
              </v-card-text>
            </v-card>
          </div>
          
          <!-- Recently Visited Section -->
          <div v-if="recentWorkspaces.length > 0" class="mt-8">
            <h3 class="text-h6 mb-4">Recently Visited</h3>
            <div class="recent-workspaces">
              <v-chip
                v-for="workspace in recentWorkspaces"
                :key="workspace.id"
                :to="`/workspaces/${workspace.id}`"
                size="large"
                class="mr-2 mb-2"
                :color="getWorkspaceColor(workspace.id)"
                variant="flat"
              >
                <v-avatar start>
                  {{ workspace.name.charAt(0).toUpperCase() }}
                </v-avatar>
                {{ workspace.name }}
              </v-chip>
            </div>
          </div>
        </v-container>
      </v-main>
    </template>

    <!-- Create Workspace Dialog -->
    <LayoutWorkspaceCreateDialog 
      v-model="uiStore.workspaceCreateDialogOpen"
      @workspace-created="handleWorkspaceCreated"
    />
  </v-container>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const userStore = useUserStore()
const workspaceStore = useWorkspaceStore()
const uiStore = useUiStore()

useAutoRefresh(async () => {
  if (userStore.isAuthenticated) {
    await workspaceStore.fetchWorkspaces()
  }
})

const recentWorkspaces = computed(() => {
  return workspaceStore.workspaces.slice(0, 3)
})

const getWorkspaceColor = (id: number): string => {
  const colors = [
    '#0079bf', '#d29034', '#519839', '#b04632', 
    '#89609e', '#cd5a91', '#4bbf6b', '#00aecc'
  ]
  return colors[id % colors.length] ?? '#0079bf'
}

const truncateDescription = (description: string, length: number = 80): string => {
  if (description.length <= length) return description
  return description.substring(0, length) + '...'
}

const formatRelativeTime = (dateString: string): string => {
  const date = new Date(dateString)
  const now = new Date()
  const diffInHours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60))
  
  if (diffInHours < 1) return 'just now'
  if (diffInHours < 24) return `${diffInHours}h ago`
  if (diffInHours < 168) return `${Math.floor(diffInHours / 24)}d ago`
  return date.toLocaleDateString()
}

const editWorkspace = (workspace: any) => {
  console.log('Edit workspace:', workspace)
}

const shareWorkspace = (workspace: any) => {
  console.log('Share workspace:', workspace)
}

const leaveWorkspace = async (workspace: any) => {
  if (!confirm(`Are you sure you want to leave "${workspace.name}"?`)) return
  
  try {
    await workspaceStore.leaveWorkspace(workspace.id)
    uiStore.showSnackbar(`Left ${workspace.name}`, 'success')
  } catch (error) {
    uiStore.showSnackbar('Failed to leave workspace', 'error')
  }
}

const deleteWorkspace = async (workspace: any) => {
  if (!confirm(`Are you sure you want to delete "${workspace.name}" and all its boards? This cannot be undone.`)) return
  
  try {
    await workspaceStore.deleteWorkspace(workspace.id)
    uiStore.showSnackbar(`Deleted ${workspace.name}`, 'success')
  } catch (error) {
    uiStore.showSnackbar('Failed to delete workspace', 'error')
  }
}

const handleWorkspaceCreated = (workspace: any) => {
  uiStore.showSnackbar(`Workspace "${workspace.name}" created!`, 'success')
  navigateTo(`/workspaces/${workspace.id}`)
}
</script>

<style scoped>
.unauthenticated {
  position: fixed;
  inset: 0;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.fill-height {
  min-height: 100vh;
}

.main-content {
  padding: 24px;
  margin: 0 auto;
}

/* Workspaces Grid */
.workspaces-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 16px;
}

.workspace-card {
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
  border: 1px solid rgba(0, 0, 0, 0.08);
}

.workspace-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1) !important;
}

.create-workspace-card {
  background: #f4f5f7;
  border: 2px dashed #dfe1e6;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 180px;
}

.create-workspace-card:hover {
  background: #ebecf0;
  border-color: #c1c7d0;
}

.workspace-icon {
  flex-shrink: 0;
}

/* Recent Workspaces */
.recent-workspaces {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

/* Responsive */
@media (max-width: 960px) {
  .main-content {
    padding: 16px;
  }
  
  .workspaces-grid {
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 12px;
  }
}

@media (max-width: 600px) {
  .workspaces-grid {
    grid-template-columns: 1fr;
  }
  
  .d-flex.justify-space-between {
    flex-direction: column;
    align-items: flex-start !important;
    gap: 16px;
  }
  
  .d-flex.justify-space-between .v-btn {
    width: 100%;
  }
}
</style>