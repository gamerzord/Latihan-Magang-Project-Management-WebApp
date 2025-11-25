<template>
  <div class="board-list">
    <!-- List header -->
    <div class="list-header d-flex align-center justify-space-between">
      <v-text-field
        v-if="editing"
        v-model="listTitle"
        density="compact"
        variant="plain"
        hide-details
        autofocus
        @blur="handleUpdateTitle"
        @keyup.enter="handleUpdateTitle"
        @keyup.esc="cancelEdit"
      />
      <h3 v-else class="text-subtitle-1 font-weight-bold" @dblclick="editing = true">
        {{ list.title }}
      </h3>

      <v-menu>
        <template #activator="{ props }">
          <v-btn 
            v-bind="props" 
            icon 
            size="small" 
            variant="text"
            class="list-menu-btn"
          >
            <v-icon>mdi-dots-horizontal</v-icon>
          </v-btn>
        </template>
        
        <v-list density="compact">
          <v-list-item @click="editing = true">
            <template #prepend>
              <v-icon>mdi-pencil</v-icon>
            </template>
            <v-list-item-title>Rename</v-list-item-title>
          </v-list-item>
          <v-list-item @click="handleArchive">
            <template #prepend>
              <v-icon>mdi-archive</v-icon>
            </template>
            <v-list-item-title>Archive</v-list-item-title>
          </v-list-item>
          <v-divider />
          <v-list-item @click="handleDelete">
            <template #prepend>
              <v-icon color="error">mdi-delete</v-icon>
            </template>
            <v-list-item-title class="text-error">Delete</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>
    </div>

    <!-- Cards -->
    <div class="list-cards">
      <Card
        v-for="card in sortedCards"
        :key="card.id"
        :card="card"
        @open="handleOpenCard"
      />
      
      <!-- Add card button -->
      <v-btn
        v-if="!addingCard"
        variant="text"
        block
        @click="addingCard = true"
        class="add-card-btn"
        :disabled="list.archived"
      >
        <v-icon start>mdi-plus</v-icon>
        Add a card
      </v-btn>
      
      <!-- Add card form -->
      <v-card v-else class="pa-2 add-card-form">
        <v-textarea
          v-model="newCardTitle"
          placeholder="Enter card title..."
          rows="2"
          variant="outlined"
          density="compact"
          hide-details
          autofocus
          @keyup.enter="handleAddCard"
          @keyup.esc="cancelAddCard"
        />
        <div class="d-flex mt-2 gap-2">
          <v-btn 
            color="primary" 
            size="small" 
            @click="handleAddCard"
            :disabled="!newCardTitle.trim()"
            :loading="cardStore.loading"
          >
            Add Card
          </v-btn>
          <v-btn 
            size="small" 
            variant="text" 
            @click="cancelAddCard"
          >
            Cancel
          </v-btn>
        </div>
      </v-card>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { List, Card } from '~/types/models'

interface Props {
  list: List
}

const props = defineProps<Props>()

const listStore = useListStore()
const cardStore = useCardStore()
const uiStore = useUiStore()

const editing = ref(false)
const listTitle = ref(props.list.title)
const addingCard = ref(false)
const newCardTitle = ref('')

const sortedCards = computed(() => {
  if (!props.list.cards?.length) return []
  return [...props.list.cards].sort((a, b) => a.position - b.position)
})

const handleUpdateTitle = async () => {
  if (!listTitle.value.trim()) {
    listTitle.value = props.list.title
    editing.value = false
    return
  }

  if (listTitle.value === props.list.title) {
    editing.value = false
    return
  }
  
  try {
    await listStore.updateList(props.list.id, { 
      title: listTitle.value.trim() 
    })
    editing.value = false
  } catch (error) {
    uiStore.showSnackbar('Failed to update list', 'error')
    listTitle.value = props.list.title
    editing.value = false
  }
}

const cancelEdit = () => {
  listTitle.value = props.list.title
  editing.value = false
}

const handleArchive = async () => {
  try {
    await listStore.updateList(props.list.id, { archived: true })
    uiStore.showSnackbar('List archived', 'success')
  } catch (error) {
    uiStore.showSnackbar('Failed to archive list', 'error')
  }
}

const handleDelete = async () => {
  if (!confirm('Are you sure you want to delete this list and all its cards? This action cannot be undone.')) return
  
  try {
    await listStore.deleteList(props.list.id)
    uiStore.showSnackbar('List deleted', 'success')
  } catch (error) {
    uiStore.showSnackbar('Failed to delete list', 'error')
  }
}

const handleAddCard = async () => {
  if (!newCardTitle.value.trim()) return
  
  try {
    await cardStore.createCard({
      list_id: props.list.id,
      title: newCardTitle.value.trim()
    })
    newCardTitle.value = ''
    addingCard.value = false
  } catch (error) {
    uiStore.showSnackbar('Failed to create card', 'error')
  }
}

const cancelAddCard = () => {
  addingCard.value = false
  newCardTitle.value = ''
}

const handleOpenCard = (card: Card) => {
  uiStore.openCardModal(card.id)
}

watch(() => props.list, (newList) => {
  listTitle.value = newList.title
  editing.value = false
  addingCard.value = false
  newCardTitle.value = ''
})
</script>

<style scoped>
.board-list {
  background-color: #ebecf0;
  border-radius: 8px;
  width: 272px;
  min-width: 272px;
  max-height: calc(100vh - 120px);
  display: flex;
  flex-direction: column;
  padding: 8px;
  transition: all 0.2s ease;
}

.board-list:hover {
  background-color: #f1f2f4;
}

.list-header {
  padding: 8px;
  margin-bottom: 8px;
  min-height: 40px;
}

.list-header h3 {
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  transition: background-color 0.2s ease;
  flex: 1;
  margin-right: 8px;
}

.list-header h3:hover {
  background-color: rgba(0, 0, 0, 0.04);
}

.list-menu-btn {
  opacity: 0;
  transition: opacity 0.2s ease;
}

.board-list:hover .list-menu-btn {
  opacity: 1;
}

.list-cards {
  flex: 1;
  overflow-y: auto;
  padding: 0 4px;
  margin: 0 -4px;
}

.list-cards::-webkit-scrollbar {
  width: 8px;
}

.list-cards::-webkit-scrollbar-track {
  background: transparent;
}

.list-cards::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

.list-cards::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

.add-card-btn {
  justify-content: flex-start;
  padding-left: 12px;
}

.add-card-form {
  border: 1px solid rgba(0, 0, 0, 0.1);
}

.gap-2 {
  gap: 8px;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .board-list {
    width: 100%;
    min-width: unset;
    max-height: unset;
    margin-bottom: 16px;
  }
  
  .list-menu-btn {
    opacity: 1;
  }
}
</style>