<template>
  <v-card variant="outlined" class="calendar-menu">
    <v-card-text class="pa-4">
      <div class="d-flex align-center flex-wrap gap-4">
        <!-- Quick Filters -->
        <div class="d-flex align-center gap-2">
          <v-checkbox
            :model-value="filters.showCompleted"
            label="Show completed"
            hide-details
            density="compact"
            color="success"
            @update:model-value="updateFilter('showCompleted', $event)"
          />

          <v-checkbox
            :model-value="filters.showOverdue"
            label="Show overdue"
            hide-details
            density="compact"
            color="error"
            @update:model-value="updateFilter('showOverdue', $event)"
          />

          <v-checkbox
            :model-value="filters.showDueToday"
            label="Due today"
            hide-details
            density="compact"
            color="warning"
            @update:model-value="updateFilter('showDueToday', $event)"
          />
        </div>

        <v-divider vertical />

        <!-- Filter by labels -->
        <v-menu v-if="board?.labels?.length" location="bottom">
          <template #activator="{ props }">
            <v-btn
              v-bind="props"
              variant="outlined"
              size="small"
              prepend-icon="mdi-label"
              :class="{ 'filter-active': filters.labelIds?.length }"
            >
              Labels
              <v-badge
                v-if="filters.labelIds?.length"
                :content="filters.labelIds.length"
                color="primary"
                inline
                class="ml-1"
              />
            </v-btn>
          </template>

          <v-card width="300">
            <v-card-title class="text-subtitle-1 font-weight-medium">
              Filter by Labels
            </v-card-title>
            <v-card-text class="pa-0">
              <v-list density="compact" class="label-list">
                <v-list-item
                  v-for="label in board.labels"
                  :key="label.id"
                  class="label-item"
                  :class="{ 'label-item--selected': isLabelSelected(label.id) }"
                  @click="toggleLabel(label.id)"
                >
                  <template #prepend>
                    <div
                      class="label-preview"
                      :style="{ backgroundColor: label.color }"
                      :title="label.name || 'Unnamed label'"
                    />
                  </template>

                  <v-list-item-title class="text-body-2">
                    {{ label.name || 'Unnamed label' }}
                  </v-list-item-title>
                  
                  <v-list-item-subtitle v-if="label.cards_count" class="text-caption">
                    {{ label.cards_count }} cards
                  </v-list-item-subtitle>

                  <template #append>
                    <v-icon 
                      v-if="isLabelSelected(label.id)" 
                      color="success"
                      size="small"
                    >
                      mdi-check-circle
                    </v-icon>
                  </template>
                </v-list-item>
              </v-list>
            </v-card-text>
            <v-card-actions>
              <v-spacer />
              <v-btn 
                size="small" 
                variant="text" 
                @click="clearLabelFilters"
                :disabled="!filters.labelIds?.length"
              >
                Clear Labels
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-menu>

        <!-- Filter by members -->
        <v-menu v-if="board?.members?.length" location="bottom">
          <template #activator="{ props }">
            <v-btn
              v-bind="props"
              variant="outlined"
              size="small"
              prepend-icon="mdi-account-group"
              :class="{ 'filter-active': filters.memberIds?.length }"
            >
              Members
              <v-badge
                v-if="filters.memberIds?.length"
                :content="filters.memberIds.length"
                color="primary"
                inline
                class="ml-1"
              />
            </v-btn>
          </template>

          <v-card width="300">
            <v-card-title class="text-subtitle-1 font-weight-medium">
              Filter by Members
            </v-card-title>
            <v-card-text class="pa-0">
              <v-list density="compact" class="member-list">
                <v-list-item
                  v-for="member in board.members"
                  :key="member.id"
                  class="member-item"
                  :class="{ 'member-item--selected': isMemberSelected(member.user_id) }"
                  @click="toggleMember(member.user_id)"
                >
                  <template #prepend>
                    <v-avatar size="32" color="primary" class="member-avatar">
                      <v-img v-if="member.user?.avatar_url" :src="member.user.avatar_url" />
                      <span v-else class="text-caption font-weight-medium">
                        {{ getUserInitials(member.user?.name || '') }}
                      </span>
                    </v-avatar>
                  </template>

                  <v-list-item-title class="text-body-2">
                    {{ member.user?.name }}
                  </v-list-item-title>
                  
                  <v-list-item-subtitle class="text-caption">
                    {{ member.role }}
                  </v-list-item-subtitle>

                  <template #append>
                    <v-icon 
                      v-if="isMemberSelected(member.user_id)" 
                      color="success"
                      size="small"
                    >
                      mdi-check-circle
                    </v-icon>
                  </template>
                </v-list-item>
              </v-list>
            </v-card-text>
            <v-card-actions>
              <v-spacer />
              <v-btn 
                size="small" 
                variant="text" 
                @click="clearMemberFilters"
                :disabled="!filters.memberIds?.length"
              >
                Clear Members
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-menu>

        <!-- Date Range Filter -->
        <v-menu location="bottom">
          <template #activator="{ props }">
            <v-btn
              v-bind="props"
              variant="outlined"
              size="small"
              prepend-icon="mdi-calendar-range"
              :class="{ 'filter-active': hasDateFilters }"
            >
              Date Range
              <v-badge
                v-if="hasDateFilters"
                content="1"
                color="primary"
                inline
                class="ml-1"
              />
            </v-btn>
          </template>

          <v-card width="320">
            <v-card-title class="text-subtitle-1 font-weight-medium">
              Filter by Date Range
            </v-card-title>
            <v-card-text>
              <div class="date-filters">
                <v-checkbox
                  v-model="dateFilters.showOverdue"
                  label="Show overdue only"
                  density="compact"
                  color="error"
                  hide-details
                  class="mb-2"
                />
                
                <v-checkbox
                  v-model="dateFilters.showDueToday"
                  label="Due today only"
                  density="compact"
                  color="warning"
                  hide-details
                  class="mb-3"
                />

                <v-divider class="mb-3" />

                <v-text-field
                  v-model="dateFilters.startDate"
                  label="From date"
                  type="date"
                  variant="outlined"
                  density="compact"
                  hide-details
                  class="mb-2"
                />

                <v-text-field
                  v-model="dateFilters.endDate"
                  label="To date"
                  type="date"
                  variant="outlined"
                  density="compact"
                  hide-details
                />
              </div>
            </v-card-text>
            <v-card-actions>
              <v-spacer />
              <v-btn 
                size="small" 
                variant="text" 
                @click="clearDateFilters"
              >
                Clear
              </v-btn>
              <v-btn 
                size="small" 
                color="primary" 
                @click="applyDateFilters"
              >
                Apply
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-menu>

        <!-- Clear all filters -->
        <v-btn
          v-if="hasFilters"
          variant="text"
          color="error"
          size="small"
          prepend-icon="mdi-filter-off"
          @click="clearAllFilters"
        >
          Clear All
        </v-btn>

        <!-- Active filters summary -->
        <div v-if="hasFilters" class="active-filters">
          <v-chip
            v-for="filter in activeFilterChips"
            :key="filter.label"
            size="small"
            variant="outlined"
            closable
            @click:close="removeFilter(filter.key, filter.value)"
          >
            {{ filter.label }}
          </v-chip>
        </div>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import type { CalendarFilter, Board } from '~/types/models'
