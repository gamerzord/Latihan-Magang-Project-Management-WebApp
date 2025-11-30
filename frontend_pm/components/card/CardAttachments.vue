<template>
  <div class="card-attachments">
    <div class="d-flex align-center mb-3">
      <v-icon class="mr-2">mdi-paperclip</v-icon>
      <h3 class="text-h6">Attachments</h3>
      <v-spacer />
      <v-btn
        size="small"
        variant="text"
        prepend-icon="mdi-plus"
        @click="showUploadDialog = true"
        :loading="attachmentStore.loading"
      >
        Add
      </v-btn>
    </div>

    <v-list class="attachment-list">
      <v-list-item
        v-for="attachment in card.attachments || []"
        :key="attachment.id"
        class="attachment-item"
      >
        <template #prepend>
          <div class="attachment-preview mr-3">
            <v-avatar v-if="isImage(attachment)" rounded size="60" class="attachment-image">
              <v-img :src="attachment.file_url" :alt="attachment.file_name" />
            </v-avatar>
            <v-icon v-else size="40" :color="getFileColor(attachment)">
              {{ getFileIcon(attachment) }}
            </v-icon>
          </div>
        </template>

        <v-list-item-title class="text-body-1 font-weight-medium">
          <a 
            :href="attachment.file_url" 
            target="_blank" 
            class="text-decoration-none attachment-link"
            @click.stop
          >
            {{ attachment.display_text || attachment.file_name }}
          </a>
        </v-list-item-title>
        
        <v-list-item-subtitle class="text-caption">
          <div class="d-flex align-center flex-wrap gap-2">
            <span v-if="attachment.file_size">{{ formatFileSize(attachment.file_size) }}</span>
            <span v-if="attachment.file_size">•</span>
            <span>Added {{ formatDate(attachment.created_at) }}</span>
            <span v-if="attachment.uploader">by {{ attachment.uploader.name }}</span>
            <v-chip v-if="attachment.type === 'link'" size="x-small" color="primary" variant="flat">
              Link
            </v-chip>
          </div>
        </v-list-item-subtitle>

        <template #append>
          <v-btn
            icon="mdi-download"
            size="small"
            variant="text"
            @click="handleDownload(attachment.id)"
            :loading="downloadingId === attachment.id"
          />
          <v-btn
            icon="mdi-delete-outline"
            size="small"
            variant="text"
            color="error"
            @click="handleDelete(attachment.id)"
            :loading="deletingId === attachment.id"
          />
        </template>
      </v-list-item>
    </v-list>

    <!-- Empty State -->
    <div v-if="!card.attachments?.length" class="text-center py-6">
      <v-icon size="64" color="grey-lighten-2" class="mb-2">mdi-paperclip-off</v-icon>
      <p class="text-body-2 text-grey">No attachments yet</p>
      <v-btn
        size="small"
        variant="text"
        color="primary"
        @click="showUploadDialog = true"
      >
        Add your first attachment
      </v-btn>
    </div>

    <!-- Upload Dialog -->
    <v-dialog v-model="showUploadDialog" max-width="500">
      <v-card>
        <v-card-title>Add Attachment</v-card-title>
        
        <v-card-text>
          <v-tabs v-model="attachmentTab" grow>
            <v-tab value="file">File</v-tab>
            <v-tab value="link">Link</v-tab>
          </v-tabs>

          <v-window v-model="attachmentTab">
            <v-window-item value="file">
              <v-file-input
                v-model="fileInput"
                label="Choose file"
                variant="outlined"
                class="mt-4"
                prepend-icon=""
                prepend-inner-icon="mdi-paperclip"
                :show-size="1000"
                :rules="[fileRules.required]"
              />
              <v-text-field
                v-model="fileDisplayText"
                label="Display text (optional)"
                variant="outlined"
                placeholder="Custom display name"
                class="mt-2"
              />
            </v-window-item>

            <v-window-item value="link">
              <v-text-field
                v-model="linkUrl"
                label="URL"
                variant="outlined"
                class="mt-4"
                prepend-inner-icon="mdi-link"
                placeholder="https://example.com"
                :rules="[validators.required, validators.url]"
              />
              <v-text-field
                v-model="linkDisplayText"
                label="Display text (optional)"
                variant="outlined"
                placeholder="Custom display name"
              />
              <v-text-field
                v-model="linkName"
                label="Link name"
                variant="outlined"
                placeholder="Name for the link"
                :rules="[validators.required]"
              />
            </v-window-item>
          </v-window>
        </v-card-text>
        
        <v-card-actions>
          <v-spacer />
          <v-btn @click="showUploadDialog = false">Cancel</v-btn>
          <v-btn
            color="primary"
            :loading="attachmentStore.loading"
            :disabled="!canUpload"
            @click="handleUpload"
          >
            Add Attachment
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
import type { Card, Attachment } from '~/types/models'

interface Props {
  card: Card
}

