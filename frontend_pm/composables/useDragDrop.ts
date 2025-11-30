import type { DragData } from '~/types/models'

export const useDragDrop = () => {
  const draggedItem = ref<DragData | null>(null)
  const dragOverList = ref<number | null>(null)
  const isDragging = ref(false)
  const uiStore = useUiStore()
  const cardStore = useCardStore()

  const startDrag = (data: DragData) => {
    draggedItem.value = data
    isDragging.value = true
  }

  const endDrag = () => {
    draggedItem.value = null
    dragOverList.value = null
    isDragging.value = false
  }

  const onDragOver = (listId: number) => {
    dragOverList.value = listId
  }

  const onDragLeave = () => {
    dragOverList.value = null
  }

  const onDrop = async (targetListId: number, targetPosition: number) => {
    if (!draggedItem.value) return

    try {
      if (draggedItem.value.type === 'card') {
        await cardStore.moveCard(draggedItem.value.id, targetListId, targetPosition)
      }
      // You could add list reordering here later:
      // else if (draggedItem.value.type === 'list') {
      //   const listStore = useListStore()
      //   await listStore.reorderLists(/* ... */)
      // }
    } catch (error) {
      console.error('Drag and drop failed:', error)
      uiStore.showSnackbar('Failed to move item', 'error')
    } finally {
      endDrag()
    }
  }

  return {
    draggedItem: draggedItem,
    dragOverList: dragOverList,
    isDragging: isDragging,
    startDrag,
    endDrag,
    onDragOver,
    onDragLeave,
    onDrop,
  }
}