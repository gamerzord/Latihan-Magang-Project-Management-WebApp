<template>
  <div class="card-members">
    <div class="avatar-group">
      <v-tooltip
        v-for="member in limitedMembers"
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

      <!-- +X more indicator -->
      <v-avatar
        v-if="extraCount > 0"
        size="32"
        color="grey-lighten-2"
        class="member-avatar more"
      >
        <span class="text-caption font-weight-medium">
          +{{ extraCount }}
        </span>
      </v-avatar>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Card } from '~/types/models'

const props = defineProps<{ card: Card }>()

const max = 3
const limitedMembers = computed(() => props.card.members?.slice(0, max) ?? [])
const extraCount = computed(() => (props.card.members?.length ?? 0) - max)

const getUserInitials = (name: string): string =>
  name
    .split(' ')
    .map(part => part.charAt(0).toUpperCase())
    .join('')
    .slice(0, 2)
</script>

<style scoped>
.card-members {
  min-height: 32px;
}

.avatar-group {
  display: flex;
  align-items: center;
}

.member-avatar {
  border: 2px solid white;
  margin-left: -10px;
  transition: all 0.2s ease;
  cursor: pointer;
}

.member-avatar:first-child {
  margin-left: 0;
}

.member-avatar:hover {
  transform: scale(1.1);
  z-index: 5;
}

.more {
  background-color: #e0e0e0;
  color: #555;
}

@media (max-width: 768px) {
  .member-avatar {
    width: 28px;
    height: 28px;
  }
}
</style>
