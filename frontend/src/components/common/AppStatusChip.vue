<script setup lang="ts">
import { computed } from 'vue'

interface ChipDef {
  label: string
  color: string
}

const props = defineProps<{
  value: string | null | undefined
  /** Mapa valor -> { label, color }. */
  map: Record<string, ChipDef>
}>()

const def = computed<ChipDef>(
  () => props.map[props.value ?? ''] ?? { label: props.value ?? '—', color: 'grey' },
)
</script>

<template>
  <v-chip :color="def.color" size="small" label variant="flat">{{ def.label }}</v-chip>
</template>
