<template>
  <v-navigation-drawer permanent class="workspace-selector">
    <v-list density="compact" nav class="pa-2">
      <!-- Header -->
      <v-list-item class="mb-4">
        <template #prepend>
          <v-icon icon="mdi-view-dashboard" color="primary" />
        </template>
        <v-list-item-title class="text-h6 font-weight-bold">
          Workspaces
        </v-list-item-title>
        <template #append>
          <v-btn
            icon="mdi-plus"
            size="small"
            variant="text"
            @click="createDialog = true"
          />
        </template>
      </v-list-item>

      <!-- Workspaces List -->
      <div v-if="loading" class="d-flex justify-center py-4">
        <v-progress-circular indeterminate color="primary" size="24" />
      </div>

      <v-list-item
        v-for="workspace in workspaces"
        :key="workspace.id"
        :value="workspace.id"
        :active="isWorkspaceActive(workspace)"
        variant="flat"
        @click="selectWorkspace(workspace)"
        class="workspace-item mb-1"
      >
        <template #prepend>
          <v-avatar color="primary" size="32" class="workspace-avatar">
            <span class="text-caption font-weight-medium">
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
      </v-list-item>

      <!-- Create Workspace Button -->
      <v-list-item
        class="create-workspace-item mt-2"
        @click="createDialog = true"
      >
        <template #prepend>
          <v-icon icon="mdi-plus" size="small" color="primary" />
        </template>
        <v-list-item-title class="text-primary text-body-2">
          Create Workspace
        </v-list-item-title>
      </v-list-item>
    </v-list>

    <!-- Create Workspace Dialog -->
    <v-dialog v-model="createDialog" max-width="500">
      <v-card>
        <v-card-title>Create New Workspace</v-card-title>
        
        <v-card-text>
          <v-text-field
            v-model="newWorkspace.name"
            label="Workspace Name"
            variant="outlined"
            class="mb-3"
            :rules="[requiredRule]"
          />

          <v-textarea
            v-model="newWorkspace.description"
            label="Description (optional)"
            variant="outlined"
            rows="3"
            class="mb-3"
          />

          <v-select
            v-model="newWorkspace.visibility"
            :items="VISIBILITY_OPTIONS"
            item-title="label"
            item-value="value"
            label="Visibility"
            variant="outlined"
          />
        </v-card-text>
        
        <v-card-actions>
          <v-spacer />
          <v-btn @click="createDialog = false">Cancel</v-btn>
          <v-btn
            color="primary"
            :loading="creating"
            :disabled="!newWorkspace.name.trim()"
            @click="handleCreate"
          >
            Create
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-navigation-drawer>
</template>

<script setup lang="ts">
import type { Workspace } from '~/types/models'

const route = useRoute()
const workspaceStore = useWorkspaceStore()
const uiStore = useUiStore()

const workspaces = computed(() => workspaceStore.workspaces)
const loading = computed(() => workspaceStore.loading)

const createDialog = ref(false)
const creating = ref(false)

const newWorkspace = reactive({
  name: '',
  description: '',
  visibility: 'private' as 'private' | 'workspace' | 'public'
})

const VISIBILITY_OPTIONS = [
  { label: 'Private', value: 'private' },
  { label: 'Workspace', value: 'workspace' },
  { label: 'Public', value: 'public' }
]

const requiredRule = (value: string) => !!value || 'This field is required'

const getWorkspaceInitials = (name: string): string => {
  return name
    .split(' ')
    .map(part => part.charAt(0).toUpperCase())
    .join('')
    .slice(0, 2)
}

const isWorkspaceActive = (workspace: Workspace): boolean => {
  return route.params.id === workspace.id.toString()
}

const selectWorkspace = (workspace: Workspace) => {
  workspaceStore.setCurrentWorkspace(workspace)
  navigateTo(`/workspaces/${workspace.id}`)
}

const handleCreate = async () => {
  if (!newWorkspace.name.trim()) return

  creating.value = true
  try {
    const workspace = await workspaceStore.createWorkspace({
      name: newWorkspace.name.trim(),
      description: newWorkspace.description,
      visibility: newWorkspace.visibility
    })
    createDialog.value = false
    resetForm()
    navigateTo(`/workspaces/${workspace.id}`)
  } catch (error) {
  } finally {
    creating.value = false
  }
}

const resetForm = () => {
  newWorkspace.name = ''
  newWorkspace.description = ''
  newWorkspace.visibility = 'private'
}

onMounted(() => {
  if (workspaces.value.length === 0) {
    workspaceStore.fetchWorkspaces()
  }
})

watch(createDialog, (open) => {
  if (!open) {
    resetForm()
  }
})
</script>

<style scoped>
.workspace-selector {
  border-right: 1px solid rgba(0, 0, 0, 0.12);
  background-color: #f8f9fa;
}

.workspace-item {
  border-radius: 8px;
  transition: all 0.2s ease;
}

.workspace-item:hover {
  background-color: rgba(0, 0, 0, 0.04);
}

.workspace-item.v-list-item--active {
  background-color: rgba(25, 118, 210, 0.08);
  border-left: 3px solid rgb(25, 118, 210);
}

.workspace-avatar {
  font-size: 0.75rem;
}

.create-workspace-item {
  border-radius: 8px;
  border: 1px dashed rgba(0, 0, 0, 0.2);
  cursor: pointer;
  transition: all 0.2s ease;
}

.create-workspace-item:hover {
  background-color: rgba(25, 118, 210, 0.04);
  border-color: rgb(25, 118, 210);
}

@media (max-width: 960px) {
  .workspace-selector {
    position: fixed;
    top: 64px;
    left: 0;
    height: calc(100vh - 64px);
    z-index: 1000;
    transform: translateX(-100%);
    transition: transform 0.3s ease;
  }

  .workspace-selector.v-navigation-drawer--active {
    transform: translateX(0);
  }
}
</style>