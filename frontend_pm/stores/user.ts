import type { User } from '~/types/models'

export const useUserStore = defineStore('user', () => {
  const user = ref<User | null>(null)
  const isAuthenticated = ref(false)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const config = useRuntimeConfig()

  const currentUser = computed(() => user.value)
  const isLoggedIn = computed(() => isAuthenticated.value)

  const setUser = (userData: User) => {
    user.value = userData
    isAuthenticated.value = true
    error.value = null
  }

  const clearUser = () => {
    user.value = null
    isAuthenticated.value = false
    error.value = null
  }

  const clearError = () => {
    error.value = null
  }

  const checkAuth = async (): Promise<boolean> => {
    if (isLoading.value) return false
    
    isLoading.value = true
    error.value = null
    try {
      const userData = await $fetch<{ user: User }>(`${config.public.apiBase}/user`)
      setUser(userData.user)
      return true
    } catch (err: any) {
      clearUser()
      
      if (err.status !== 401) {
        error.value = err.data?.message || 'Authentication check failed'
      }

      return false
    } finally {
      isLoading.value = false
    }
  }

  const login = async (credentials: { email: string; password: string }): Promise<User> => {
    isLoading.value = true
    error.value = null
    try {
      await $fetch('https://localhost:8000/sanctum/csrf-cookie')
      
      const userData = await $fetch<{ user: User }>(`${config.public.apiBase}/login`, {
        method: 'POST',
        body: credentials,
      })
      
      setUser(userData.user)
      return userData.user
    } catch (err: any) {
      clearUser()
      error.value = err.data?.message || 'Login failed. Please check your credentials.'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  const register = async (userData: { name: string; email: string; password: string; password_confirmation: string }): Promise<User> => {
    isLoading.value = true
    error.value = null
    try {
      await $fetch('https://localhost:8000/sanctum/csrf-cookie')
      
      const newUser = await $fetch<{ user: User }>(`${config.public.apiBase}/register`, {
        method: 'POST',
        body: userData
      })
      
      setUser(newUser.user)
      return newUser.user
    } catch (err: any) {
      clearUser()
      error.value = err.data?.message || 'Registration failed. Please try again.'
      throw err
    } finally {
      isLoading.value = false
    }
  }

    const searchUsers = async (query: string, excludeIds: number[] = []) => {
    try {
      const response = await $fetch<{ users: User[] }>(`${config.public.apiBase}/users/search`, {
        method: 'GET',
        params: { q: query, exclude: excludeIds.join(',') }
      })
      return response.users
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to search users'
      throw err
    }
  }

  const getWorkspaceMembers = async (workspaceId: number, excludeIds: number[] = []) => {
    try {
      const response = await $fetch<{ members: User[] }>(`${config.public.apiBase}/workspaces/${workspaceId}/available-members`, {
        method: 'GET',
        params: { exclude: excludeIds.join(',') }
      })
      return response.members
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to fetch workspace members'
      throw err
    }
  }

  const getBoardMembers = async (boardId: number, excludeIds: number[] = []) => {
    try {
      const response = await $fetch<{ members: User[] }>(`${config.public.apiBase}/boards/${boardId}/available-members`, {
        method: 'GET',
        params: { exclude: excludeIds.join(',') }
      })
      return response.members
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to fetch board members'
      throw err
    }
  }

  const logout = async (): Promise<void> => {
    try {
      await $fetch(`${config.public.apiBase}/logout`, { method: 'POST' })
    } catch (err: any) {
      error.value = err.data?.message || 'Logout failed'
    } finally {
      clearUser()
    }
  }

  return {
    user: user,
    isAuthenticated: isAuthenticated,
    isLoading: isLoading,
    error: error,
    
    currentUser,
    isLoggedIn,
    
    setUser,
    clearUser,
    clearError,
    checkAuth,
    login,
    register,
    searchUsers,
    getWorkspaceMembers,
    getBoardMembers,
    logout
  }
}, {
  persist: true
})