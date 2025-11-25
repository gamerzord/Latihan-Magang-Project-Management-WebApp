<template>
  <v-app-bar color="primary" elevation="2">
    <v-app-bar-nav-icon @click="toggleSidebar" />
    
    <v-toolbar-title class="text-h6">
      Project Management Web App
    </v-toolbar-title>

    <v-spacer />

    <!-- Workspace selector -->
    <v-menu offset-y v-if="userStore.isAuthenticated">
      <template #activator="{ props }">
        <v-btn v-bind="props" variant="text" :loading="workspaceStore.loading">
          <v-icon start>mdi-view-dashboard</v-icon>
          {{ currentWorkspace?.name || 'Select Workspace' }}
          <v-icon end>mdi-chevron-down</v-icon>
        </v-btn>
      </template>
      
      <v-list>
        <v-list-item
          v-for="workspace in workspaceStore.workspaces"
          :key="workspace.id"
          @click="selectWorkspace(workspace)"
          :active="currentWorkspace?.id === workspace.id"
        >
          <v-list-item-title>{{ workspace.name }}</v-list-item-title>
          <v-list-item-subtitle v-if="workspace.boards_count !== undefined">
            {{ workspace.boards_count }} boards
          </v-list-item-subtitle>
        </v-list-item>
        
        <v-divider />
        
        <v-list-item @click="uiStore.workspaceCreateDialogOpen = true">
          <v-list-item-title>
            <v-icon start>mdi-plus</v-icon>
            Create Workspace
          </v-list-item-title>
        </v-list-item>
      </v-list>
    </v-menu>

    <!-- User menu -->
    <v-menu offset-y v-if="userStore.isAuthenticated">
      <template #activator="{ props }">
        <v-btn v-bind="props" icon>
          <v-avatar v-if="userStore.user?.avatar_url" :image="userStore.user.avatar_url" size="32" />
          <v-avatar v-else color="secondary" size="32">
            {{ getUserInitials(userStore.user?.name || '') }}
          </v-avatar>
        </v-btn>
      </template>
      
      <v-list>
        <v-list-item>
          <v-list-item-title>{{ userStore.user?.name }}</v-list-item-title>
          <v-list-item-subtitle>{{ userStore.user?.email }}</v-list-item-subtitle>
        </v-list-item>
        
        <v-divider />
        
        <v-list-item @click="handleLogout">
          <v-list-item-title>
            <v-icon start>mdi-logout</v-icon>
            Logout
          </v-list-item-title>
        </v-list-item>
      </v-list>
    </v-menu>
  </v-app-bar>
</template>

<script setup lang="ts">
import type { Workspace } from '~/types/models'

const userStore = useUserStore()
const workspaceStore = useWorkspaceStore()
const uiStore = useUiStore()
const router = useRouter()

const currentWorkspace = computed(() => workspaceStore.currentWorkspace)

const getUserInitials = (name: string): string => {
  return name
    .split(' ')
    .map(part => part.charAt(0).toUpperCase())
    .join('')
    .slice(0, 2)
}

const toggleSidebar = () => {
  uiStore.toggleSidebar()
}

const selectWorkspace = (workspace: Workspace) => {
  workspaceStore.setCurrentWorkspace(workspace)
  router.push(`/workspaces/${workspace.id}`)
}

const handleLogout = async () => {
  try {
    await userStore.logout()
    router.push('/login')
  } catch (error) {
    uiStore.showSnackbar('Logout failed', 'error')
  }
}
</script>