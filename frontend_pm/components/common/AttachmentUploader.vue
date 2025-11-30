<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      <v-icon class="mr-2">mdi-paperclip</v-icon>
      Add Attachment
      <v-spacer />
      <v-btn
        icon="mdi-close"
        variant="text"
        size="small"
        @click="$emit('close')"
      />
    </v-card-title>

    <v-card-text class="pa-4">
      <!-- Tabs for File/Link -->
      <v-tabs v-model="tab" grow class="mb-4">
        <v-tab value="file" class="text-capitalize">
          <v-icon size="small" class="mr-2">mdi-file-upload</v-icon>
          Upload File
        </v-tab>
        <v-tab value="link" class="text-capitalize">
          <v-icon size="small" class="mr-2">mdi-link</v-icon>
          Add Link
        </v-tab>
      </v-tabs>

      <v-window v-model="tab">
        <!-- File Upload Tab -->
        <v-window-item value="file">
          <div class="file-upload-area">
            <v-file-input
              v-model="file"
              label="Choose a file or drop it here"
              variant="outlined"
              density="comfortable"
              prepend-icon=""
              prepend-inner-icon="mdi-paperclip"
              :hint="`Maximum file size: ${maxFileSizeMB}MB`"
              persistent-hint
              :show-size="1000"
              :rules="fileRules"
              :loading="attachmentStore.loading"
              @change="handleFileSelect"
              @click:clear="clearFile"
            />

            <!-- File Preview -->
            <div v-if="file.length > 0 && file[0]" class="file-preview mt-3">
              <v-card variant="outlined" class="pa-3">
                <div class="d-flex align-center gap-3">
                  <v-avatar size="48" :color="getFileColor(file[0])" rounded>
                    <v-icon :color="getFileIconColor(file[0])">
                      {{ getFileIcon(file[0]) }}
                    </v-icon>
                  </v-avatar>
                  <div class="flex-grow-1">
                    <p class="text-body-2 font-weight-medium mb-1">
                      {{ file[0].name }}
                    </p>
                    <p class="text-caption text-grey">
                      {{ formatFileSize(file[0].size) }}
                    </p>
                  </div>
                  <v-btn
                    icon="mdi-close"
                    size="small"
                    variant="text"
                    @click="clearFile"
                  />
                </div>
              </v-card>
            </div>

            <!-- Supported Formats -->
            <div class="supported-formats mt-3">
              <p class="text-caption text-grey mb-1">Supported formats:</p>
              <div class="d-flex flex-wrap gap-1">
                <v-chip
                  v-for="format in supportedFormats"
                  :key="format"
                  size="x-small"
                  variant="outlined"
                  color="grey"
                >
                  {{ format }}
                </v-chip>
              </div>
            </div>
          </div>
        </v-window-item>

        <!-- Link Tab -->
        <v-window-item value="link">
          <div class="link-input-area">
            <v-text-field
              v-model="linkUrl"
              label="URL"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi-link"
              placeholder="https://example.com"
              :rules="linkUrlRules"
              class="mb-3"
            />

            <v-text-field
              v-model="linkName"
              label="Link name"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi-format-title"
              placeholder="Enter a descriptive name"
              :rules="linkNameRules"
              class="mb-3"
            />

            <v-text-field
              v-model="linkDisplayText"
              label="Display text (optional)"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi-text"
              placeholder="Custom text to display"
              hint="If empty, the link name will be used"
              persistent-hint
            />

            <!-- Link Preview -->
            <div v-if="linkUrl && isValidUrl(linkUrl)" class="link-preview mt-3">
              <v-card variant="outlined" class="pa-3">
                <div class="d-flex align-center gap-3">
                  <v-avatar size="48" color="primary" rounded>
                    <v-icon color="white">mdi-link</v-icon>
                  </v-avatar>
                  <div class="flex-grow-1">
                    <p class="text-body-2 font-weight-medium mb-1">
                      {{ linkName || 'Untitled Link' }}
                    </p>
                    <p class="text-caption text-grey text-truncate">
                      {{ linkUrl }}
                    </p>
                  </div>
                </div>
              </v-card>
            </div>
          </div>
        </v-window-item>
      </v-window>
    </v-card-text>

    <v-card-actions class="pa-4">
      <v-spacer />
      <v-btn 
        variant="text" 
        @click="$emit('close')"
        :disabled="attachmentStore.loading"
      >
        Cancel
      </v-btn>
      <v-btn
        color="primary"
        :loading="attachmentStore.loading"
        :disabled="!canSubmit"
        @click="handleSubmit"
        prepend-icon="mdi-plus"
      >
        Add Attachment
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
interface Props {
  cardId: number
}

