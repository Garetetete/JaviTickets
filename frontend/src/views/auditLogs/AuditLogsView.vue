<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useAuditLogsStore } from '@/stores/auditLogs'
import { usePagination, type SortItem } from '@/composables/usePagination'
import { useFilters } from '@/composables/useFilters'
import AppDataTable from '@/components/common/AppDataTable.vue'
import type { AuditLog } from '@/types/domain'

const store = useAuditLogsStore()
const pagination = usePagination()
const { filters, debouncedFilters } = useFilters({
  resource: '',
  admin_user_id: '',
  date_from: '',
  date_to: '',
})

const payloadDialog = ref(false)
const selectedLog = ref<AuditLog | null>(null)

const headers = [
  { title: 'Usuario admin', key: 'admin_user', sortable: false },
  { title: 'Acción', key: 'action', sortable: false },
  { title: 'Recurso + ID', key: 'resource', sortable: false },
  { title: 'Fecha', key: 'created_at' },
  { title: 'Payload', key: 'actions', sortable: false, align: 'end' as const },
]

const selectedPayload = computed(() =>
  selectedLog.value ? JSON.stringify(selectedLog.value.payload, null, 2) : '',
)

function formatDate(value?: string): string {
  if (!value) return '—'
  const d = new Date(value)
  return Number.isNaN(d.getTime()) ? value : d.toLocaleString('es')
}

function adminName(item: AuditLog): string {
  return item.admin_user?.name ?? (item.admin_user_id != null ? String(item.admin_user_id) : '—')
}

function resourceLabel(item: AuditLog): string {
  return item.resource_id != null ? `${item.resource} #${item.resource_id}` : item.resource
}

function showPayload(item: AuditLog) {
  selectedLog.value = item
  payloadDialog.value = true
}

async function load() {
  const adminUserId = debouncedFilters.value.admin_user_id
    ? Number(debouncedFilters.value.admin_user_id)
    : undefined
  await store.fetchAll({
    ...pagination.toParams(),
    resource: debouncedFilters.value.resource || undefined,
    admin_user_id: Number.isNaN(adminUserId as number) ? undefined : adminUserId,
    date_from: debouncedFilters.value.date_from || undefined,
    date_to: debouncedFilters.value.date_to || undefined,
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

onMounted(load)
</script>

<template>
  <div>
    <div class="d-flex align-center mb-4">
      <h1 class="text-h5">Auditoría</h1>
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
            v-model="filters.resource"
            label="Recurso"
            prepend-inner-icon="mdi-magnify"
            hide-details
            clearable
            density="compact"
            style="max-width: 220px"
          />
          <v-text-field
            v-model="filters.admin_user_id"
            label="ID usuario admin"
            type="number"
            hide-details
            clearable
            density="compact"
            style="max-width: 180px"
          />
          <v-text-field
            v-model="filters.date_from"
            label="Desde"
            type="date"
            hide-details
            clearable
            density="compact"
            style="max-width: 180px"
          />
          <v-text-field
            v-model="filters.date_to"
            label="Hasta"
            type="date"
            hide-details
            clearable
            density="compact"
            style="max-width: 180px"
          />
        </div>
      </template>

      <template #[`item.admin_user`]="{ item }">
        {{ adminName(item as AuditLog) }}
      </template>

      <template #[`item.action`]="{ item }">
        {{ (item as AuditLog).action }}
      </template>

      <template #[`item.resource`]="{ item }">
        {{ resourceLabel(item as AuditLog) }}
      </template>

      <template #[`item.created_at`]="{ item }">
        {{ formatDate((item as AuditLog).created_at) }}
      </template>

      <template #[`item.actions`]="{ item }">
        <v-btn
          icon="mdi-code-json"
          size="small"
          variant="text"
          @click="showPayload(item as AuditLog)"
        />
      </template>
    </AppDataTable>

    <v-dialog v-model="payloadDialog" max-width="640">
      <v-card>
        <v-card-title>Payload</v-card-title>
        <v-card-text>
          <pre class="payload-pre">{{ selectedPayload }}</pre>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="payloadDialog = false">Cerrar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<style scoped>
.payload-pre {
  font-family: monospace;
  font-size: 0.85rem;
  white-space: pre-wrap;
  word-break: break-word;
  max-height: 60vh;
  overflow: auto;
}
</style>