import { computed, reactive, watch } from 'vue'

interface Props {
  filters: CalendarFilter
  board: Board | null
}

interface Emits {
  (event: 'update:filters', filters: CalendarFilter): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const dateFilters = reactive({
  showOverdue: false,
  showDueToday: false,
  startDate: '',
  endDate: ''
})

const hasFilters = computed(() => {
  return (
    props.filters.labelIds?.length ||
    props.filters.memberIds?.length ||
    props.filters.showCompleted !== true ||
    props.filters.showOverdue !== true ||
    props.filters.showDueToday !== true ||
    hasDateFilters.value
  )
})

const hasDateFilters = computed(() => {
  return (
    dateFilters.showOverdue ||
    dateFilters.showDueToday ||
    !!dateFilters.startDate ||
    !!dateFilters.endDate
  )
})

const activeFilterChips = computed(() => {
  const chips: Array<{ label: string; key: string; value: any }> = []

  if (props.filters.showCompleted === false)
    chips.push({ label: 'Hide completed', key: 'showCompleted', value: true })

  if (props.filters.showOverdue === false)
    chips.push({ label: 'Hide overdue', key: 'showOverdue', value: true })

  if (props.filters.showDueToday === false)
    chips.push({ label: 'Hide due today', key: 'showDueToday', value: true })

  if (props.filters.labelIds?.length)
    chips.push({
      label: `${props.filters.labelIds.length} label(s)`,
      key: 'labelIds',
      value: undefined
    })

  if (props.filters.memberIds?.length)
    chips.push({
      label: `${props.filters.memberIds.length} member(s)`,
      key: 'memberIds',
      value: undefined
    })

  if (dateFilters.showOverdue)
    chips.push({ label: 'Overdue only', key: 'dateOverdue', value: false })

  if (dateFilters.showDueToday)
    chips.push({ label: 'Due today only', key: 'dateDueToday', value: false })

  if (dateFilters.startDate)
    chips.push({ label: `From ${dateFilters.startDate}`, key: 'dateStart', value: '' })

  if (dateFilters.endDate)
    chips.push({ label: `To ${dateFilters.endDate}`, key: 'dateEnd', value: '' })

  return chips
})

const getUserInitials = (name: string): string =>
  name
    .split(' ')
    .map(p => p[0]?.toUpperCase())
    .join('')
    .slice(0, 2)

const isLabelSelected = (labelId: number): boolean =>
  props.filters.labelIds?.includes(labelId) || false

const isMemberSelected = (userId: number): boolean =>
  props.filters.memberIds?.includes(userId) || false

const updateFilter = (key: keyof CalendarFilter, value: any) => {
  emit('update:filters', { ...props.filters, [key]: value })
}

const toggleLabel = (labelId: number) => {
  const labelIds = props.filters.labelIds || []
  const newIds = labelIds.includes(labelId)
    ? labelIds.filter(id => id !== labelId)
    : [...labelIds, labelId]

  updateFilter('labelIds', newIds.length ? newIds : undefined)
}

const clearLabelFilters = () => {
  updateFilter('labelIds', undefined)
}

const toggleMember = (userId: number) => {
  const memberIds = props.filters.memberIds || []
  const newIds = memberIds.includes(userId)
    ? memberIds.filter(id => id !== userId)
    : [...memberIds, userId]

  updateFilter('memberIds', newIds.length ? newIds : undefined)
}

const clearMemberFilters = () => {
  updateFilter('memberIds', undefined)
}

const applyDateFilters = () => {
  const newFilters = { ...props.filters }

  if (dateFilters.showOverdue) newFilters.showOverdue = true
  if (dateFilters.showDueToday) newFilters.showDueToday = true

  if (dateFilters.startDate) newFilters.startDate = dateFilters.startDate
  if (dateFilters.endDate) newFilters.endDate = dateFilters.endDate

  emit('update:filters', newFilters)
}

const clearDateFilters = () => {
  dateFilters.showOverdue = false
  dateFilters.showDueToday = false
  dateFilters.startDate = ''
  dateFilters.endDate = ''

  const newFilters = { ...props.filters }
  delete newFilters.startDate
  delete newFilters.endDate

  emit('update:filters', newFilters)
}

const removeFilter = (key: string, value: any) => {
  switch (key) {
    case 'showCompleted':
    case 'showOverdue':
    case 'showDueToday':
      updateFilter(key as keyof CalendarFilter, value)
      break

    case 'labelIds':
      clearLabelFilters()
      break

    case 'memberIds':
      clearMemberFilters()
      break

    case 'dateOverdue':
      dateFilters.showOverdue = value
      break

    case 'dateDueToday':
      dateFilters.showDueToday = value
      break

    case 'dateStart':
      dateFilters.startDate = value
      emit('update:filters', { ...props.filters, startDate: undefined })
      break

    case 'dateEnd':
      dateFilters.endDate = value
      emit('update:filters', { ...props.filters, endDate: undefined })
      break
  }
}

const clearAllFilters = () => {
  dateFilters.showOverdue = false
  dateFilters.showDueToday = false
  dateFilters.startDate = ''
  dateFilters.endDate = ''

  emit('update:filters', {
    showCompleted: true,
    showOverdue: true,
    showDueToday: true
  })
}

watch(
  () => props.filters,
  newFilters => {
    if (newFilters.startDate) dateFilters.startDate = newFilters.startDate
    if (newFilters.endDate) dateFilters.endDate = newFilters.endDate
  },
  { immediate: true }
)
</script>

<style scoped>
.calendar-menu {
  border-radius: 12px;
  background: #f8f9fa;
}

.filter-active {
  background-color: rgba(25, 118, 210, 0.08) !important;
  border-color: rgb(25, 118, 210) !important;
  color: rgb(25, 118, 210) !important;
}

.label-list,
.member-list {
  max-height: 300px;
  overflow-y: auto;
}

.label-item,
.member-item {
  border-radius: 8px;
  margin: 2px 8px;
  transition: all 0.2s ease;
}

.label-item:hover,
.member-item:hover {
  background-color: rgba(0, 0, 0, 0.04);
}

.label-item--selected,
.member-item--selected {
  background-color: rgba(25, 118, 210, 0.08);
  border-left: 3px solid rgb(25, 118, 210);
}

.label-preview {
  width: 32px;
  height: 20px;
  border-radius: 4px;
  border: 1px solid rgba(0, 0, 0, 0.1);
}

.member-avatar {
  border: 2px solid rgba(0, 0, 0, 0.1);
}

.active-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  margin-top: 8px;
  width: 100%;
}

.date-filters {
  min-height: 200px;
}

.gap-2 {
  gap: 8px;
}

.gap-4 {
  gap: 16px;
}

/* Custom scrollbar */
.label-list::-webkit-scrollbar,
.member-list::-webkit-scrollbar {
  width: 6px;
}

.label-list::-webkit-scrollbar-track,
.member-list::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.04);
  border-radius: 3px;
}

.label-list::-webkit-scrollbar-thumb,
.member-list::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 3px;
}

.label-list::-webkit-scrollbar-thumb:hover,
.member-list::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.3);
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .d-flex.flex-wrap {
    gap: 12px;
  }
  
  .active-filters {
    gap: 6px;
  }
  
  .v-btn {
    font-size: 0.75rem;
  }
}

@media (max-width: 600px) {
  .calendar-menu {
    border-radius: 8px;
  }
  
  .d-flex.flex-wrap {
    gap: 8px;
  }
  
  .v-divider {
    display: none;
  }
}
</style>