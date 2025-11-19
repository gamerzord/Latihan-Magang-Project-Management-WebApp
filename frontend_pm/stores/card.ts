import { defineStore } from 'pinia'
import { useBoardStore } from './board'
import type { Card, CreateCardRequest, UpdateCardRequest, MoveCardRequest, AddLabelRequest, AddCardMemberRequest } from '~/types/models'

export const useCardStore = defineStore('card', () => {
  const currentCard = ref<Card | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const config = useRuntimeConfig()
  const boardStore = useBoardStore()

  const updateCardInBoardStore = (cardId: number, updates: Partial<Card>) => {
    if (boardStore.currentBoard?.lists) {
      for (const list of boardStore.currentBoard.lists) {
        const cardIndex = list.cards?.findIndex(c => c.id === cardId)
        if (cardIndex !== undefined && cardIndex !== -1 && list.cards) {
          list.cards[cardIndex] = { ...list.cards[cardIndex], ...updates }
          break
        }
      }
    }
  }

  const removeCardFromBoardStore = (cardId: number) => {
    if (boardStore.currentBoard?.lists) {
      for (const list of boardStore.currentBoard.lists) {
        if (list.cards) {
          list.cards = list.cards.filter(c => c.id !== cardId)
        }
      }
    }
  }

  const fetchCard = async (id: number) => {
    loading.value = true
    error.value = null
    try {
      currentCard.value = await $fetch<Card>(`${config.public.apiBase}/cards/${id}`)
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to fetch card'
    } finally {
      loading.value = false
    }
  }

  const createCard = async (data: CreateCardRequest) => {
    loading.value = true
    error.value = null
    try {
      const card = await $fetch<Card>(`${config.public.apiBase}/cards`, {
        method: 'POST',
        body: data
      })
      
      if (boardStore.currentBoard?.lists) {
        const list = boardStore.currentBoard.lists.find(l => l.id === data.list_id)
        if (list) {
          const currentCards = list.cards || []
          list.cards = [...currentCards, card]
        }
      }
      
      return card
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to create card'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateCard = async (id: number, data: UpdateCardRequest) => {
    try {
      const updated = await $fetch<Card>(`${config.public.apiBase}/cards/${id}`, {
        method: 'PUT',
        body: data
      })
      
      if (currentCard.value?.id === id) {
        currentCard.value = { ...currentCard.value, ...updated }
      }
      
      updateCardInBoardStore(id, updated)
      
      return updated
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to update card'
      throw err
    }
  }

  const deleteCard = async (id: number) => {
    try {
      await $fetch(`${config.public.apiBase}/cards/${id}`, {
        method: 'DELETE'
      })
      
      if (currentCard.value?.id === id) {
        currentCard.value = null
      }
      
      removeCardFromBoardStore(id)
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to delete card'
      throw err
    }
  }

  const moveCard = async (id: number, listId: number, position: number) => {
    try {
      await $fetch(`${config.public.apiBase}/cards/${id}/move`, {
        method: 'POST',
        body: { list_id: listId, position } as MoveCardRequest
      })
      
      if (boardStore.currentBoard?.lists) {
        let movedCard: Card | undefined
        for (const list of boardStore.currentBoard.lists) {
          const cardIndex = list.cards?.findIndex(c => c.id === id)
          if (cardIndex !== undefined && cardIndex !== -1 && list.cards) {
            movedCard = list.cards.splice(cardIndex, 1)[0]
            break
          }
        }
        
        if (movedCard) {
          const newList = boardStore.currentBoard.lists.find(l => l.id === listId)
          if (newList) {
            movedCard.list_id = listId
            movedCard.position = position
            if (!newList.cards) newList.cards = []
            newList.cards.push(movedCard)
          }
        }
      }
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to move card'
      throw err
    }
  }

  const addLabel = async (cardId: number, data: AddLabelRequest) => {
    try {
      const result = await $fetch(`${config.public.apiBase}/cards/${cardId}/labels`, {
        method: 'POST',
        body: data
      })
      
      if (currentCard.value?.id === cardId) {
        await fetchCard(cardId)
      }
      
      return result
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to add label'
      throw err
    }
  }

  const removeLabel = async (cardId: number, labelId: number) => {
    try {
      await $fetch(`${config.public.apiBase}/cards/${cardId}/labels/${labelId}`, {
        method: 'DELETE'
      })
      
      if (currentCard.value?.id === cardId) {
        await fetchCard(cardId)
      }
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to remove label'
      throw err
    }
  }

  const addMember = async (cardId: number, data: AddCardMemberRequest) => {
    try {
      const result = await $fetch(`${config.public.apiBase}/cards/${cardId}/members`, {
        method: 'POST',
        body: data
      })
      
      if (currentCard.value?.id === cardId) {
        await fetchCard(cardId)
      }
      
      return result
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to add member'
      throw err
    }
  }

  const removeMember = async (cardId: number, userId: number) => {
    try {
      await $fetch(`${config.public.apiBase}/cards/${cardId}/members/${userId}`, {
        method: 'DELETE'
      })
      
      if (currentCard.value?.id === cardId) {
        await fetchCard(cardId)
      }
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to remove member'
      throw err
    }
  }

  const archiveCard = async (cardId: number) => {
    try {
      const updated = await $fetch<Card>(`${config.public.apiBase}/cards/${cardId}/archive`, {
        method: 'POST'
      })
      
      updateCardInBoardStore(cardId, updated)
      if (currentCard.value?.id === cardId) {
        currentCard.value = updated
      }
      
      return updated
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to archive card'
      throw err
    }
  }

  const restoreCard = async (cardId: number) => {
    try {
      const updated = await $fetch<Card>(`${config.public.apiBase}/cards/${cardId}/restore`, {
        method: 'POST'
      })
      
      updateCardInBoardStore(cardId, updated)
      if (currentCard.value?.id === cardId) {
        currentCard.value = updated
      }
      
      return updated
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to restore card'
      throw err
    }
  }

  const toggleDueDateCompletion = async (cardId: number) => {
    try {
      const updated = await $fetch<Card>(`${config.public.apiBase}/cards/${cardId}/toggle-due`, {
        method: 'POST'
      })
      
      updateCardInBoardStore(cardId, updated)
      if (currentCard.value?.id === cardId) {
        currentCard.value = updated
      }
      
      return updated
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to toggle due date'
      throw err
    }
  }

  const setCurrentCard = (card: Card | null) => {
    currentCard.value = card
  }

  const clearError = () => {
    error.value = null
  }

  return {
    currentCard: readonly(currentCard),
    loading: readonly(loading),
    error: readonly(error),
    
    fetchCard,
    createCard,
    updateCard,
    deleteCard,
    moveCard,
    addLabel,
    removeLabel,
    addMember,
    removeMember,
    archiveCard,
    restoreCard,
    toggleDueDateCompletion,
    setCurrentCard,
    clearError
  }
})