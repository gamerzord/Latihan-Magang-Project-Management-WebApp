import type { Workspace, CreateWorkspaceRequest, UpdateWorkspaceRequest, AddWorkspaceMemberRequest, UpdateMemberRoleRequest, WorkspaceMember, WorkspaceMembershipResponse } from '~/types/models'

export const useWorkspaceStore = defineStore('workspace', () => {
  const workspaces = ref<Workspace[]>([])
  const currentWorkspace = ref<Workspace | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const config = useRuntimeConfig()

  const workspaceById = computed(() => (id: number) => 
    workspaces.value.find(w => w.id === id)
  )
  
  const currentWorkspaceBoards = computed(() => 
    currentWorkspace.value?.boards || []
  )

  const fetchWorkspaces = async () => {
    loading.value = true
    error.value = null
    try {
      const response = await $fetch<{ workspaces: Workspace[]}>(`${config.public.apiBase}/workspaces`)
      workspaces.value = response.workspaces
      return response.workspaces
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to fetch workspaces'
    } finally {
      loading.value = false
    }
  }

  const fetchWorkspace = async (id: number) => {
    loading.value = true
    error.value = null
    try {
      const response = await $fetch<{ workspace: Workspace }>(`${config.public.apiBase}/workspaces/${id}`)
      currentWorkspace.value = response.workspace
      return response.workspace
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to fetch workspace'
    } finally {
      loading.value = false
    }
  }

  const createWorkspace = async (data: CreateWorkspaceRequest) => {
    loading.value = true
    error.value = null
    try {
      const response = await $fetch<{ workspace: Workspace }>(`${config.public.apiBase}/workspaces`, {
        method: 'POST',
        body: data
      })
      workspaces.value.push(response.workspace)
      return response.workspace
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to create workspace'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateWorkspace = async (id: number, data: UpdateWorkspaceRequest) => {
    try {
      const response = await $fetch<{ workspace: Workspace }>(`${config.public.apiBase}/workspaces/${id}`, {
        method: 'PUT',
        body: data
      })
      const index = workspaces.value.findIndex(w => w.id === id)
      if (index !== -1) {
        workspaces.value[index] = response.workspace
      }
      if (currentWorkspace.value?.id === id) {
        currentWorkspace.value = response.workspace
      }
      return response.workspace
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to update workspace'
      throw err
    }
  }

  const deleteWorkspace = async (id: number) => {
    try {
      await $fetch(`${config.public.apiBase}/workspaces/${id}`, {
        method: 'DELETE'
      })
      workspaces.value = workspaces.value.filter(w => w.id !== id)
      if (currentWorkspace.value?.id === id) {
        currentWorkspace.value = null
      }
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to delete workspace'
      throw err
    }
  }

  const addMember = async (workspaceId: number, data: AddWorkspaceMemberRequest) => {
    try {
      const response = await $fetch<{ member: WorkspaceMember }>(`${config.public.apiBase}/workspaces/${workspaceId}/members`, {
        method: 'POST',
        body: data
      })
      if (currentWorkspace.value?.id === workspaceId) {
        await fetchWorkspace(workspaceId)
      }
      return response.member
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to add member'
      throw err
    }
  }

  const removeMember = async (workspaceId: number, userId: number) => {
    try {
      await $fetch(`${config.public.apiBase}/workspaces/${workspaceId}/members/${userId}`, {
        method: 'DELETE'
      })
      if (currentWorkspace.value?.id === workspaceId) {
        await fetchWorkspace(workspaceId)
      }
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to remove member'
      throw err
    }
  }

  const updateMemberRole = async (workspaceId: number, userId: number, data: UpdateMemberRoleRequest) => {
    try {
      const response = await $fetch<{ member: WorkspaceMember }>(`${config.public.apiBase}/workspaces/${workspaceId}/members/${userId}/role`, {
        method: 'PATCH',
        body: data
      })
      if (currentWorkspace.value?.id === workspaceId) {
        await fetchWorkspace(workspaceId)
      }
      return response.member
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to update member role'
      throw err
    }
  }

  const leaveWorkspace = async (workspaceId: number) => {
    try {
      await $fetch(`${config.public.apiBase}/workspaces/${workspaceId}/leave`, {
        method: 'POST'
      })
      workspaces.value = workspaces.value.filter(w => w.id !== workspaceId)
      if (currentWorkspace.value?.id === workspaceId) {
        currentWorkspace.value = null
      }
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to leave workspace'
      throw err
    }
  }

const getMembership = async (workspaceId: number) => {
  try {
    const response = await $fetch<WorkspaceMembershipResponse>(
      `${config.public.apiBase}/workspaces/${workspaceId}/membership`
    )

    return {
      workspace: response.workspace,
      membership: response.membership
    }
  } catch (err: any) {
    error.value = err.data?.message || 'Failed to get membership'
    throw err
  }
}

  const setCurrentWorkspace = (workspace: Workspace | null) => {
    currentWorkspace.value = workspace
  }

  const clearError = () => {
    error.value = null
  }

  return {
    workspaces: workspaces,
    currentWorkspace: currentWorkspace,
    loading: loading,
    error: error,
    
    workspaceById,
    currentWorkspaceBoards,
    
    fetchWorkspaces,
    fetchWorkspace,
    createWorkspace,
    updateWorkspace,
    deleteWorkspace,
    addMember,
    removeMember,
    updateMemberRole,
    leaveWorkspace,
    getMembership,
    setCurrentWorkspace,
    clearError
  }
})