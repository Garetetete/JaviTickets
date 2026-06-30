<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { getMetrics } from '@/api/dashboard'
import { getEvents } from '@/api/events'
import { useApi } from '@/composables/useApi'
import AppEmptyState from '@/components/common/AppEmptyState.vue'
import AppStatusChip from '@/components/common/AppStatusChip.vue'
import { SCAN_RESULT_MAP } from '@/constants/status'
import type { DashboardMetrics } from '@/types/api'
import type { Event } from '@/types/domain'

const events = ref<Event[]>([])
const selectedEvent = ref<number | null>(null)
const metrics = ref<DashboardMetrics | null>(null)
const loadingEvents = ref(true)
const { loading, error, execute } = useApi()

async function loadEvents() {
  loadingEvents.value = true
  try {
    const res = await getEvents({ per_page: 100 })
    events.value = res.data
    if (!selectedEvent.value && events.value.length) {
      selectedEvent.value = events.value[0].id
    }
  } finally {
    loadingEvents.value = false
  }
}

async function loadMetrics() {
  if (!selectedEvent.value) return
  const result = await execute(() => getMetrics(selectedEvent.value as number))
  metrics.value = result
}

watch(selectedEvent, loadMetrics)

onMounted(async () => {
  await loadEvents()
  await loadMetrics()
})

const ticketCards = (m: DashboardMetrics) => [
  { label: 'Capacidad', value: m.overview.capacity, icon: 'mdi-account-group', color: 'primary' },
  { label: 'Emitidos', value: m.overview.issued, icon: 'mdi-ticket-confirmation', color: 'info' },
  { label: 'Usados', value: m.overview.used, icon: 'mdi-check-decagram', color: 'success' },
  { label: 'Disponibles', value: m.overview.available, icon: 'mdi-ticket-outline', color: 'teal' },
]

function pct(n: number): string {
  return `${Math.round((n ?? 0) * 100)}%`
}
</script>

<template>
  <div>
    <div class="d-flex align-center flex-wrap ga-3 mb-4">
      <h1 class="text-h5">Dashboard</h1>
      <v-spacer />
      <v-select
        v-model="selectedEvent"
        :items="events"
        item-title="name"
        item-value="id"
        label="Evento"
        hide-details
        density="compact"
        style="max-width: 320px"
      />
    </div>

    <AppEmptyState
      v-if="!loading && !loadingEvents && !selectedEvent"
      title="No hay eventos"
      subtitle="Crea un evento para ver sus métricas."
      icon="mdi-chart-box-outline"
    />

    <template v-else>
      <!-- Skeleton de carga (eventos o métricas) -->
      <v-row v-if="loading || loadingEvents">
        <v-col v-for="n in 4" :key="n" cols="12" sm="6" md="3">
          <v-skeleton-loader type="card" />
        </v-col>
      </v-row>

      <v-alert v-else-if="error" type="error" variant="tonal">{{ error }}</v-alert>

      <template v-else-if="metrics">
        <!-- Tarjetas principales -->
        <v-row>
          <v-col v-for="card in ticketCards(metrics)" :key="card.label" cols="12" sm="6" md="3">
            <v-card>
              <v-card-text class="d-flex align-center">
                <v-avatar :color="card.color" variant="tonal" size="48" class="mr-4">
                  <v-icon :icon="card.icon" />
                </v-avatar>
                <div>
                  <div class="text-caption text-medium-emphasis">{{ card.label }}</div>
                  <div class="text-h5 font-weight-bold">{{ card.value }}</div>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <v-row class="mt-1">
          <v-col cols="12" sm="6" md="4">
            <v-card>
              <v-card-text>
                <div class="text-caption text-medium-emphasis">Ingresos</div>
                <div class="text-h4 font-weight-bold">{{ metrics.revenue }}</div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" sm="6" md="4">
            <v-card>
              <v-card-text>
                <div class="text-caption text-medium-emphasis">Tasa de no-show</div>
                <div class="text-h4 font-weight-bold">{{ pct(metrics.overview.no_show_rate) }}</div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" sm="6" md="4">
            <v-card>
              <v-card-text>
                <div class="text-caption text-medium-emphasis">Ocupación</div>
                <div class="text-h4 font-weight-bold">
                  {{ metrics.overview.capacity ? pct(metrics.overview.issued / metrics.overview.capacity) : '—' }}
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <!-- Ventas por tipo -->
        <v-card class="mt-4">
          <v-card-title class="text-subtitle-1">Ventas por tipo de ticket</v-card-title>
          <v-divider />
          <AppEmptyState
            v-if="!metrics.sales_by_type.length"
            title="Sin ventas aún"
            icon="mdi-cart-outline"
          />
          <v-table v-else density="comfortable">
            <thead>
              <tr><th>Tipo</th><th class="text-end">Vendidos</th><th class="text-end">Ingresos</th></tr>
            </thead>
            <tbody>
              <tr v-for="(row, i) in metrics.sales_by_type" :key="i">
                <td>{{ row.name ?? '—' }}</td>
                <td class="text-end">{{ row.sold ?? 0 }}</td>
                <td class="text-end">{{ row.revenue ?? 0 }}</td>
              </tr>
            </tbody>
          </v-table>
        </v-card>

        <!-- Resultados de escaneo -->
        <v-card class="mt-4">
          <v-card-title class="text-subtitle-1">Resultados de escaneo</v-card-title>
          <v-divider />
          <AppEmptyState
            v-if="!metrics.scan_results.length"
            title="Sin escaneos registrados"
            icon="mdi-qrcode-scan"
          />
          <v-card-text v-else class="d-flex flex-wrap ga-3">
            <div v-for="(row, i) in metrics.scan_results" :key="i" class="d-flex align-center ga-2">
              <AppStatusChip :value="row.result" :map="SCAN_RESULT_MAP" />
              <span class="text-h6">{{ row.total ?? 0 }}</span>
            </div>
          </v-card-text>
        </v-card>
      </template>
    </template>
  </div>
</template>
