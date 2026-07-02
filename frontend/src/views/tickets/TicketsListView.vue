<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useTicketsStore } from '@/stores/tickets'
import { usePagination, type SortItem } from '@/composables/usePagination'
import { useFilters } from '@/composables/useFilters'
import { useConfirm } from '@/composables/useConfirm'
import { useSnackbar } from '@/composables/useSnackbar'
import AppDataTable from '@/components/common/AppDataTable.vue'
import TicketStatusChip from '@/components/tickets/TicketStatusChip.vue'
import { getEvents } from '@/api/events'
import { exportTickets } from '@/api/tickets'
import type { Event, Ticket } from '@/types/domain'

const store = useTicketsStore()
const { confirm } = useConfirm()
const { success, error } = useSnackbar()
const pagination = usePagination()
const { filters, debouncedFilters } = useFilters({ search: '', status: null as string | null, event_id: null as number | null })

const events = ref<Event[]>([])
const exporting = ref(false)

const statusOptions = [
  { title: 'Activo', value: 'active' },
  { title: 'Usado', value: 'used' },
  { title: 'Anulado', value: 'void' },
  { title: 'Expirado', value: 'expired' },
]

const headers = [
  { title: 'Code', key: 'code' },
  { title: 'Tipo', key: 'ticket_type', sortable: false },
  { title: 'Evento', key: 'event', sortable: false },
  { title: 'Cliente', key: 'customer', sortable: false },
  { title: 'Sección/Asiento', key: 'seat', sortable: false },
  { title: 'Estado', key: 'status', sortable: false },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' as const },
]

function truncate(value: string, max = 16): string {
  return value.length > max ? `${value.slice(0, max)}…` : value
}

function seatLabel(item: Ticket): string {
  const parts = [item.section, item.seat].filter((p) => p)
  return parts.length ? parts.join(' / ') : '—'
}

async function loadEvents() {
  const res = await getEvents({ per_page: 100 })
  events.value = res.data
}

async function load() {
  await store.fetchAll({
    ...pagination.toParams(),
    search: debouncedFilters.value.search || undefined,
    status: debouncedFilters.value.status || undefined,
    event_id: debouncedFilters.value.event_id ?? undefined,
  })
}

function onOptions(opts: { page: number; itemsPerPage: number; sortBy: SortItem[] }) {
  pagination.setFromOptions(opts)
  load()
}

watch(debouncedFilters, () => {
  pagination.page.value = 1
  load()
})

async function onExport() {
  exporting.value = true
  try {
    const blob = await exportTickets({
      search: debouncedFilters.value.search || undefined,
      status: debouncedFilters.value.status || undefined,
      event_id: debouncedFilters.value.event_id ?? undefined,
    })
    const url = URL.createObjectURL(blob as Blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'tickets.csv'
    document.body.appendChild(a)
    a.click()
    a.remove()
    URL.revokeObjectURL(url)
  } catch {
    error('No se pudo exportar el CSV')
  } finally {
    exporting.value = false
  }
}

async function voidTicket(ticket: Ticket) {
  const ok = await confirm({
    title: 'Anular ticket',
    message: `¿Anular el ticket "${ticket.code}"? Esta acción inhabilita el QR.`,
    confirmText: 'Anular',
  })
  if (!ok) return
  try {
    await store.voidTicket(ticket.id)
    success('Ticket anulado')
  } catch {
    error('No se pudo anular el ticket')
  }
}

async function reissueTicket(ticket: Ticket) {
  const ok = await confirm({
    title: 'Reemitir ticket',
    message: `¿Reemitir el ticket "${ticket.code}"? Se generará un nuevo code y QR.`,
    confirmText: 'Reemitir',
    color: 'primary',
  })
  if (!ok) return
  try {
    await store.reissueTicket(ticket.id)
    success('Ticket reemitido')
  } catch {
    error('No se pudo reemitir el ticket')
  }
}

onMounted(() => {
  loadEvents()
  load()
})
</script>

<template>
  <div>
    <div class="d-flex align-center mb-4">
      <h1 class="text-h5">Tickets</h1>
      <v-spacer />
      <v-btn
        color="primary"
        prepend-icon="mdi-download"
        :loading="exporting"
        @click="onExport"
      >
        Exportar CSV
      </v-btn>
    </div>

    <AppDataTable
      :headers="headers"
      :items="store.items"
      :total-items="store.meta?.total ?? 0"
      :loading="store.loading"
      :options="{ page: pagination.page.value, itemsPerPage: pagination.itemsPerPage.value, sortBy: pagination.sortBy.value }"
      @update:options="onOptions"
    >
      <template #top>
        <div class="d-flex align-center flex-wrap ga-3">
          <v-text-field
            v-model="filters.search"
            label="Buscar por code"
            prepend-inner-icon="mdi-magnify"
            hide-details
            clearable
            density="compact"
            style="max-width: 280px"
          />
          <v-select
            v-model="filters.status"
            :items="statusOptions"
            label="Estado"
            hide-details
            clearable
            density="compact"
            style="max-width: 200px"
          />
          <v-select
            v-model="filters.event_id"
            :items="events"
            item-title="name"
            item-value="id"
            label="Evento"
            hide-details
            clearable
            density="compact"
            style="max-width: 260px"
          />
        </div>
      </template>

      <template #[`item.code`]="{ item }">
        <v-tooltip :text="(item as Ticket).code" location="top">
          <template #activator="{ props }">
            <span v-bind="props" class="font-monospace">{{ truncate((item as Ticket).code) }}</span>
          </template>
        </v-tooltip>
      </template>

      <template #[`item.ticket_type`]="{ item }">
        {{ (item as Ticket).ticket_type?.name ?? '—' }}
      </template>

      <template #[`item.event`]="{ item }">
        {{ (item as Ticket).event?.name ?? '—' }}
      </template>

      <template #[`item.customer`]="{ item }">
        {{ (item as Ticket).customer?.full_name ?? '—' }}
      </template>

      <template #[`item.seat`]="{ item }">
        {{ seatLabel(item as Ticket) }}
      </template>

      <template #[`item.status`]="{ item }">
        <TicketStatusChip :value="(item as Ticket).status" />
      </template>

      <template #[`item.actions`]="{ item }">
        <v-btn
          v-if="(item as Ticket).status === 'active'"
          icon="mdi-cancel"
          size="small"
          variant="text"
          color="error"
          @click="voidTicket(item as Ticket)"
        />
        <v-btn
          v-if="(item as Ticket).status === 'void' || (item as Ticket).status === 'expired'"
          icon="mdi-refresh"
          size="small"
          variant="text"
          color="primary"
          @click="reissueTicket(item as Ticket)"
        />
      </template>
    </AppDataTable>
  </div>
</template>

<style scoped>
.font-monospace {
  font-family: monospace;
}
</style>
