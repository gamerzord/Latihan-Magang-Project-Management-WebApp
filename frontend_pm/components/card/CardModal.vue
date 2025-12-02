<template>
  <v-dialog
    :model-value="true"
    max-width="800"
    scrollable
    persistent
    @update:model-value="$emit('close')"
  >
    <v-card v-if="card" class="card-modal">
      <!-- Header with cover image if exists -->
      <div
        v-if="coverImage"
        class="card-cover"
        :style="{
          backgroundImage: `url(${coverImage})`
        }"
      />

      <v-card-title class="d-flex align-center pa-4">
        <v-icon class="mr-2">mdi-card-text-outline</v-icon>
        <v-text-field
          v-if="editingTitle"
          v-model="cardTitle"
          density="compact"
          variant="plain"
          hide-details
          autofocus
          class="title-input"
          @blur="handleUpdateTitle"
          @keyup.enter="handleUpdateTitle"
          @keyup.esc="cancelEditTitle"
        />
        <span v-else @click="editingTitle = true" class="cursor-pointer text-h6 font-weight-medium">
          {{ card.title }}
        </span>

        <v-spacer />

        <v-btn
          icon="mdi-close"
          variant="text"
          @click="$emit('close')"
        />
      </v-card-title>

      <v-divider />

      <v-card-text class="pa-0">
        <v-row no-gutters>
          <!-- Main Content -->
          <v-col cols="12" md="9" class="pa-4">
            <!-- Labels, Members, Due Date -->
            <div class="d-flex flex-wrap gap-3 mb-4">
              <!-- Labels -->
              <div v-if="card.labels?.length">
                <label class="text-caption text-grey d-block mb-1">Labels</label>
                <CardLabels :card="card" @refresh="fetchCard" />
              </div>

              <!-- Members -->
              <div v-if="card.members?.length">
                <label class="text-caption text-grey d-block mb-1">Members</label>
                <CardMembers :card="card" @refresh="fetchCard" />
              </div>

              <!-- Due Date -->
              <div v-if="card.due_date">
                <label class="text-caption text-grey d-block mb-1">Due Date</label>
                <CardDueDate :card="card" @refresh="fetchCard" />
              </div>
            </div>

            <!-- Description -->
            <CardDescription :card="card" @refresh="fetchCard" />

            <!-- Checklists -->
            <div v-if="card.checklists?.length" class="mb-4">
              <CardChecklist
                v-for="checklist in sortedChecklists"
                :key="checklist.id"
                :checklist="checklist"
                @refresh="fetchCard"
              />
            </div>

            <!-- Attachments -->
            <CardAttachments
              v-if="card.attachments?.length"
              :card="card"
              @refresh="fetchCard"
            />

            <!-- Comments -->
            <CardComments :card="card" @refresh="fetchCard" />
          </v-col>

          <!-- Sidebar Actions -->
          <v-col cols="12" md="3" class="pa-4 bg-grey-lighten-4">
            <div class="text-caption text-grey mb-2">ADD TO CARD</div>

            <v-btn
              block
              variant="outlined"
              prepend-icon="mdi-account"
              class="mb-2 justify-start"
              @click="addMemberDialog = true"
            >
              Members
            </v-btn>

            <v-btn
              block
              variant="outlined"
              prepend-icon="mdi-label"
              class="mb-2 justify-start"
              @click="addLabelDialog = true"
            >
              Labels
            </v-btn>

            <v-btn
              block
              variant="outlined"
              prepend-icon="mdi-checkbox-marked-outline"
              class="mb-2 justify-start"
              @click="addChecklistDialog = true"
            >
              Checklist
            </v-btn>

            <v-btn
              block
              variant="outlined"
              prepend-icon="mdi-clock-outline"
              class="mb-2 justify-start"
              @click="dueDateDialog = true"
            >
              Due Date
            </v-btn>

            <v-btn
              block
              variant="outlined"
              prepend-icon="mdi-attachment"
              class="mb-2 justify-start"
              @click="attachmentDialog = true"
            >
              Attachment
            </v-btn>

            <v-divider class="my-3" />

            <div class="text-caption text-grey mb-2">ACTIONS</div>

            <v-btn
              block
              variant="outlined"
              prepend-icon="mdi-content-copy"
              class="mb-2 justify-start"
              @click="handleCopy"
            >
              Copy
            </v-btn>

            <v-btn
              block
              variant="outlined"
              prepend-icon="mdi-archive-outline"
              class="mb-2 justify-start"
              @click="handleArchive"
            >
              Archive
            </v-btn>

            <v-btn
              block
              variant="outlined"
              color="error"
              prepend-icon="mdi-delete-outline"
              class="justify-start"
              @click="handleDelete"
            >
              Delete
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <div v-else class="d-flex justify-center pa-8">
      <v-progress-circular indeterminate color="primary" />
    </div>

    <!-- Add Member Dialog -->
    <v-dialog v-model="addMemberDialog" max-width="400">
      <CommonMemberSelector
        :card-id="cardId"
        :current-members="card?.members ? Array.from(card.members) : []"
        @close="addMemberDialog = false"
        @refresh="fetchCard"
      />
    </v-dialog>

    <!-- Add Label Dialog -->
    <v-dialog v-model="addLabelDialog" max-width="400">
      <CommonLabelManager
        :board-id="card?.list?.board_id || 0"
        :card-id="cardId"
        @close="addLabelDialog = false"
        @refresh="fetchCard"
      />
    </v-dialog>

    <!-- Add Checklist Dialog -->
    <v-dialog v-model="addChecklistDialog" max-width="400">
      <v-card>
        <v-card-title>Add Checklist</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="checklistTitle"
            label="Checklist Title"
            variant="outlined"
            :rules="[requiredRule]"
            autofocus
            @keyup.enter="handleAddChecklist"
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="addChecklistDialog = false">Cancel</v-btn>
          <v-btn 
            color="primary" 
            @click="handleAddChecklist"
            :disabled="!checklistTitle.trim()"
          >
            Add
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Due Date Dialog -->
    <v-dialog v-model="dueDateDialog" max-width="400">
      <CommonDatePicker
        :card="card"
        @close="dueDateDialog = false"
        @refresh="fetchCard"
      />
    </v-dialog>

    <!-- Attachment Dialog -->
    <v-dialog v-model="attachmentDialog" max-width="500">
      <CommonAttachmentUploader
        :card-id="cardId"
        @close="attachmentDialog = false"
        @refresh="fetchCard"
      />
    </v-dialog>
  </v-dialog>
