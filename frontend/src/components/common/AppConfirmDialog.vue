<script setup lang="ts">
import { useConfirm } from '@/composables/useConfirm'

// Dialog de confirmación global. Consumido por useConfirm() desde cualquier vista.
const { state, respond } = useConfirm()
</script>

<template>
  <v-dialog :model-value="state.open" max-width="460" persistent>
    <v-card>
      <v-card-title class="text-h6">{{ state.title }}</v-card-title>
      <v-card-text>{{ state.message }}</v-card-text>
      <v-card-actions class="justify-end">
        <v-btn variant="text" @click="respond(false)">{{ state.cancelText }}</v-btn>
        <v-btn :color="state.color" variant="flat" @click="respond(true)">
          {{ state.confirmText }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