interface Emits {
  (event: 'refresh'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const attachmentStore = useAttachmentStore()
const uiStore = useUiStore()

const showUploadDialog = ref(false)
const attachmentTab = ref<'file' | 'link'>('file')
const fileInput = ref<File[]>([])
const fileDisplayText = ref('')
const linkUrl = ref('')
const linkDisplayText = ref('')
const linkName = ref('')
const downloadingId = ref<number | null>(null)
const deletingId = ref<number | null>(null)

const validators = {
  required: (value: any) => !!value || 'This field is required',
  url: (value: string) => {
    try {
      new URL(value)
      return true
    } catch {
      return 'Invalid URL'
    }
  }
}

const fileRules = {
  required: (value: File[]) => value.length > 0 || 'File is required'
}

const canUpload = computed(() => {
  if (attachmentTab.value === 'file') {
    return fileInput.value.length > 0
  } else {
    return linkUrl.value.trim().length > 0 && linkName.value.trim().length > 0
  }
})

const isImage = (attachment: Attachment): boolean => {
  return attachment.type === 'file' && attachment.mime_type?.startsWith('image/') === true
}

const getFileIcon = (attachment: Attachment): string => {
  if (attachment.type === 'link') return 'mdi-link-variant'
  
  const mime = attachment.mime_type || ''
  if (mime.startsWith('image/')) return 'mdi-file-image'
  if (mime.startsWith('application/pdf')) return 'mdi-file-pdf-box'
  if (mime.startsWith('application/msword') || mime.includes('wordprocessingml')) return 'mdi-file-word'
  if (mime.startsWith('application/vnd.ms-excel') || mime.includes('spreadsheetml')) return 'mdi-file-excel'
  if (mime.startsWith('application/vnd.ms-powerpoint') || mime.includes('presentationml')) return 'mdi-file-powerpoint'
  if (mime.startsWith('video/')) return 'mdi-file-video'
  if (mime.startsWith('audio/')) return 'mdi-file-music'
  if (mime.startsWith('text/')) return 'mdi-file-document'
  if (mime.includes('zip') || mime.includes('compressed')) return 'mdi-folder-zip'
  
  return 'mdi-file'
}

const getFileColor = (attachment: Attachment): string => {
  if (attachment.type === 'link') return 'primary'
  
  const mime = attachment.mime_type || ''
  if (mime.startsWith('image/')) return 'red'
  if (mime.startsWith('application/pdf')) return 'red'
  if (mime.startsWith('application/msword') || mime.includes('wordprocessingml')) return 'blue'
  if (mime.startsWith('application/vnd.ms-excel') || mime.includes('spreadsheetml')) return 'green'
  if (mime.startsWith('video/')) return 'purple'
  if (mime.startsWith('audio/')) return 'orange'
  
  return 'grey'
}

const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes'
  
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const handleDelete = async (attachmentId: number) => {
  if (!confirm('Are you sure you want to delete this attachment? This action cannot be undone.')) return

  deletingId.value = attachmentId
  try {
    await attachmentStore.deleteAttachment(attachmentId)
    emit('refresh')
    uiStore.showSnackbar('Attachment deleted', 'success')
  } catch (error) {
    uiStore.showSnackbar('Failed to delete attachment', 'error')
  } finally {
    deletingId.value = null
  }
}

const handleDownload = async (attachmentId: number) => {
  downloadingId.value = attachmentId
  try {
    await attachmentStore.downloadAttachment(attachmentId)
  } catch (error) {
    uiStore.showSnackbar('Failed to download attachment', 'error')
  } finally {
    downloadingId.value = null
  }
}

const handleUpload = async () => {
  try {
    if (attachmentTab.value === 'file' && fileInput.value[0]) {
      await attachmentStore.createAttachment({
        card_id: props.card.id,
        type: 'file',
        file: fileInput.value[0],
        display_text: fileDisplayText.value || undefined
      })
    } else if (attachmentTab.value === 'link') {
      await attachmentStore.createAttachment({
        card_id: props.card.id,
        type: 'link',
        url: linkUrl.value,
        name: linkName.value,
        display_text: linkDisplayText.value || undefined
      })
    }
    
    showUploadDialog.value = false
    resetUploadForm()
    emit('refresh')
    uiStore.showSnackbar('Attachment added successfully', 'success')
  } catch (error) {
  }
}

const resetUploadForm = () => {
  fileInput.value = []
  fileDisplayText.value = ''
  linkUrl.value = ''
  linkDisplayText.value = ''
  linkName.value = ''
  attachmentTab.value = 'file'
}

watch(showUploadDialog, (open) => {
  if (!open) {
    resetUploadForm()
  }
})
</script>

<style scoped>
.card-attachments {
  margin-bottom: 24px;
}

.attachment-list {
  background: transparent;
}

.attachment-item {
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 8px;
  margin-bottom: 8px;
  transition: all 0.2s ease;
}

.attachment-item:hover {
  border-color: rgba(0, 0, 0, 0.24);
  background-color: rgba(0, 0, 0, 0.02);
}

.attachment-preview {
  flex-shrink: 0;
}

.attachment-image {
  border: 1px solid rgba(0, 0, 0, 0.1);
}

.attachment-link {
  color: inherit;
  transition: color 0.2s ease;
}

.attachment-link:hover {
  color: primary;
}

.gap-2 {
  gap: 8px;
}

@media (max-width: 768px) {
  .attachment-item {
    padding: 8px;
  }
  
  .attachment-preview {
    margin-right: 12px;
  }
}
</style>