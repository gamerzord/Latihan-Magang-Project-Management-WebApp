import type { Comment, CreateCommentRequest, UpdateCommentRequest } from '~/types/models'

export const useCommentStore = defineStore('comment', () => {
  const loading = ref(false)
  const error = ref<string | null>(null)
  const config = useRuntimeConfig()

  const createComment = async (data: CreateCommentRequest) => {
    loading.value = true
    error.value = null
    try {
      const response = await $fetch<{ comment: Comment }>(`${config.public.apiBase}/comments`, {
        method: 'POST',
        body: data
      })
      return response.comment
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to create comment'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateComment = async (id: number, data: UpdateCommentRequest) => {
    loading.value = true
    error.value = null
    try {
      const response = await $fetch<{ comment: Comment }>(`${config.public.apiBase}/comments/${id}`, {
        method: 'PUT',
        body: data
      })
      return response.comment
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to update comment'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteComment = async (id: number) => {
    loading.value = true
    error.value = null
    try {
      await $fetch(`${config.public.apiBase}/comments/${id}`, {
        method: 'DELETE'
      })
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to delete comment'
      throw err
    } finally {
      loading.value = false
    }
  }

  const getCardComments = async (cardId: number) => {
    loading.value = true
    error.value = null
    try {
      const response = await $fetch<{ comments: Comment[] }>(`${config.public.apiBase}/cards/${cardId}/comments`)
      return response.comments
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to fetch comments'
      throw err
    } finally {
      loading.value = false
    }
  }

  const clearError = () => {
    error.value = null
  }

  return {
    loading: loading,
    error: error,
    
    createComment,
    updateComment,
    deleteComment,
    getCardComments,
    clearError
  }
})