interface Emits {
  (event: 'close'): void
  (event: 'refresh'): void
  (event: 'attachment-added'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const attachmentStore = useAttachmentStore()
const uiStore = useUiStore()

const maxFileSizeMB = 10
const maxFileSizeBytes = maxFileSizeMB * 1024 * 1024

const supportedFormats = [
  'Images', 'PDF', 'Word', 'Excel', 'PowerPoint', 
  'Text', 'ZIP', 'Video', 'Audio'
]

const tab = ref<'file' | 'link'>('file')
const file = ref<File[]>([])
const linkUrl = ref('')
const linkName = ref('')
const linkDisplayText = ref('')

const fileRules = [
  (value: File[]) => value.length > 0 || 'Please select a file',
  (value: File[]) => {
    if (value.length > 0 && value[0]) {
      return value[0].size <= maxFileSizeBytes || `File size must be less than ${maxFileSizeMB}MB`
    }
    return true
  }
]

const linkUrlRules = [
  (value: string) => !!value?.trim() || 'URL is required',
  (value: string) => isValidUrl(value) || 'Please enter a valid URL'
]

const linkNameRules = [
  (value: string) => !!value?.trim() || 'Link name is required',
  (value: string) => value.length <= 255 || 'Link name must be less than 255 characters'
]

const canSubmit = computed(() => {
  if (tab.value === 'file') {
    return file.value.length > 0 && file.value[0] && file.value[0].size <= maxFileSizeBytes
  } else {
    return linkUrl.value.trim() !== '' && 
           linkName.value.trim() !== '' && 
           isValidUrl(linkUrl.value)
  }
})

function isValidUrl(url: string): boolean {
  try {
    const parsedUrl = new URL(url)
    return ['http:', 'https:'].includes(parsedUrl.protocol)
  } catch {
    return false
  }
}

function getFileIcon(file: File): string {
  const type = file.type.toLowerCase()
  
  if (type.startsWith('image/')) return 'mdi-file-image'
  if (type.includes('pdf')) return 'mdi-file-pdf-box'
  if (type.includes('word') || type.includes('document')) return 'mdi-file-word'
  if (type.includes('excel') || type.includes('spreadsheet')) return 'mdi-file-excel'
  if (type.includes('powerpoint') || type.includes('presentation')) return 'mdi-file-powerpoint'
  if (type.startsWith('video/')) return 'mdi-file-video'
  if (type.startsWith('audio/')) return 'mdi-file-music'
  if (type.includes('zip') || type.includes('compressed')) return 'mdi-folder-zip'
  if (type.startsWith('text/')) return 'mdi-file-document'
  
  return 'mdi-file'
}

function getFileColor(file: File): string {
  const type = file.type.toLowerCase()
  
  if (type.startsWith('image/')) return 'red-lighten-4'
  if (type.includes('pdf')) return 'red-lighten-4'
  if (type.includes('word') || type.includes('document')) return 'blue-lighten-4'
  if (type.includes('excel') || type.includes('spreadsheet')) return 'green-lighten-4'
  if (type.includes('powerpoint') || type.includes('presentation')) return 'orange-lighten-4'
  if (type.startsWith('video/')) return 'purple-lighten-4'
  if (type.startsWith('audio/')) return 'pink-lighten-4'
  
  return 'grey-lighten-3'
}

function getFileIconColor(file: File): string {
  const type = file.type.toLowerCase()
  
  if (type.startsWith('image/')) return 'red'
  if (type.includes('pdf')) return 'red'
  if (type.includes('word') || type.includes('document')) return 'blue'
  if (type.includes('excel') || type.includes('spreadsheet')) return 'green'
  if (type.includes('powerpoint') || type.includes('presentation')) return 'orange'
  if (type.startsWith('video/')) return 'purple'
  if (type.startsWith('audio/')) return 'pink'
  
  return 'grey'
}

function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 Bytes'
  
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const handleFileSelect = (event: Event) => {
  const input = event.target as HTMLInputElement
  if (input.files && input.files.length > 0) {
    const selectedFile = input.files[0]
    if (!selectedFile) return

    if (tab.value === 'file' && !linkName.value && selectedFile.name) {
      linkName.value = selectedFile.name.replace(/\.[^/.]+$/, "")
    }
    
    file.value = [selectedFile]
  }
}

const clearFile = () => {
  file.value = []
}

const handleSubmit = async () => {
  try {
    if (tab.value === 'file' && file.value[0]) {
      await attachmentStore.createAttachment({
        card_id: props.cardId,
        type: 'file',
        file: file.value[0],
        display_text: linkDisplayText.value || undefined
      })
    } else if (tab.value === 'link') {
      await attachmentStore.createAttachment({
        card_id: props.cardId,
        type: 'link',
        url: linkUrl.value,
        name: linkName.value,
        display_text: linkDisplayText.value || undefined
      })
    }

    emit('attachment-added')
    emit('refresh')
    uiStore.showSnackbar('Attachment added successfully', 'success')
    emit('close')
  } catch (error) {
  }
}

watch(tab, (newTab) => {
  if (newTab === 'file') {
    linkUrl.value = ''
    linkName.value = ''
    linkDisplayText.value = ''
  } else {
    file.value = []
  }
})

watch(linkUrl, (newUrl) => {
  if (newUrl && !linkName.value) {
    try {
      const url = new URL(newUrl)
      linkName.value = url.hostname.replace('www.', '')
    } catch {
    }
  }
})
</script>

<style scoped>
.file-upload-area,
.link-input-area {
  min-height: 200px;
}

.file-preview,
.link-preview {
  border-radius: 8px;
}

.supported-formats {
  border: 1px dashed rgba(0, 0, 0, 0.2);
  border-radius: 8px;
  padding: 12px;
  background: rgba(0, 0, 0, 0.02);
}

.gap-1 {
  gap: 4px;
}

.gap-3 {
  gap: 12px;
}

:deep(.v-file-input .v-field__input) {
  cursor: pointer;
}

:deep(.v-file-input .v-field__input:hover) {
  background-color: rgba(0, 0, 0, 0.02);
}

@media (max-width: 768px) {
  .file-upload-area,
  .link-input-area {
    min-height: 150px;
  }
}
</style>