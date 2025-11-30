<template>
  <div class="card-comments">
    <div class="d-flex align-center mb-4">
      <v-icon class="mr-2">mdi-comment-outline</v-icon>
      <h3 class="text-h6">Comments</h3>
      <v-chip v-if="card.comments?.length" size="small" variant="flat" color="primary" class="ml-2">
        {{ card.comments.length }}
      </v-chip>
    </div>

    <!-- Add Comment -->
    <div class="add-comment mb-6">
      <div class="d-flex align-start gap-3">
        <v-avatar color="primary" size="40" class="flex-shrink-0">
          <v-img v-if="userStore.user?.avatar_url" :src="userStore.user.avatar_url" />
          <span v-else class="text-caption font-weight-medium">
            {{ getUserInitials(userStore.user?.name || '') }}
          </span>
        </v-avatar>
        
        <div class="flex-grow-1">
          <v-textarea
            v-model="newComment"
            placeholder="Write a comment..."
            variant="outlined"
            rows="3"
            hide-details
            auto-grow
            :disabled="commentStore.loading"
          />
          <div class="d-flex justify-end mt-2 gap-2">
            <v-btn
              size="small"
              variant="text"
              @click="clearComment"
              :disabled="!newComment.trim() || commentStore.loading"
            >
              Cancel
            </v-btn>
            <v-btn
              color="primary"
              size="small"
              :disabled="!newComment.trim()"
              :loading="commentStore.loading"
              @click="handleAddComment"
            >
              Comment
            </v-btn>
          </div>
        </div>
      </div>
    </div>

    <!-- Comments List -->
    <div v-if="card.comments?.length" class="comments-list">
      <v-list class="transparent">
        <v-list-item
          v-for="comment in sortedComments"
          :key="comment.id"
          class="comment-item px-0"
        >
          <template #prepend>
            <v-avatar color="primary" size="40" class="flex-shrink-0">
              <v-img v-if="comment.user?.avatar_url" :src="comment.user.avatar_url" />
              <span v-else class="text-caption font-weight-medium">
                {{ getUserInitials(comment.user?.name || '') }}
              </span>
            </v-avatar>
          </template>

          <div class="comment-content flex-grow-1">
            <div class="d-flex align-center justify-space-between mb-1">
              <div class="d-flex align-center flex-wrap gap-2">
                <span class="font-weight-medium text-body-2">{{ comment.user?.name }}</span>
                <span class="text-caption text-grey">
                  {{ formatDate(comment.created_at) }}
                </span>
                <v-chip 
                  v-if="comment.edited_at" 
                  size="x-small" 
                  variant="outlined" 
                  color="grey"
                >
                  edited
                </v-chip>
              </div>

              <v-menu v-if="comment.user_id === userStore.user?.id">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    icon="mdi-dots-horizontal"
                    size="small"
                    variant="text"
                    class="comment-menu-btn"
                  />
                </template>
                <v-list density="compact">
                  <v-list-item @click="startEdit(comment)">
                    <template #prepend>
                      <v-icon size="small">mdi-pencil</v-icon>
                    </template>
                    <v-list-item-title>Edit</v-list-item-title>
                  </v-list-item>
                  <v-list-item @click="handleDeleteComment(comment.id)">
                    <template #prepend>
                      <v-icon size="small" color="error">mdi-delete</v-icon>
                    </template>
                    <v-list-item-title class="text-error">Delete</v-list-item-title>
                  </v-list-item>
                </v-list>
              </v-menu>
            </div>

            <div v-if="editingCommentId === comment.id" class="edit-comment">
              <v-textarea
                v-model="editingText"
                variant="outlined"
                rows="3"
                hide-details
                auto-grow
                :disabled="commentStore.loading"
              />
              <div class="d-flex gap-2 mt-2">
                <v-btn 
                  color="primary" 
                  size="small" 
                  @click="handleEditComment"
                  :loading="commentStore.loading"
                >
                  Save
                </v-btn>
                <v-btn 
                  size="small" 
                  variant="text"
                  @click="cancelEdit"
                  :disabled="commentStore.loading"
                >
                  Cancel
                </v-btn>
              </div>
            </div>

            <div v-else class="comment-text text-body-2">
              {{ comment.text }}
            </div>
          </div>
        </v-list-item>
      </v-list>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-8">
      <v-icon size="64" color="grey-lighten-2" class="mb-2">mdi-comment-outline</v-icon>
      <p class="text-body-2 text-grey mb-4">No comments yet</p>
      <p class="text-caption text-grey">
        Be the first to comment on this card
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Card, Comment } from '~/types/models'

