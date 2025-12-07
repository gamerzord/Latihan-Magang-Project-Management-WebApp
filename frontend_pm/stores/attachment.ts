import type { Attachment, CreateAttachmentRequest, UpdateAttachmentRequest, BulkDestroyAttachmentsRequest, AttachmentStats } from '~/types/models'

export const useAttachmentStore = defineStore('attachment', () => {
  const loading = ref(false)
  const error = ref<string | null>(null)
  const config = useRuntimeConfig()

  const createAttachment = async (data: CreateAttachmentRequest) => {
    loading.value = true
    error.value = null
    try {
      if (data.type === 'file' && data.file) {
        const formData = new FormData()
        formData.append('card_id', data.card_id.toString())
        formData.append('type', 'file')
        formData.append('file', data.file)
        if (data.display_text) {
          formData.append('display_text', data.display_text)
        }

        const response = await $fetch<{ attachment: Attachment }>(`${config.public.apiBase}/attachments`, {
          method: 'POST',
          body: formData,
          headers: {}
        })
        return response.attachment
      } else if (data.type === 'link' && data.url) {
        const response = await $fetch<{ attachment: Attachment }>(`${config.public.apiBase}/attachments`, {
          method: 'POST',
          body: {
            card_id: data.card_id,
            type: 'link',
            url: data.url,
            name: data.name || data.url,
            display_text: data.display_text
          }
        })
        return response.attachment
      } else {
        throw new Error('Invalid attachment data')
      }
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to create attachment'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteAttachment = async (id: number) => {
    try {
      await $fetch(`${config.public.apiBase}/attachments/${id}`, {
        method: 'DELETE'
      })
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to delete attachment'
      throw err
    }
  }

  const updateAttachment = async (id: number, data: UpdateAttachmentRequest) => {
    try {
      const response = await $fetch<{ attachment: Attachment }>(`${config.public.apiBase}/attachments/${id}`, {
        method: 'PUT',
        body: data
      })
      return response.attachment
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to update attachment'
      throw err
    }
  }

  const getCardAttachments = async (cardId: number) => {
    loading.value = true
    error.value = null
    try {
      const response = await $fetch<{ attachments: Attachment[] }>(`${config.public.apiBase}/cards/${cardId}/attachments`)
      return response.attachments
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to fetch attachments'
      throw err
    } finally {
      loading.value = false
    }
  }

  const bulkDeleteAttachments = async (data: BulkDestroyAttachmentsRequest) => {
    try {
      const response = await $fetch<{ message: string }>(`${config.public.apiBase}/attachments/bulk-destroy`, {
        method: 'POST',
        body: data
      })
      return response.message
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to delete attachments'
      throw err
    }
  }

  const getAttachmentStats = async (cardId: number) => {
    try {
      const response = await $fetch<{ stats: AttachmentStats }>(`${config.public.apiBase}/cards/${cardId}/attachment/stats`)
      return response.stats
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to fetch attachment stats'
      throw err
    }
  }

  const downloadAttachment = async (id: number) => {
    try {
      window.open(`${config.public.apiBase}/attachments/${id}/download`, '_blank')
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to download attachment'
      throw err
    }
  }

  const clearError = () => {
    error.value = null
  }

  return {
    loading: loading,
    error: error,
    
    createAttachment,
    deleteAttachment,
    updateAttachment,
    getCardAttachments,
    bulkDeleteAttachments,
    getAttachmentStats,
    downloadAttachment,
    clearError
  }
})