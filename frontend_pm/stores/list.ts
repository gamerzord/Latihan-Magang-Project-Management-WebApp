import { defineStore } from 'pinia'
import { useBoardStore } from './board'
import type { List, CreateListRequest, UpdateListRequest, ReorderListsRequest } from '~/types/models'

export const useListStore = defineStore('list', () => {
  const loading = ref(false)
  const error = ref<string | null>(null)
  const config = useRuntimeConfig()
  const boardStore = useBoardStore()

  const createList = async (data: CreateListRequest) => {
    loading.value = true
    error.value = null
    try {
      const list = await $fetch<{ list: List }>(`${config.public.apiBase}/lists`, {
        method: 'POST',
        body: data
      })

      if (boardStore.currentBoard) {
        const currentLists = boardStore.currentBoard.lists || []
        boardStore.updateLocalBoard({
          lists: [...currentLists, list.list]
        })
      }
      
      return list.list
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to create list'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateList = async (id: number, data: UpdateListRequest) => {
    try {
      const updated = await $fetch<{ list: List }>(`${config.public.apiBase}/lists/${id}`, {
        method: 'PUT',
        body: data
      })
      
      if (boardStore.currentBoard?.lists) {
        const index = boardStore.currentBoard.lists.findIndex(l => l.id === id)
        if (index !== -1) {
          const updatedLists = [...boardStore.currentBoard.lists]
          updatedLists[index] = { ...updatedLists[index], ...updated.list }
          boardStore.updateLocalBoard({ lists: updatedLists })
        }
      }
      
      return updated.list
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to update list'
      throw err
    }
  }

  const deleteList = async (id: number) => {
    try {
      await $fetch(`${config.public.apiBase}/lists/${id}`, {
        method: 'DELETE'
      })
      
      if (boardStore.currentBoard?.lists) {
        const filteredLists = boardStore.currentBoard.lists.filter(l => l.id !== id)
        boardStore.updateLocalBoard({ lists: filteredLists })
      }
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to delete list'
      throw err
    }
  }

  const reorderLists = async (lists: Array<{ id: number, position: number }>) => {
    try {
      await $fetch(`${config.public.apiBase}/lists/reorder`, {
        method: 'POST',
        body: { lists } as ReorderListsRequest
      })
      
      if (boardStore.currentBoard?.lists) {
        const updatedLists = boardStore.currentBoard.lists.map(list => {
          const updated = lists.find(l => l.id === list.id)
          return updated ? { ...list, position: updated.position } : list
        })
        boardStore.updateLocalBoard({ lists: updatedLists })
      }
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to reorder lists'
      throw err
    }
  }

  const archiveList = async (id: number) => {
    try {
      const updated = await $fetch<{ list: List }>(`${config.public.apiBase}/lists/${id}/archive`, {
        method: 'POST'
      })
      
      if (boardStore.currentBoard?.lists) {
        const index = boardStore.currentBoard.lists.findIndex(l => l.id === id)
        if (index !== -1) {
          const updatedLists = [...boardStore.currentBoard.lists]
          updatedLists[index] = updated.list
          boardStore.updateLocalBoard({ lists: updatedLists })
        }
      }
      
      return updated.list
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to archive list'
      throw err
    }
  }

  const restoreList = async (id: number) => {
    try {
      const updated = await $fetch<{ list: List }>(`${config.public.apiBase}/lists/${id}/restore`, {
        method: 'POST'
      })
      
      if (boardStore.currentBoard?.lists) {
        const index = boardStore.currentBoard.lists.findIndex(l => l.id === id)
        if (index !== -1) {
          const updatedLists = [...boardStore.currentBoard.lists]
          updatedLists[index] = updated.list
          boardStore.updateLocalBoard({ lists: updatedLists })
        }
      }
      
      return updated.list
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to restore list'
      throw err
    }
  }

  const clearError = () => {
    error.value = null
  }

  return {
    loading: readonly(loading),
    error: readonly(error),
    
    createList,
    updateList,
    deleteList,
    reorderLists,
    archiveList,
    restoreList,
    clearError
  }
})