interface Props {
  card: Card
}

interface Emits {
  (event: 'refresh'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const userStore = useUserStore()
const commentStore = useCommentStore()
const uiStore = useUiStore()

const newComment = ref('')
const editingCommentId = ref<number | null>(null)
const editingText = ref('')

const sortedComments = computed(() => {
  if (!props.card.comments) return []
  return [...props.card.comments].sort((a, b) => 
    new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
  )
})

const getUserInitials = (name: string): string => {
  return name
    .split(' ')
    .map(part => part.charAt(0).toUpperCase())
    .join('')
    .slice(0, 2)
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / (1000 * 60))
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))

  if (diffMins < 1) return 'Just now'
  if (diffMins < 60) return `${diffMins}m ago`
  if (diffHours < 24) return `${diffHours}h ago`
  if (diffDays < 7) return `${diffDays}d ago`
  
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
  })
}

const handleAddComment = async () => {
  if (!newComment.value.trim()) return

  try {
    await commentStore.createComment({
      card_id: props.card.id,
      text: newComment.value.trim()
    })
    newComment.value = ''
    emit('refresh')
    uiStore.showSnackbar('Comment added', 'success')
  } catch (error) {
  }
}

const startEdit = (comment: Comment) => {
  editingCommentId.value = comment.id
  editingText.value = comment.text
}

const cancelEdit = () => {
  editingCommentId.value = null
  editingText.value = ''
}

const handleEditComment = async () => {
  if (!editingCommentId.value || !editingText.value.trim()) return

  try {
    await commentStore.updateComment(editingCommentId.value, {
      text: editingText.value.trim()
    })
    editingCommentId.value = null
    editingText.value = ''
    emit('refresh')
    uiStore.showSnackbar('Comment updated', 'success')
  } catch (error) {
  }
}

const handleDeleteComment = async (commentId: number) => {
  if (!confirm('Are you sure you want to delete this comment? This action cannot be undone.')) return

  try {
    await commentStore.deleteComment(commentId)
    emit('refresh')
    uiStore.showSnackbar('Comment deleted', 'success')
  } catch (error) {
    uiStore.showSnackbar('Failed to delete comment', 'error')
  }
}

const clearComment = () => {
  newComment.value = ''
}

onMounted(() => {
  nextTick(() => {
    const textarea = document.querySelector('.add-comment textarea')
    if (textarea instanceof HTMLTextAreaElement) {
      textarea.focus()
    }
  })
})
</script>

<style scoped>
.card-comments {
  margin-bottom: 24px;
}

.add-comment {
  background: rgba(0, 0, 0, 0.02);
  padding: 16px;
  border-radius: 8px;
  border: 1px solid rgba(0, 0, 0, 0.08);
}

.comments-list {
  max-height: 400px;
  overflow-y: auto;
}

.comment-item {
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  align-items: flex-start;
}

.comment-item:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.comment-content {
  min-width: 0;
}

.comment-text {
  white-space: pre-wrap;
  word-break: break-word;
  line-height: 1.5;
}

.comment-menu-btn {
  opacity: 0;
  transition: opacity 0.2s ease;
}

.comment-item:hover .comment-menu-btn {
  opacity: 1;
}

.edit-comment {
  background: rgba(0, 0, 0, 0.02);
  padding: 12px;
  border-radius: 8px;
  border: 1px solid rgba(0, 0, 0, 0.12);
}

.gap-2 {
  gap: 8px;
}

.gap-3 {
  gap: 12px;
}

.comments-list::-webkit-scrollbar {
  width: 6px;
}

.comments-list::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.04);
  border-radius: 3px;
}

.comments-list::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 3px;
}

.comments-list::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.3);
}

@media (max-width: 768px) {
  .add-comment {
    padding: 12px;
  }
  
  .comment-item {
    padding-bottom: 12px;
    margin-bottom: 12px;
  }
  
  .comment-menu-btn {
    opacity: 1;
  }
}
</style>