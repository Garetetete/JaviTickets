<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useTicketTypesStore } from '@/stores/ticketTypes'
import { usePagination, type SortItem } from '@/composables/usePagination'
import { useFilters } from '@/composables/useFilters'
import { useConfirm } from '@/composables/useConfirm'
import { useSnackbar } from '@/composables/useSnackbar'
import AppDataTable from '@/components/common/AppDataTable.vue'
import AppSoftDeleteBadge from '@/components/common/AppSoftDeleteBadge.vue'
import * as eventsApi from '@/api/events'
import type { Event, TicketType } from '@/types/domain'

const router = useRouter()
const store = useTicketTypesStore()
const { confirm } = useConfirm()
const { success, error } = useSnackbar()
const pagination = usePagination()
const { filters, debouncedFilters } = useFilters({ search: '' })
const withTrashed = ref(false)
const eventId = ref<number | null>(null)
const events = ref<Event[]>([])

const headers = [
  { title: 'ID', key: 'id', width: 80 },
  { title: 'Nombre', key: 'name' },
  { title: 'Evento', key: 'event', sortable: false },
  { title: 'Precio', key: 'price' },
  { title: 'Cupo', key: 'quota' },
  { title: 'Estado', key: 'status', sortable: false },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' as const },
]

function eventName(item: TicketType): string {
  return item.event?.name ?? `#${item.event_id}`
}

function quotaLabel(item: TicketType): string {
  return item.quota === null || item.quota === undefined ? 'Sin límite' : String(item.quota)
}

async function loadEvents() {
  try {
    const res = await eventsApi.getEvents({ per_page: 100 })
    events.value = res.data
  } catch {
    events.value = []
  }
}

async function load() {
  await store.fetchAll({
    ...pagination.toParams(),
    search: debouncedFilters.value.search || undefined,
    event_id: eventId.value ?? undefined,
    with_trashed: withTrashed.value ? true : undefined,
  })
}

function onOptions(opts: { page: number; itemsPerPage: number; sortBy: SortItem[] }) {
  pagination.setFromOptions(opts)
  load()
}

watch([debouncedFilters, withTrashed, eventId], () => {
  pagination.page.value = 1
  load()
})

async function removeTicketType(tt: TicketType) {
  const ok = await confirm({
    title: 'Eliminar tipo de ticket',
    message: `¿Eliminar el tipo de ticket "${tt.name}"? Podrás restaurarlo después.`,
    confirmText: 'Eliminar',
  })
  if (!ok) return
  try {
    await store.remove(tt.id)
    success('Tipo de ticket eliminado')
    if (!withTrashed.value) load()
  } catch {
    error('No se pudo eliminar el tipo de ticket')
  }
}

async function restoreTicketType(tt: TicketType) {
  try {
    await store.restore(tt.id)
    success('Tipo de ticket restaurado')
  } catch {
    error('No se pudo restaurar el tipo de ticket')
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
      <h1 class="text-h5">Tipos de ticket</h1>
      <v-spacer />
      <v-btn color="primary" prepend-icon="mdi-plus" @click="router.push('/ticket-types/create')">
        Nuevo tipo
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
            label="Buscar por nombre"
            prepend-inner-icon="mdi-magnify"
            hide-details
            clearable
            density="compact"
            style="max-width: 280px"
          />
          <v-select
            v-model="eventId"
            label="Evento"
            :items="events"
            item-title="name"
            item-value="id"
            hide-details
            clearable
            density="compact"
            style="max-width: 280px"
          />
          <v-spacer />
          <v-switch
            v-model="withTrashed"
            label="Mostrar eliminados"
            color="primary"
            hide-details
            density="compact"
          />
        </div>
      </template>

      <template #[`item.event`]="{ item }">
        {{ eventName(item as TicketType) }}
      </template>

      <template #[`item.price`]="{ item }">
        {{ (item as TicketType).price }} {{ (item as TicketType).currency }}
      </template>

      <template #[`item.quota`]="{ item }">
        {{ quotaLabel(item as TicketType) }}
      </template>

      <template #[`item.status`]="{ item }">
        <AppSoftDeleteBadge :deleted-at="(item as TicketType).deleted_at" />
      </template>

      <template #[`item.actions`]="{ item }">
        <v-btn
          v-if="!(item as TicketType).deleted_at"
          icon="mdi-pencil"
          size="small"
          variant="text"
          @click="router.push(`/ticket-types/${(item as TicketType).id}/edit`)"
        />
        <v-btn
          v-if="!(item as TicketType).deleted_at"
          icon="mdi-delete"
          size="small"
          variant="text"
          color="error"
          @click="removeTicketType(item as TicketType)"
        />
        <v-btn
          v-else
          icon="mdi-restore"
          size="small"
          variant="text"
          color="primary"
          @click="restoreTicketType(item as TicketType)"
        />
      </template>
    </AppDataTable>
  </div>
</template>
