<template>
  <div class="card-members">
    <v-avatar-group density="compact" max="3">
      <v-tooltip
        v-for="member in card.members || []"
        :key="member.id"
        :text="member.name"
        location="top"
      >
        <template #activator="{ props }">
          <v-avatar
            v-bind="props"
            size="32"
            color="primary"
            class="member-avatar"
          >
            <v-img v-if="member.avatar_url" :src="member.avatar_url" alt="" />
            <span v-else class="text-caption font-weight-medium">
              {{ getUserInitials(member.name) }}
            </span>
          </v-avatar>
        </template>
      </v-tooltip>
    </v-avatar-group>
  </div>
</template>

<script setup lang="ts">
import type { Card } from '~/types/models'

interface Props {
  card: Card
}

interface Emits {
  (event: 'refresh'): void
}

defineProps<Props>()
defineEmits<Emits>()

const getUserInitials = (name: string): string => {
  return name
    .split(' ')
    .map(part => part.charAt(0).toUpperCase())
    .join('')
    .slice(0, 2)
}
</script>

<style scoped>
.card-members {
  min-height: 32px;
}

.member-avatar {
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
}

.member-avatar:hover {
  transform: scale(1.1);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  z-index: 1;
}

@media (max-width: 768px) {
  .member-avatar {
    size: 28px;
  }
}
</style>