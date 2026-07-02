<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useScansStore } from '@/stores/scans'
import { usePagination, type SortItem } from '@/composables/usePagination'
import { useFilters } from '@/composables/useFilters'
import AppDataTable from '@/components/common/AppDataTable.vue'
import AppEmptyState from '@/components/common/AppEmptyState.vue'
import AppStatusChip from '@/components/common/AppStatusChip.vue'
import { SCAN_RESULT_MAP } from '@/constants/status'
import { getEvents } from '@/api/events'
import type { Event, ScanLog } from '@/types/domain'

const store = useScansStore()
const pagination = usePagination()
const { filters, debouncedFilters } = useFilters({ date_from: '', date_to: '' })

const events = ref<Event[]>([])
const eventId = ref<number | null>(null)

const headers = [
  { title: 'Code', key: 'code', sortable: false },
  { title: 'Evento', key: 'event', sortable: false },
  { title: 'Resultado', key: 'result', sortable: false },
  { title: 'Escaneado por', key: 'scanned_by', sortable: false },
  { title: 'Fecha/hora', key: 'created_at' },
]

function formatDate(value?: string): string {
  if (!value) return '—'
  const d = new Date(value)
  return Number.isNaN(d.getTime()) ? value : d.toLocaleString('es')
}

function eventName(item: ScanLog): string {
  return events.value.find((e) => e.id === item.event_id)?.name ?? '—'
}

function scannedBy(item: ScanLog): string {
  return item.gate_user?.name ?? (item.scanned_by != null ? String(item.scanned_by) : '—')
}

async function loadEvents() {
  const res = await getEvents({ per_page: 100 })
  events.value = res.data
}

async function load() {
  if (!eventId.value) return
  await store.fetchAll({
    ...pagination.toParams(),
    event_id: eventId.value,
    date_from: debouncedFilters.value.date_from || undefined,
    date_to: debouncedFilters.value.date_to || undefined,
  })
}

function onOptions(opts: { page: number; itemsPerPage: number; sortBy: SortItem[] }) {
  pagination.setFromOptions(opts)
  load()
}

watch([eventId, debouncedFilters], () => {
  pagination.page.value = 1
  if (eventId.value) load()
  else {
    store.items.splice(0)
    store.meta = null
  }
})

onMounted(loadEvents)
</script>

<template>
  <div>
    <div class="d-flex align-center mb-4">
      <h1 class="text-h5">Escaneos</h1>
    </div>

    <v-card class="mb-4">
      <div class="pa-4 d-flex align-center flex-wrap ga-3">
        <v-select
          v-model="eventId"
          :items="events"
          item-title="name"
          item-value="id"
          label="Evento"
          hide-details
          clearable
          density="compact"
          style="max-width: 320px"
        />
        <v-text-field
          v-model="filters.date_from"
          label="Desde"
          type="date"
          hide-details
          clearable
          density="compact"
          style="max-width: 200px"
        />
        <v-text-field
          v-model="filters.date_to"
          label="Hasta"
          type="date"
          hide-details
          clearable
          density="compact"
          style="max-width: 200px"
        />
      </div>
    </v-card>

    <v-card v-if="!eventId">
      <AppEmptyState
        icon="mdi-qrcode-scan"
        title="Selecciona un evento"
        subtitle="Elige un evento arriba para ver su historial de escaneos."
      />
    </v-card>

    <AppDataTable
      v-else
      :headers="headers"
      :items="store.items"
      :total-items="store.meta?.total ?? 0"
      :loading="store.loading"
      :options="{ page: pagination.page.value, itemsPerPage: pagination.itemsPerPage.value, sortBy: pagination.sortBy.value }"
      @update:options="onOptions"
    >
      <template #[`item.code`]="{ item }">
        <span class="font-monospace">{{ (item as ScanLog).code ?? '—' }}</span>
      </template>

      <template #[`item.event`]="{ item }">
        {{ eventName(item as ScanLog) }}
      </template>

      <template #[`item.result`]="{ item }">
        <AppStatusChip :value="(item as ScanLog).result" :map="SCAN_RESULT_MAP" />
      </template>

      <template #[`item.scanned_by`]="{ item }">
        {{ scannedBy(item as ScanLog) }}
      </template>

      <template #[`item.created_at`]="{ item }">
        {{ formatDate((item as ScanLog).created_at) }}
      </template>
    </AppDataTable>
  </div>
</template>

<style scoped>
.font-monospace {
  font-family: monospace;
}
</style>
