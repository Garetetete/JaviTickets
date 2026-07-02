<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useOrdersStore } from '@/stores/orders'
import { usePagination, type SortItem } from '@/composables/usePagination'
import { useFilters } from '@/composables/useFilters'
import { getEvents } from '@/api/events'
import AppDataTable from '@/components/common/AppDataTable.vue'
import OrderStatusChip from '@/components/orders/OrderStatusChip.vue'
import type { Event, Order } from '@/types/domain'

const router = useRouter()
const store = useOrdersStore()
const pagination = usePagination()
const { filters, debouncedFilters } = useFilters<{
  status: string | null
  event_id: number | null
  search: string
}>({ status: null, event_id: null, search: '' })

const events = ref<Event[]>([])

const statusOptions = [
  { title: 'Todos', value: null },
  { title: 'Pago pendiente', value: 'pending_payment' },
  { title: 'Por verificar', value: 'pending_verification' },
  { title: 'Verificada', value: 'verified' },
  { title: 'Rechazada', value: 'rejected' },
  { title: 'Expirada', value: 'expired' },
]

const headers = [
  { title: 'ID', key: 'id', width: 80 },
  { title: 'Ref. externa', key: 'external_reference' },
  { title: 'Cliente', key: 'customer', sortable: false },
  { title: 'Evento', key: 'event', sortable: false },
  { title: 'Monto', key: 'amount' },
  { title: 'Estado', key: 'payment_status', sortable: false },
  { title: 'Fecha', key: 'created_at' },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' as const },
]

function formatDate(value?: string) {
  if (!value) return '—'
  return new Date(value).toLocaleString('es')
}

function formatAmount(order: Order) {
  return `${Number(order.amount).toFixed(2)} ${order.currency}`
}

async function load() {
  await store.fetchAll({
    ...pagination.toParams(),
    status: debouncedFilters.value.status ?? undefined,
    event_id: debouncedFilters.value.event_id ?? undefined,
    search: debouncedFilters.value.search || undefined,
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

onMounted(async () => {
  load()
  try {
    const res = await getEvents({ per_page: 100 })
    events.value = res.data
  } catch {
    events.value = []
  }
})
</script>

<template>
  <div>
    <div class="d-flex align-center mb-4">
      <h1 class="text-h5">Órdenes</h1>
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
            label="Buscar (ref. externa o email)"
            prepend-inner-icon="mdi-magnify"
            hide-details
            clearable
            density="compact"
            style="max-width: 320px"
          />
          <v-select
            v-model="filters.status"
            :items="statusOptions"
            label="Estado"
            hide-details
            clearable
            density="compact"
            style="max-width: 220px"
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

      <template #[`item.customer`]="{ item }">
        <div>{{ (item as Order).customer?.full_name ?? '—' }}</div>
        <div class="text-caption text-medium-emphasis">
          {{ (item as Order).customer?.email ?? '' }}
        </div>
      </template>

      <template #[`item.event`]="{ item }">
        {{ (item as Order).event?.name ?? '—' }}
      </template>

      <template #[`item.amount`]="{ item }">
        {{ formatAmount(item as Order) }}
      </template>

      <template #[`item.payment_status`]="{ item }">
        <OrderStatusChip :value="(item as Order).payment_status" />
      </template>

      <template #[`item.created_at`]="{ item }">
        {{ formatDate((item as Order).created_at) }}
      </template>

      <template #[`item.actions`]="{ item }">
        <v-btn
          icon="mdi-eye"
          size="small"
          variant="text"
          @click="router.push(`/orders/${(item as Order).id}`)"
        />
      </template>
    </AppDataTable>
  </div>
</template>
