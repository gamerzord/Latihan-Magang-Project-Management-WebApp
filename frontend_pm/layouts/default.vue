<template>
  <v-app>
    <Navbar v-if="userStore.isAuthenticated" />
    
    <v-main>
      <slot />
    </v-main>

    <!-- Global snackbar -->
    <v-snackbar
      v-model="uiStore.snackbar.show"
      :color="uiStore.snackbar.color"
      :timeout="3000"
      location="bottom right"
    >
      {{ uiStore.snackbar.message }}
      <template #actions>
        <v-btn
          variant="text"
          @click="uiStore.hideSnackbar()"
        >
          Close
        </v-btn>
      </template>
    </v-snackbar>

    <!-- Global loading overlay -->
    <v-overlay
      v-model="uiStore.loading"
      class="align-center justify-center"
      persistent
    >
      <v-progress-circular
        indeterminate
        size="64"
        color="primary"
      />
    </v-overlay>
  </v-app>
</template>

<script setup lang="ts">
import { useUiStore } from '~/stores/ui'
import { useUserStore } from '~/stores/user'

const uiStore = useUiStore()
const userStore = useUserStore()
</script>