</template>

<script setup lang="ts">
import type { Card, Checklist } from '~/types/models'

interface Props {
  cardId: number
}

interface Emits {
  (event: 'close'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const cardStore = useCardStore()
const uiStore = useUiStore()

const card = computed(() => cardStore.currentCard)
const loading = computed(() => cardStore.loading)

const editingTitle = ref(false)
const cardTitle = ref('')
const addMemberDialog = ref(false)
const addLabelDialog = ref(false)
const addChecklistDialog = ref(false)
const dueDateDialog = ref(false)
const attachmentDialog = ref(false)
const checklistTitle = ref('Checklist')

const requiredRule = (value: string) => !!value?.trim() || 'This field is required'

const coverImage = computed(() => {
  return card.value?.attachments?.find(a => 
    a.type === 'file' && a.mime_type?.startsWith('image')
  )?.file_url
})

const sortedChecklists = computed(() => {
  if (!card.value?.checklists?.length) return []
  return [...card.value.checklists].sort((a: Checklist, b: Checklist) => a.position - b.position)
})

const fetchCard = async () => {
  await cardStore.fetchCard(props.cardId)
}

const handleUpdateTitle = async () => {
  if (!card.value || !cardTitle.value.trim()) {
    cancelEditTitle()
    return
  }

  if (cardTitle.value === card.value.title) {
    editingTitle.value = false
    return
  }

  try {
    await cardStore.updateCard(props.cardId, { title: cardTitle.value.trim() })
    editingTitle.value = false
  } catch (error) {
    uiStore.showSnackbar('Failed to update title', 'error')
    cancelEditTitle()
  }
}

const cancelEditTitle = () => {
  cardTitle.value = card.value?.title || ''
  editingTitle.value = false
}

const handleAddChecklist = async () => {
  if (!checklistTitle.value.trim()) return

  try {
    await cardStore.createChecklist({
      card_id: props.cardId,
      title: checklistTitle.value.trim()
    })
    addChecklistDialog.value = false
    checklistTitle.value = 'Checklist'
    await fetchCard()
  } catch (error) {
    uiStore.showSnackbar('Failed to add checklist', 'error')
  }
}

const handleCopy = () => {
  uiStore.showSnackbar('Copy feature coming soon!', 'info')
}

const handleArchive = async () => {
  if (!card.value) return
  
  if (!confirm('Are you sure you want to archive this card?')) return
  
  try {
    await cardStore.updateCard(props.cardId, { archived: true })
    emit('close')
  } catch (error) {
    uiStore.showSnackbar('Failed to archive card', 'error')
  }
}

const handleDelete = async () => {
  if (!confirm('Are you sure you want to delete this card? This action cannot be undone.')) return

  try {
    await cardStore.deleteCard(props.cardId)
    emit('close')
  } catch (error) {
    uiStore.showSnackbar('Failed to delete card', 'error')
  }
}

onMounted(() => {
  fetchCard()
})

watch(card, (newCard) => {
  if (newCard) {
    cardTitle.value = newCard.title
  }
})

watch(addChecklistDialog, (open) => {
  if (!open) {
    checklistTitle.value = 'Checklist'
  }
})
</script>

<style scoped>
.card-modal {
  max-height: 90vh;
}

.card-cover {
  height: 160px;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}

.cursor-pointer {
  cursor: pointer;
}

.gap-3 {
  gap: 12px;
}

.title-input :deep(.v-field__input) {
  font-size: 1.25rem;
  font-weight: 600;
}

@media (max-width: 960px) {
  .card-modal {
    max-height: 95vh;
  }
  
  .card-cover {
    height: 120px;
  }
}

@media (max-width: 600px) {
  .v-row {
    flex-direction: column;
  }
  
  .v-col {
    width: 100%;
  }
}
</style>