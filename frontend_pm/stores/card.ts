import type { Card, CreateCardRequest, UpdateCardRequest, MoveCardRequest, AddLabelRequest, AddCardMemberRequest } from '~/types/models'

export const useCardStore = defineStore('card', () => {
  const currentCard = ref<Card | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const config = useRuntimeConfig()
  const boardStore = useBoardStore()

  const updateCardInBoardStore = (cardId: number, updates: Partial<Card>) => {
    if (boardStore.currentBoard?.lists) {
      const updatedLists = boardStore.currentBoard.lists.map(list => {
        if (!list.cards) return list

        const cardIndex = list.cards.findIndex(c => c.id === cardId)
        if (cardIndex !== -1) {
          const updatedCards = [...list.cards]
          updatedCards[cardIndex] = { ...updatedCards[cardIndex], ...updates } as Card
          return { ...list, cards: updatedCards }
        }
        return list
      })
      boardStore.updateLocalBoard({ lists: updatedLists })
    }
  }

  const removeCardFromBoardStore = (cardId: number) => {
    if (boardStore.currentBoard?.lists) {
      const updatedLists = boardStore.currentBoard.lists.map(list => {
        if (!list.cards) return list
        return {
          ...list,
          cards: list.cards.filter(c => c.id !== cardId)
        }
      })
      boardStore.updateLocalBoard({ lists: updatedLists })
    }
  }

  const fetchCard = async (id: number) => {
    loading.value = true
    error.value = null
    try {
      currentCard.value = (await $fetch<{ card: Card} >(`${config.public.apiBase}/cards/${id}`)).card
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
      const card = await $fetch<{ card: Card }>(`${config.public.apiBase}/cards`, {
        method: 'POST',
        body: data
      })
      
      if (boardStore.currentBoard?.lists) {
        const updatedLists = boardStore.currentBoard.lists.map(list => {
          if (list.id === data.list_id) {
            const currentCards = list.cards || []
            return { ...list, cards: [...currentCards, card.card ]}
        }
          return list
      })
        boardStore.updateLocalBoard({ lists: updatedLists })
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
      const updated = await $fetch<{ card: Card }>(`${config.public.apiBase}/cards/${id}`, {
        method: 'PUT',
        body: data
      })
      
      if (currentCard.value?.id === id) {
        currentCard.value = { ...currentCard.value, ...updated.card }
      }
      
      updateCardInBoardStore(id, updated.card)
      
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

        const listsWithoutCard = boardStore.currentBoard.lists.map(list => {
          if (!list.cards) return list

          const cardIndex = list.cards.findIndex(c => c.id === id)
          if (cardIndex !== -1) {
            movedCard = list.cards[cardIndex]
            return {
              ...list,
              cards: list.cards.filter(c => c.id !== id)
            }
          }
          return list
        })
        
        
        if (movedCard) {
          const updatedLists = listsWithoutCard.map(list => {
            if (list.id === listId) {
              const updateCard = { ...movedCard!, list_id: listId, position }
              const currentCards = list.cards || []
              return { ...list, cards: [...currentCards, updateCard ] }
            }
            return list
          })
          boardStore.updateLocalBoard({ lists: updatedLists } )
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
      const updated = await $fetch<{ card: Card }>(`${config.public.apiBase}/cards/${cardId}/archive`, {
        method: 'POST'
      })
      
      updateCardInBoardStore(cardId, updated.card)
      if (currentCard.value?.id === cardId) {
        currentCard.value = updated.card
      }
      
      return updated
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to archive card'
      throw err
    }
  }

  const restoreCard = async (cardId: number) => {
    try {
      const updated = await $fetch<{ card: Card }>(`${config.public.apiBase}/cards/${cardId}/restore`, {
        method: 'POST'
      })
      
      updateCardInBoardStore(cardId, updated.card)
      if (currentCard.value?.id === cardId) {
        currentCard.value = updated.card
      }
      
      return updated
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to restore card'
      throw err
    }
  }

  const toggleDueDateCompletion = async (cardId: number) => {
    try {
      const updated = await $fetch<{ card: Card }>(`${config.public.apiBase}/cards/${cardId}/toggle-due`, {
        method: 'POST'
      })
      
      updateCardInBoardStore(cardId, updated.card)
      if (currentCard.value?.id === cardId) {
        currentCard.value = updated.card
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
    currentCard: currentCard,
    loading: loading,
    error: error,
    
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