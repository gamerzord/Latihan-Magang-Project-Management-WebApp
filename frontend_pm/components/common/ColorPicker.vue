<template>
  <div class="color-picker">
    <!-- Preset Colors -->
    <div class="color-section">
      <p class="text-caption font-weight-medium text-grey mb-2">Preset Colors</p>
      <div class="color-grid">
        <div
          v-for="color in presetColors"
          :key="color.value"
          class="color-option"
          :class="{ 
            selected: modelValue === color.value,
            'color-option--named': color.name
          }"
          :style="{ backgroundColor: color.value }"
          :title="color.name"
          @click="handleColorSelect(color.value)"
        >
          <v-icon 
            v-if="modelValue === color.value" 
            size="small" 
            color="white"
            class="color-check"
          >
            mdi-check
          </v-icon>
        </div>
      </div>
    </div>

    <!-- Recent Colors -->
    <div v-if="recentColors.length > 0" class="color-section">
      <p class="text-caption font-weight-medium text-grey mb-2">Recent Colors</p>
      <div class="color-grid">
        <div
          v-for="color in recentColors"
          :key="color"
          class="color-option"
          :class="{ selected: modelValue === color }"
          :style="{ backgroundColor: color }"
          :title="color"
          @click="handleColorSelect(color)"
        >
          <v-icon 
            v-if="modelValue === color" 
            size="small" 
            color="white"
            class="color-check"
          >
            mdi-check
          </v-icon>
        </div>
      </div>
    </div>

    <!-- Custom Color -->
    <div class="color-section">
      <p class="text-caption font-weight-medium text-grey mb-2">Custom Color</p>
      <div class="custom-color-inputs">
        <!-- HEX Input -->
        <v-text-field
          :model-value="modelValue"
          label="HEX Color"
          variant="outlined"
          density="compact"
          prefix="#"
          :rules="hexRules"
          @update:model-value="handleHexInput"
          class="mb-3"
        />

        <!-- Color Preview -->
        <div class="color-preview-container">
          <div 
            class="color-preview"
            :style="{ backgroundColor: modelValue }"
          />
          <span class="text-caption ml-2">{{ modelValue }}</span>
        </div>

        <!-- Color Picker -->
        <v-menu location="bottom">
          <template #activator="{ props }">
            <v-btn
              v-bind="props"
              variant="outlined"
              size="small"
              prepend-icon="mdi-eyedropper"
              class="mt-2"
              block
            >
              Pick Color
            </v-btn>
          </template>
          <v-card width="300">
            <v-card-text class="pa-4">
              <div class="color-picker-popup">
                <input
                  v-model="colorPickerValue"
                  type="color"
                  class="color-input-native"
                  @input="handleNativeColorPick"
                />
                <div class="color-picker-controls">
                  <v-text-field
                    v-model="colorPickerValue"
                    label="Color"
                    variant="outlined"
                    density="compact"
                    prefix="#"
                    @update:model-value="handleColorPickerInput"
                  />
                  <div 
                    class="color-picker-preview"
                    :style="{ backgroundColor: colorPickerValue }"
                  />
                </div>
              </div>
            </v-card-text>
            <v-card-actions>
              <v-spacer />
              <v-btn variant="text" @click="cancelColorPick">Cancel</v-btn>
              <v-btn color="primary" @click="applyColorPick">Apply</v-btn>
            </v-card-actions>
          </v-card>
        </v-menu>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="color-actions">
      <v-btn
        variant="text"
        size="small"
        prepend-icon="mdi-refresh"
        @click="handleRandomColor"
      >
        Random Color
      </v-btn>
      
      <v-btn
        v-if="modelValue !== defaultColor"
        variant="text"
        size="small"
        prepend-icon="mdi-undo"
        @click="handleResetColor"
      >
        Reset to Default
      </v-btn>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  modelValue: string
  colors?: Array<{ name: string, value: string }>
  defaultColor?: string
  showRecent?: boolean
  maxRecentColors?: number
}

interface Emits {
  (event: 'update:modelValue', value: string): void
  (event: 'color-change', value: string): void
}

const props = withDefaults(defineProps<Props>(), {
  colors: () => LABEL_COLORS,
  defaultColor: '#0079bf',
  showRecent: true,
  maxRecentColors: 6
})

const emit = defineEmits<Emits>()

const LABEL_COLORS = [
  { name: 'Green', value: '#61bd4f' },
  { name: 'Yellow', value: '#f2d600' },
  { name: 'Orange', value: '#ff9f1a' },
  { name: 'Red', value: '#eb5a46' },
  { name: 'Purple', value: '#c377e0' },
  { name: 'Blue', value: '#0079bf' },
  { name: 'Sky', value: '#00c2e0' },
  { name: 'Lime', value: '#51e898' },
  { name: 'Pink', value: '#ff78cb' },
  { name: 'Black', value: '#344563' },
]

const recentColors = ref<string[]>([])
const colorPickerValue = ref(props.modelValue)
const isColorPickerOpen = ref(false)

const hexRules = [
  (value: string) => isValidHexColor(value) || 'Please enter a valid HEX color'
]

const presetColors = computed(() => props.colors)

const isValidHexColor = (color: string): boolean => {
  return /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(color)
}

