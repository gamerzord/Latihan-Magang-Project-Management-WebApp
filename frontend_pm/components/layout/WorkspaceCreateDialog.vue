<template>
  <v-dialog
    v-model="dialog"
    max-width="500"
    persistent
    @update:model-value="handleDialogChange"
  >
    <v-card>
      <v-card-title class="d-flex align-center">
        <v-icon class="mr-2">mdi-plus-circle</v-icon>
        Create New Workspace
        <v-spacer />
        <v-btn
          icon="mdi-close"
          variant="text"
          size="small"
          @click="closeDialog"
        />
      </v-card-title>

      <v-card-text class="pa-4">
        <v-form ref="formRef" @submit.prevent="handleSubmit">
          <!-- Workspace Name -->
          <v-text-field
            v-model="form.name"
            label="Workspace Name"
            variant="outlined"
            density="comfortable"
            placeholder="Enter workspace name"
            :rules="nameRules"
            :disabled="workspaceStore.loading"
            class="mb-4"
            autofocus
          />

          <!-- Description -->
          <v-textarea
            v-model="form.description"
            label="Description (optional)"
            variant="outlined"
            density="comfortable"
            placeholder="Describe what this workspace is for..."
            rows="3"
            :counter="255"
            :rules="descriptionRules"
            :disabled="workspaceStore.loading"
            class="mb-4"
          />

          <!-- Visibility -->
          <div class="visibility-section">
            <p class="text-caption font-weight-medium text-grey mb-2">
              Visibility
            </p>
            <v-radio-group
              v-model="form.visibility"
              hide-details
              :disabled="workspaceStore.loading"
            >
              <v-radio
                value="private"
                color="primary"
              >
                <template #label>
                  <div class="d-flex flex-column">
                    <span class="text-body-2 font-weight-medium">Private</span>
                    <span class="text-caption text-grey">
                      Only invited members can see and access
                    </span>
                  </div>
                </template>
              </v-radio>

              <v-radio
                value="workspace"
                color="primary"
              >
                <template #label>
                  <div class="d-flex flex-column">
                    <span class="text-body-2 font-weight-medium">Workspace</span>
                    <span class="text-caption text-grey">
                      All workspace members can see and access
                    </span>
                  </div>
                </template>
              </v-radio>

              <v-radio
                value="public"
                color="primary"
              >
                <template #label>
                  <div class="d-flex flex-column">
                    <span class="text-body-2 font-weight-medium">Public</span>
                    <span class="text-caption text-grey">
                      Anyone with the link can view (read-only)
                    </span>
                  </div>
                </template>
              </v-radio>
            </v-radio-group>
          </div>

          <!-- Form Error -->
          <v-alert
            v-if="workspaceStore.error"
            type="error"
            variant="tonal"
            density="compact"
            class="mt-4"
          >
            {{ workspaceStore.error }}
          </v-alert>
        </v-form>
      </v-card-text>

      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn
          variant="text"
          @click="closeDialog"
          :disabled="workspaceStore.loading"
        >
          Cancel
        </v-btn>
        <v-btn
          color="primary"
          :loading="workspaceStore.loading"
          :disabled="!isFormValid"
          @click="handleSubmit"
          prepend-icon="mdi-plus"
        >
          Create Workspace
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
interface Props {
  modelValue: boolean
}

interface Emits {
  (event: 'update:modelValue', value: boolean): void
  (event: 'workspace-created', workspace: any): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const workspaceStore = useWorkspaceStore()
const uiStore = useUiStore()
const formRef = ref()

const form = reactive({
  name: '',
  description: '',
  visibility: 'private' as 'private' | 'workspace' | 'public'
})

const dialog = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const isFormValid = computed(() => {
  return form.name.trim().length > 0 && form.name.trim().length <= 100
})

const nameRules = [
  (v: string) => !!v?.trim() || 'Workspace name is required',
  (v: string) => v.length <= 100 || 'Name must be less than 100 characters',
  (v: string) => v.length >= 2 || 'Name must be at least 2 characters'
]

const descriptionRules = [
  (v: string) => !v || v.length <= 255 || 'Description must be less than 255 characters'
]

const resetForm = () => {
  form.name = ''
  form.description = ''
  form.visibility = 'private'
  workspaceStore.clearError()
}

const closeDialog = () => {
  dialog.value = false
  resetForm()
}

const handleDialogChange = (value: boolean) => {
  if (!value) {
    resetForm()
  }
}

const handleSubmit = async () => {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  try {
    const workspace = await workspaceStore.createWorkspace({
      name: form.name.trim(),
      description: form.description.trim() || undefined,
      visibility: form.visibility
    })

    uiStore.showSnackbar('Workspace created successfully!', 'success')
    emit('workspace-created', workspace)
    closeDialog()
  } catch (error) {
  }
}

watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    workspaceStore.clearError()
  }
})

watch(dialog, (newValue) => {
  if (newValue) {
    nextTick(() => {
      const input = document.querySelector('input[autofocus]')
      if (input instanceof HTMLInputElement) {
        input.focus()
      }
    })
  }
})
</script>

<style scoped>
.visibility-section {
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 8px;
  padding: 16px;
  background: rgba(0, 0, 0, 0.02);
}

:deep(.v-radio-group .v-radio) {
  align-items: flex-start;
  margin-bottom: 12px;
}

:deep(.v-radio-group .v-radio:last-child) {
  margin-bottom: 0;
}

:deep(.v-radio-group .v-radio .v-label) {
  padding-top: 2px;
}

@media (max-width: 600px) {
  .visibility-section {
    padding: 12px;
  }
}
</style>