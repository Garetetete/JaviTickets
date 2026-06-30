<script setup lang="ts">
import type { SortItem } from '@/composables/usePagination'

export interface TableOptions {
  page: number
  itemsPerPage: number
  sortBy: SortItem[]
}

defineProps<{
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  headers: readonly any[]
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  items: readonly any[]
  totalItems: number
  loading?: boolean
  options: TableOptions
}>()

const emit = defineEmits<{
  'update:options': [value: TableOptions]
}>()

function onUpdate(value: TableOptions) {
  emit('update:options', value)
}
</script>

<template>
  <v-card>
    <div v-if="$slots.top" class="pa-4">
      <slot name="top" />
    </div>
    <v-divider v-if="$slots.top" />
    <v-data-table-server
      :headers="(headers as any)"
      :items="(items as any)"
      :items-length="totalItems"
      :loading="loading"
      :items-per-page="options.itemsPerPage"
      :page="options.page"
      :sort-by="(options.sortBy as any)"
      :items-per-page-options="[10, 20, 50, 100]"
      @update:options="onUpdate($event as TableOptions)"
    >
      <template v-for="(_, name) in $slots" #[name]="slotProps" :key="name">
        <slot :name="name" v-bind="slotProps ?? {}" />
      </template>
    </v-data-table-server>
  </v-card>
</template>
