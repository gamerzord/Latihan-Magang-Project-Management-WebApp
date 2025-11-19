import { defineStore } from 'pinia'
import type { Board, CreateBoardRequest, UpdateBoardRequest, AddBoardMemberRequest } from '~/types/models'

export const useBoardStore = defineStore('board', () => {
  const boards = ref<Board[]>([])
  const currentBoard = ref<Board | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const config = useRuntimeConfig()

  const sortByPosition = <T extends { position: number }>(items: T[]): T[] => {
    return [...items].sort((a, b) => a.position - b.position)
  }

  const boardById = computed(() => (id: number) => 
    boards.value.find(b => b.id === id)
  )
  
  const currentBoardLists = computed(() => 
    currentBoard.value?.lists ? sortByPosition(currentBoard.value.lists) : []
  )
  
  const currentBoardLabels = computed(() => 
    currentBoard.value?.labels || []
  )

  const fetchBoards = async (workspaceId?: number) => {
    loading.value = true
    error.value = null
    try {
      const url = workspaceId 
        ? `${config.public.apiBase}/boards?workspace_id=${workspaceId}`
        : `${config.public.apiBase}/boards`
      
      boards.value = await $fetch<Board[]>(url)
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to fetch boards'
    } finally {
      loading.value = false
    }
  }

  const fetchBoard = async (id: number) => {
    loading.value = true
    error.value = null
    try {
      currentBoard.value = await $fetch<Board>(`${config.public.apiBase}/boards/${id}`)
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to fetch board'
      throw err
    } finally {
      loading.value = false
    }
  }

  const createBoard = async (data: CreateBoardRequest) => {
    loading.value = true
    error.value = null
    try {
      const board = await $fetch<Board>(`${config.public.apiBase}/boards`, {
        method: 'POST',
        body: data
      })
      boards.value.push(board)
      return board
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to create board'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateBoard = async (id: number, data: UpdateBoardRequest) => {
    try {
      const updated = await $fetch<Board>(`${config.public.apiBase}/boards/${id}`, {
        method: 'PUT',
        body: data
      })
      const index = boards.value.findIndex(b => b.id === id)
      if (index !== -1) {
        boards.value[index] = updated
      }
      if (currentBoard.value?.id === id) {
        currentBoard.value = { ...currentBoard.value, ...updated }
      }
      return updated
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to update board'
      throw err
    }
  }

  const deleteBoard = async (id: number) => {
    try {
      await $fetch(`${config.public.apiBase}/boards/${id}`, {
        method: 'DELETE'
      })
      boards.value = boards.value.filter(b => b.id !== id)
      if (currentBoard.value?.id === id) {
        currentBoard.value = null
      }
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to delete board'
      throw err
    }
  }

  const addMember = async (boardId: number, data: AddBoardMemberRequest) => {
    try {
      const member = await $fetch(`${config.public.apiBase}/boards/${boardId}/members`, {
        method: 'POST',
        body: data
      })
      if (currentBoard.value?.id === boardId) {
        await fetchBoard(boardId)
      }
      return member
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to add member'
      throw err
    }
  }

  const removeMember = async (boardId: number, userId: number) => {
    try {
      await $fetch(`${config.public.apiBase}/boards/${boardId}/members/${userId}`, {
        method: 'DELETE'
      })
      if (currentBoard.value?.id === boardId) {
        await fetchBoard(boardId)
      }
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to remove member'
      throw err
    }
  }

  const updateMemberRole = async (boardId: number, userId: number, data: { role: string }) => {
    try {
      const member = await $fetch(`${config.public.apiBase}/boards/${boardId}/members/${userId}/role`, {
        method: 'PATCH',
        body: data
      })
      if (currentBoard.value?.id === boardId) {
        await fetchBoard(boardId)
      }
      return member
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to update member role'
      throw err
    }
  }

  const setCurrentBoard = (board: Board | null) => {
    currentBoard.value = board
  }

  const updateLocalBoard = (data: Partial<Board>) => {
    if (currentBoard.value) {
      currentBoard.value = { ...currentBoard.value, ...data }
    }
  }

  const clearError = () => {
    error.value = null
  }

  return {
    boards: readonly(boards),
    currentBoard: readonly(currentBoard),
    loading: readonly(loading),
    error: readonly(error),
    
    boardById,
    currentBoardLists,
    currentBoardLabels,
    
    fetchBoards,
    fetchBoard,
    createBoard,
    updateBoard,
    deleteBoard,
    addMember,
    removeMember,
    updateMemberRole,
    setCurrentBoard,
    updateLocalBoard,
    clearError
  }
})