const normalizeHexColor = (color: string): string => {
  if (!color.startsWith('#')) {
    color = '#' + color
  }
  
  if (color.length === 4) {
    color = '#' + color[1] + color[1] + color[2] + color[2] + color[3] + color[3]
  }
  
  return color.toLowerCase()
}

const handleColorSelect = (color: string) => {
  const normalizedColor = normalizeHexColor(color)
  emit('update:modelValue', normalizedColor)
  emit('color-change', normalizedColor)
  addToRecentColors(normalizedColor)
}

const handleHexInput = (value: string) => {
  if (isValidHexColor(value)) {
    const normalizedColor = normalizeHexColor(value)
    emit('update:modelValue', normalizedColor)
    emit('color-change', normalizedColor)
    addToRecentColors(normalizedColor)
  }
}

const handleNativeColorPick = (event: Event) => {
  const input = event.target as HTMLInputElement
  colorPickerValue.value = input.value
}

const handleColorPickerInput = (value: string) => {
  if (isValidHexColor(value)) {
    colorPickerValue.value = normalizeHexColor(value)
  }
}

const applyColorPick = () => {
  if (isValidHexColor(colorPickerValue.value)) {
    const normalizedColor = normalizeHexColor(colorPickerValue.value)
    emit('update:modelValue', normalizedColor)
    emit('color-change', normalizedColor)
    addToRecentColors(normalizedColor)
    isColorPickerOpen.value = false
  }
}

const cancelColorPick = () => {
  colorPickerValue.value = props.modelValue
  isColorPickerOpen.value = false
}

const handleRandomColor = () => {
  const randomColor = '#' + Math.floor(Math.random() * 16777215).toString(16).padStart(6, '0')
  const normalizedColor = normalizeHexColor(randomColor)
  emit('update:modelValue', normalizedColor)
  emit('color-change', normalizedColor)
  addToRecentColors(normalizedColor)
}

const handleResetColor = () => {
  emit('update:modelValue', props.defaultColor)
  emit('color-change', props.defaultColor)
  addToRecentColors(props.defaultColor)
}

const addToRecentColors = (color: string) => {
  if (!props.showRecent) return
  
  recentColors.value = recentColors.value.filter(c => c !== color)
  
  recentColors.value.unshift(color)
  
  if (recentColors.value.length > props.maxRecentColors) {
    recentColors.value = recentColors.value.slice(0, props.maxRecentColors)
  }
  
  if (import.meta.client) {
    localStorage.setItem('color-picker-recent', JSON.stringify(recentColors.value))
  }
}

onMounted(() => {
  if (import.meta.client && props.showRecent) {
    try {
      const saved = localStorage.getItem('color-picker-recent')
      if (saved) {
        recentColors.value = JSON.parse(saved)
      }
    } catch (error) {
      console.warn('Failed to load recent colors from localStorage')
    }
  }
})

watch(() => props.modelValue, (newValue) => {
  colorPickerValue.value = newValue
})

onMounted(() => {
  colorPickerValue.value = props.modelValue
})
</script>

<style scoped>
.color-picker {
  padding: 8px;
}

.color-section {
  margin-bottom: 20px;
}

.color-section:last-child {
  margin-bottom: 0;
}

.color-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 8px;
}

.color-option {
  height: 40px;
  border-radius: 8px;
  cursor: pointer;
  border: 3px solid transparent;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.color-option:hover {
  opacity: 0.8;
  transform: scale(1.05);
}

.color-option.selected {
  border-color: #000;
  box-shadow: 0 0 0 2px white, 0 0 0 4px #000;
}

.color-option--named::after {
  content: '';
  position: absolute;
  top: -2px;
  right: -2px;
  width: 8px;
  height: 8px;
  background: rgba(0, 0, 0, 0.3);
  border-radius: 50%;
}

.color-check {
  filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.3));
}

.custom-color-inputs {
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 8px;
  padding: 16px;
  background: rgba(0, 0, 0, 0.02);
}

.color-preview-container {
  display: flex;
  align-items: center;
  margin-bottom: 12px;
}

.color-preview {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  border: 2px solid rgba(0, 0, 0, 0.1);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.color-picker-popup {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.color-input-native {
  width: 100%;
  height: 60px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

.color-picker-controls {
  display: flex;
  align-items: center;
  gap: 12px;
}

.color-picker-preview {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  border: 2px solid rgba(0, 0, 0, 0.1);
  flex-shrink: 0;
}

.color-actions {
  display: flex;
  gap: 8px;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid rgba(0, 0, 0, 0.12);
}

@media (max-width: 768px) {
  .color-grid {
    grid-template-columns: repeat(4, 1fr);
    gap: 6px;
  }
  
  .color-option {
    height: 36px;
  }
  
  .custom-color-inputs {
    padding: 12px;
  }
  
  .color-picker-controls {
    flex-direction: column;
    gap: 8px;
  }
}

@media (max-width: 480px) {
  .color-grid {
    grid-template-columns: repeat(3, 1fr);
  }
  
  .color-actions {
    flex-direction: column;
  }
}

@media (prefers-color-scheme: dark) {
  .color-option.selected {
    border-color: #fff;
    box-shadow: 0 0 0 2px #000, 0 0 0 4px #fff;
  }
}
</style>