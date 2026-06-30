<script setup lang="ts">
defineProps<{
  title?: string
  subtitle?: string
  icon?: string
}>()
</script>

<template>
  <div class="d-flex flex-column align-center justify-center pa-12 text-center text-medium-emphasis">
    <v-icon :icon="icon ?? 'mdi-inbox-outline'" size="64" class="mb-4" />
    <div class="text-h6 mb-1">{{ title ?? 'Sin datos' }}</div>
    <div v-if="subtitle" class="text-body-2 mb-4">{{ subtitle }}</div>
    <slot />
  </div>
</template>
