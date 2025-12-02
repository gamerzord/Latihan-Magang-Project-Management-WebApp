<template>
  <v-card
    class="trello-card mb-2"
    elevation="1"
    @click="$emit('open', card)"
  >
    <!-- Labels -->
    <div v-if="card.labels?.length" class="card-labels mb-2">
      <span
        v-for="label in card.labels"
        :key="label.id"
        class="card-label"
        :style="{ backgroundColor: label.color }"
        :title="label.name || ''"
      />
    </div>

    <!-- Title -->
    <v-card-title class="text-body-1 pa-2">
      {{ card.title }}
    </v-card-title>

    <!-- Badges -->
    <v-card-text v-if="showBadges" class="pa-2 pt-0">
      <div class="d-flex align-center gap-2 flex-wrap">
        <!-- Due date -->
        <v-chip
          v-if="card.due_date"
          :color="dueDateStatus.color"
          size="small"
          label
          variant="flat"
        >
          <v-icon start size="small">mdi-clock-outline</v-icon>
          {{ formatDate(card.due_date, 'short') }}
        </v-chip>

        <!-- Description indicator -->
        <v-icon v-if="card.description" size="small" color="grey-darken-1">
          mdi-text-box-outline
        </v-icon>

        <!-- Comments count -->
        <div v-if="card.comments?.length" class="d-flex align-center">
          <v-icon size="small" color="grey-darken-1">mdi-comment-outline</v-icon>
          <span class="text-caption ml-1">{{ card.comments.length }}</span>
        </div>

        <!-- Checklist progress -->
        <div v-if="checklistProgress" class="d-flex align-center">
          <v-icon size="small" color="grey-darken-1">mdi-checkbox-marked-outline</v-icon>
          <span class="text-caption ml-1">
            {{ checklistProgress.completed }}/{{ checklistProgress.total }}
          </span>
        </div>

        <!-- Attachments count -->
        <div v-if="card.attachments?.length" class="d-flex align-center">
          <v-icon size="small" color="grey-darken-1">mdi-paperclip</v-icon>
          <span class="text-caption ml-1">{{ card.attachments.length }}</span>
        </div>

        <!-- Members -->
        <div class="avatar-group ml-auto" v-if="card.members?.length" density="compact">
          <v-avatar
            v-for="member in card.members.slice(0, 3)"
            :key="member.id"
            size="24"
            color="primary"
          >
            <v-img v-if="member.avatar_url" :src="member.avatar_url" />
            <span v-else class="text-caption font-weight-medium">{{ getUserInitials(member.name) }}</span>
          </v-avatar>
          <v-avatar v-if="card.members.length > 3" size="24" color="grey-lighten-2">
            <span class="text-caption text-grey-darken-2">+{{ card.members.length - 3 }}</span>
          </v-avatar>
        </div>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import type { Card } from '~/types/models'

interface Props {
  card: Card
}

interface Emits {
  (event: 'open', card: Card): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const showBadges = computed(() => {
  return props.card.due_date || 
         props.card.description || 
         props.card.comments?.length ||
         props.card.checklists?.length ||
         props.card.attachments?.length ||
         props.card.members?.length
})

const dueDateStatus = computed(() => {
  if (!props.card.due_date) return { color: 'grey', text: '' }
  
  const dueDate = new Date(props.card.due_date)
  const today = new Date()
  const timeDiff = dueDate.getTime() - today.getTime()
  const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24))

  if (props.card.due_date_completed) {
    return { color: 'success', text: 'Completed' }
  } else if (daysDiff < 0) {
    return { color: 'error', text: 'Overdue' }
  } else if (daysDiff === 0) {
    return { color: 'warning', text: 'Due today' }
  } else if (daysDiff <= 2) {
    return { color: 'warning', text: 'Due soon' }
  } else {
    return { color: 'primary', text: 'Upcoming' }
  }
})

const checklistProgress = computed(() => {
  if (!props.card.checklists?.length) return null
  
  let total = 0
  let completed = 0
  
  props.card.checklists.forEach(checklist => {
    checklist.items?.forEach(item => {
      total++
      if (item.completed) completed++
    })
  })
  
  return total > 0 ? { total, completed } : null
})

const formatDate = (dateString: string, format: 'short' | 'long' = 'short'): string => {
  const date = new Date(dateString)
  return format === 'short' 
    ? date.toLocaleDateString()
    : date.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' })
}

const getUserInitials = (name: string): string => {
  return name
    .split(' ')
    .map(part => part.charAt(0).toUpperCase())
    .join('')
    .slice(0, 2)
}
</script>

<style scoped>
.trello-card {
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid rgba(0, 0, 0, 0.08);
}

.trello-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

.card-labels {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  padding: 8px 8px 0;
  min-height: 8px;
}

.card-label {
  height: 8px;
  min-width: 40px;
  border-radius: 4px;
  flex-shrink: 0;
}

.gap-2 {
  gap: 8px;
}

@media (max-width: 600px) {
  .trello-card {
    margin-bottom: 4px;
  }
  
  .card-labels {
    padding: 6px 6px 0;
  }
}
</style>