import { defineStore } from 'pinia'

export const useUiStore = defineStore('ui', () => {
  const cardModalOpen = ref(false)
  const cardModalId = ref<number | null>(null)
  const boardCreateDialogOpen = ref(false)
  const listCreateDialogOpen = ref(false)
  const workspaceCreateDialogOpen = ref(false)
  const sidebarOpen = ref(true)
  const loading = ref(false)
  const snackbar = ref({
    show: false,
    message: '',
    color: 'success' as 'success' | 'error' | 'warning' | 'info',
  })

  const openCardModal = (cardId: number) => {
    cardModalOpen.value = true
    cardModalId.value = cardId
  }

  const closeCardModal = () => {
    cardModalOpen.value = false
    cardModalId.value = null
  }

  const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value
  }

  const showSnackbar = (message: string, color: 'success' | 'error' | 'warning' | 'info' = 'success') => {
    snackbar.value = { show: true, message, color }
  }

  const hideSnackbar = () => {
    snackbar.value.show = false
  }

  const setLoading = (loadingState: boolean) => {
    loading.value = loadingState
  }

  const isCardModalOpen = computed(() => cardModalOpen.value)
  const currentCardModalId = computed(() => cardModalId.value)
  const isSidebarOpen = computed(() => sidebarOpen.value)
  const isLoading = computed(() => loading.value)
  const currentSnackbar = computed(() => snackbar.value)

  return {
    cardModalOpen: readonly(cardModalOpen),
    cardModalId: readonly(cardModalId),
    boardCreateDialogOpen: readonly(boardCreateDialogOpen),
    listCreateDialogOpen: readonly(listCreateDialogOpen),
    workspaceCreateDialogOpen: readonly(workspaceCreateDialogOpen),
    sidebarOpen: readonly(sidebarOpen),
    loading: readonly(loading),
    snackbar: readonly(snackbar),
    
    isCardModalOpen,
    currentCardModalId,
    isSidebarOpen,
    isLoading,
    currentSnackbar,
    
    openCardModal,
    closeCardModal,
    toggleSidebar,
    showSnackbar,
    hideSnackbar,
    setLoading
  }
})