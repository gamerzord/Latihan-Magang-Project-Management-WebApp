<template>
  <div class="add-list">
    <v-card 
      v-if="!adding" 
      class="add-list-button" 
      variant="flat"
      @click="adding = true"
    >
      <v-card-text class="d-flex align-center pa-4">
        <v-icon start>mdi-plus</v-icon>
        <span class="text-body-1">Add a list</span>
      </v-card-text>
    </v-card>

    <v-card v-else class="add-list-form" variant="flat">
      <v-card-text class="pa-3">
        <v-text-field
          v-model="listTitle"
          placeholder="Enter list title..."
          variant="outlined"
          density="compact"
          hide-details
          autofocus
          @keyup.enter="handleAdd"
          @keyup.esc="cancel"
          :disabled="listStore.loading"
        />

        <div class="d-flex mt-3 gap-2">
          <v-btn
            color="primary"
            size="small"
            :disabled="!listTitle.trim()"
            :loading="listStore.loading"
            @click="handleAdd"
          >
            Add List
          </v-btn>
          <v-btn 
            size="small" 
            variant="text"
            @click="cancel"
            :disabled="listStore.loading"
          >
            Cancel
          </v-btn>
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup lang="ts">
interface Props {
  boardId: number
}

const props = defineProps<Props>()

const listStore = useListStore()
const uiStore = useUiStore()

const adding = ref(false)
const listTitle = ref('')

const handleAdd = async () => {
  if (!listTitle.value.trim()) return

  try {
    await listStore.createList({
      board_id: props.boardId,
      title: listTitle.value.trim()
    })
    listTitle.value = ''
    adding.value = false
  } catch (error) {
  }
}

const cancel = () => {
  adding.value = false
  listTitle.value = ''
}

watch(() => props.boardId, () => {
  adding.value = false
  listTitle.value = ''
})

watch(adding, (newValue) => {
  if (newValue) {
    nextTick(() => {
      const input = document.querySelector('.add-list-form input')
      if (input instanceof HTMLInputElement) {
        input.focus()
      }
    })
  }
})
</script>

<style scoped>
.add-list {
  min-width: 272px;
  width: 272px;
  flex-shrink: 0;
}

.add-list-button {
  background: rgba(255, 255, 255, 0.2);
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px dashed rgba(255, 255, 255, 0.3);
}

.add-list-button:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: translateY(-1px);
}

.add-list-form {
  background: #ebecf0;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.gap-2 {
  gap: 8px;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .add-list {
    min-width: 250px;
    width: 250px;
  }
}

@media (max-width: 600px) {
  .add-list {
    min-width: 100%;
    width: 100%;
    margin-bottom: 8px;
  }
}
</style>