import type { Label, CreateLabelRequest, UpdateLabelRequest, BulkUpdateLabelsRequest, BulkLabelResult } from '~/types/models'

export const useLabelStore = defineStore('label', () => {
  const labels = ref<Label[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const config = useRuntimeConfig()

  const createLabel = async (data: CreateLabelRequest) => {
    loading.value = true
    error.value = null
    try {
      const response = await $fetch<{ label: Label }>(`${config.public.apiBase}/labels`, {
        method: 'POST',
        body: data
      })
      labels.value.push(response.label)
      return response.label
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to create label'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateLabel = async (id: number, data: UpdateLabelRequest) => {
    loading.value = true
    error.value = null
    try {
      const response = await $fetch<{ label: Label }>(`${config.public.apiBase}/labels/${id}`, {
        method: 'PUT',
        body: data
      })
      const index = labels.value.findIndex(l => l.id === id)
      if (index !== -1) {
        labels.value[index] = response.label
      }
      return response.label
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to update label'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteLabel = async (id: number) => {
    loading.value = true
    error.value = null
    try {
      await $fetch(`${config.public.apiBase}/labels/${id}`, {
        method: 'DELETE'
      })
      labels.value = labels.value.filter(l => l.id !== id)
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to delete label'
      throw err
    } finally {
      loading.value = false
    }
  }

  const getBoardLabels = async (boardId: number) => {
    loading.value = true
    error.value = null
    try {
      const response = await $fetch<{ labels: Label[] }>(`${config.public.apiBase}/boards/${boardId}/labels`)
      labels.value = response.labels
      return response.labels
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to fetch labels'
      throw err
    } finally {
      loading.value = false
    }
  }

  const bulkUpdateLabels = async (boardId: number, data: BulkUpdateLabelsRequest) => {
    loading.value = true
    error.value = null
    try {
      const response = await $fetch<{ results: BulkLabelResult[] }>(`${config.public.apiBase}/boards/${boardId}/labels/bulk`, {
        method: 'POST',
        body: data
      })
      
      for (const item of response.results) {
        if (item.action === 'deleted') {
          labels.value = labels.value.filter(l => l.id !== item.id)
          continue
        }

        if (item.action === 'updated') {
          const i = labels.value.findIndex(l => l.id === item.id)
          if (i !== -1 && item.label) labels.value[i] = item.label
          continue
        }

        if (item.action === 'created' && item.label) {
          labels.value.push(item.label)
        }

        return labels.value
      }
    } catch (err: any) {
      error.value = err.data?.message || 'Failed to update labels'
      throw err
    } finally {
      loading.value = false
    }
  }

  const clearError = () => {
    error.value = null
  }

  return {
    labels: labels,
    loading: loading,
    error: error,
    
    createLabel,
    updateLabel,
    deleteLabel,
    getBoardLabels,
    bulkUpdateLabels,
    clearError
  }
})