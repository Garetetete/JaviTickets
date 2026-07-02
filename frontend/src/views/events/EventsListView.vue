<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useEventsStore } from '@/stores/events'
import { usePagination, type SortItem } from '@/composables/usePagination'
import { useFilters } from '@/composables/useFilters'
import { useConfirm } from '@/composables/useConfirm'
import { useSnackbar } from '@/composables/useSnackbar'
import AppDataTable from '@/components/common/AppDataTable.vue'
import AppSoftDeleteBadge from '@/components/common/AppSoftDeleteBadge.vue'
import AppStatusChip from '@/components/common/AppStatusChip.vue'
import { SEATING_TYPE_MAP } from '@/constants/status'
import type { Event } from '@/types/domain'

const router = useRouter()
const store = useEventsStore()
const { confirm } = useConfirm()
const { success, error } = useSnackbar()
const pagination = usePagination()
const { filters, debouncedFilters } = useFilters({ search: '' })
const withTrashed = ref(false)

const headers = [
  { title: 'ID', key: 'id', width: 80 },
  { title: 'Nombre', key: 'name' },
  { title: 'Fecha', key: 'event_date' },
  { title: 'Ciudad', key: 'city' },
  { title: 'Capacidad', key: 'capacity' },
  { title: 'Tipo de asientos', key: 'seating_type', sortable: false },
  { title: 'Estado', key: 'status', sortable: false },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' as const },
]

function formatDate(value: string): string {
  const d = new Date(value)
  return Number.isNaN(d.getTime()) ? value : d.toLocaleString()
}

async function load() {
  await store.fetchAll({
    ...pagination.toParams(),
    search: debouncedFilters.value.search || undefined,
    with_trashed: withTrashed.value ? true : undefined,
  })
}

function onOptions(opts: { page: number; itemsPerPage: number; sortBy: SortItem[] }) {
  pagination.setFromOptions(opts)
  load()
}

watch([debouncedFilters, withTrashed], () => {
  pagination.page.value = 1
  load()
})

async function removeEvent(event: Event) {
  const ok = await confirm({
    title: 'Eliminar evento',
    message: `¿Eliminar el evento "${event.name}"? Podrás restaurarlo después.`,
    confirmText: 'Eliminar',
  })
  if (!ok) return
  try {
    await store.remove(event.id)
    success('Evento eliminado')
    if (!withTrashed.value) load()
  } catch {
    error('No se pudo eliminar el evento')
  }
}

async function restoreEvent(event: Event) {
  try {
    await store.restore(event.id)
    success('Evento restaurado')
  } catch {
    error('No se pudo restaurar el evento')
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div class="d-flex align-center mb-4">
      <h1 class="text-h5">Eventos</h1>
      <v-spacer />
      <v-btn color="primary" prepend-icon="mdi-plus" @click="router.push('/events/create')">
        Nuevo evento
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
            style="max-width: 320px"
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

      <template #[`item.event_date`]="{ item }">
        {{ (item as Event).event_date ? formatDate((item as Event).event_date as string) : '—' }}
      </template>

      <template #[`item.seating_type`]="{ item }">
        <AppStatusChip :value="(item as Event).seating_type" :map="SEATING_TYPE_MAP" />
      </template>

      <template #[`item.status`]="{ item }">
        <AppSoftDeleteBadge :deleted-at="(item as Event).deleted_at" />
      </template>

      <template #[`item.actions`]="{ item }">
        <v-btn
          v-if="!(item as Event).deleted_at && (item as Event).seating_type === 'seated'"
          icon="mdi-seat"
          size="small"
          variant="text"
          color="indigo"
          @click="router.push(`/events/${(item as Event).id}/seats`)"
        />
        <v-btn
          v-if="!(item as Event).deleted_at"
          icon="mdi-pencil"
          size="small"
          variant="text"
          @click="router.push(`/events/${(item as Event).id}/edit`)"
        />
        <v-btn
          v-if="!(item as Event).deleted_at"
          icon="mdi-delete"
          size="small"
          variant="text"
          color="error"
          @click="removeEvent(item as Event)"
        />
        <v-btn
          v-else
          icon="mdi-restore"
          size="small"
          variant="text"
          color="primary"
          @click="restoreEvent(item as Event)"
        />
      </template>
    </AppDataTable>
  </div>
</template>
