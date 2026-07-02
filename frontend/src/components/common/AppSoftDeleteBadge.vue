<script setup lang="ts">
// Marca visual para ítems con soft-delete (deleted_at != null).
defineProps<{ deletedAt: string | null | undefined }>()
</script>

<template>
  <v-chip v-if="deletedAt" color="grey" size="x-small" label variant="tonal">
    <v-icon start icon="mdi-delete-outline" size="small" />
    Eliminado
  </v-chip>
  <v-chip v-else color="success" size="x-small" label variant="tonal">Activo</v-chip>
</template>
