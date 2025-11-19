import { defineStore } from 'pinia'
import type { User } from '~/types/models'

export const useUserStore = defineStore('user', () => {
  const user = ref<User | null>(null)
  const isAuthenticated = ref(false)
  const isLoading = ref(false)
  const config = useRuntimeConfig()
  const baseURL = config.public.apiBase.replace('/api', '')

  const currentUser = computed(() => user.value)
  const isLoggedIn = computed(() => isAuthenticated.value)

  const setUser = (userData: User) => {
    user.value = userData
    isAuthenticated.value = true
  }

  const clearUser = () => {
    user.value = null
    isAuthenticated.value = false
  }

  const checkAuth = async (): Promise<boolean> => {
    if (isLoading.value) return false
    
    isLoading.value = true
    try {
      const userData = await $fetch<User>(`${config.public.apiBase}/user`)
      setUser(userData)
      return true
    } catch (error) {
      clearUser()
      return false
    } finally {
      isLoading.value = false
    }
  }

  const login = async (credentials: { email: string; password: string }): Promise<User> => {
    isLoading.value = true
    try {
      await $fetch('/sanctum/csrf-cookie', { baseURL })
      
      const userData = await $fetch<User>(`${config.public.apiBase}/login`, {
        method: 'POST',
        body: credentials
      })
      
      setUser(userData)
      return userData
    } catch (error) {
      clearUser()
      throw error
    } finally {
      isLoading.value = false
    }
  }

  const register = async (userData: { name: string; email: string; password: string; password_confirmation: string }): Promise<User> => {
    isLoading.value = true
    try {
      await $fetch('/sanctum/csrf-cookie', { baseURL })
      
      const newUser = await $fetch<User>(`${config.public.apiBase}/register`, {
        method: 'POST',
        body: userData
      })
      
      setUser(newUser)
      return newUser
    } catch (error) {
      clearUser()
      throw error
    } finally {
      isLoading.value = false
    }
  }

  const logout = async (): Promise<void> => {
    try {
      await $fetch(`${config.public.apiBase}/logout`, { method: 'POST' })
    } catch (error) {
    } finally {
      clearUser()
    }
  }

  return {
    user,
    isAuthenticated,
    isLoading,
    
    currentUser,
    isLoggedIn,
    
    setUser,
    clearUser,
    checkAuth,
    login,
    register,
    logout
  }
})