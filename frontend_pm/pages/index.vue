<template>
  <v-container fluid class="pa-0 fill-height">
    <!-- Unauthenticated state -->
    <v-row v-if="!userStore.isAuthenticated" class="fill-height" align="center" justify="center">
      <v-col cols="12" sm="8" md="6" lg="4">
        <v-card elevation="8" class="pa-6">
          <v-card-title class="text-h4 text-center py-6 text-primary">
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
      <v-row class="fill-height">
        <v-col class="sidebar-col" cols="12" md="3" lg="2" v-if="uiStore.sidebarOpen">
          <WorkspaceSelector />
        </v-col>
        <v-col class="main-content" :class="{ 'full-width': !uiStore.sidebarOpen }">
          <div class="d-flex align-center mb-4">
            <v-btn
              icon
              variant="text"
              @click="uiStore.toggleSidebar()"
              class="mr-2"
            >
              <v-icon>mdi-menu</v-icon>
            </v-btn>
            <h1 class="text-h5">My Workspaces</h1>
          </div>
          
          <!-- Workspace content -->
          <div v-if="workspaceStore.loading" class="text-center py-8">
            <v-progress-circular indeterminate color="primary" />
          </div>
          
          <div v-else-if="workspaceStore.workspaces.length === 0" class="text-center py-8">
            <v-card variant="outlined" class="pa-6">
              <v-icon size="64" color="grey-lighten-1" class="mb-4">mdi-folder-outline</v-icon>
              <h3 class="text-h6 mb-2">No workspaces yet</h3>
              <p class="text-body-2 text-medium-emphasis mb-4">
                Create your first workspace to get started with project management.
              </p>
              <v-btn color="primary" @click="uiStore.workspaceCreateDialogOpen = true">
                Create Workspace
              </v-btn>
            </v-card>
          </div>
          
          <div v-else>
            <!-- Workspace grid/list will go here -->
            <p class="text-body-1">Select a workspace from the sidebar to get started.</p>
          </div>
        </v-col>
      </v-row>
    </template>

    <!-- Create Workspace Dialog -->
    <WorkspaceCreateDialog 
      v-model="uiStore.workspaceCreateDialogOpen"
    />
  </v-container>
</template>

<script setup lang="ts">
import { useUiStore } from '~/stores/ui'
import { useUserStore } from '~/stores/user'
import { useWorkspaceStore } from '~/stores/workspace'

const userStore = useUserStore()
const workspaceStore = useWorkspaceStore()
const uiStore = useUiStore()

useAutoRefresh(async () => {
  if (userStore.isAuthenticated) {
    await workspaceStore.fetchWorkspaces()
  }
})
</script>

<style scoped>
.fill-height {
  min-height: calc(100vh - 64px);
}

.sidebar-col {
  border-right: 1px solid rgba(0, 0, 0, 0.12);
  background-color: #f8f9fa;
}

.main-content {
  transition: all 0.3s ease;
}

.full-width {
  max-width: 100%;
}

@media (max-width: 960px) {
  .sidebar-col {
    position: fixed;
    top: 64px;
    left: 0;
    height: calc(100vh - 64px);
    z-index: 100;
    transform: translateX(-100%);
    transition: transform 0.3s ease;
  }
  
  .sidebar-col:has(.v-navigation-drawer--active) {
    transform: translateX(0);
  }
}
</style>