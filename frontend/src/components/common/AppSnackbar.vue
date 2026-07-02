<script setup lang="ts">
import { useSnackbar } from '@/composables/useSnackbar'

// Snackbar global montado en App.vue. Apila notificaciones en cola.
const { queue, dismiss } = useSnackbar()
</script>

<template>
  <div class="app-snackbar-stack">
    <v-snackbar
      v-for="msg in queue"
      :key="msg.id"
      :model-value="true"
      :color="msg.color"
      :timeout="msg.timeout"
      location="top right"
      @update:model-value="dismiss(msg.id)"
    >
      {{ msg.message }}
      <template #actions>
        <v-btn variant="text" icon="mdi-close" @click="dismiss(msg.id)" />
      </template>
    </v-snackbar>
  </div>
</template>
