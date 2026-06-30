<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useApiClientsStore } from '@/stores/apiClients'
import { usePagination, type SortItem } from '@/composables/usePagination'
import { useFilters } from '@/composables/useFilters'
import { useConfirm } from '@/composables/useConfirm'
import { useSnackbar } from '@/composables/useSnackbar'
import * as apiClientsApi from '@/api/apiClients'
import AppDataTable from '@/components/common/AppDataTable.vue'
import AppSoftDeleteBadge from '@/components/common/AppSoftDeleteBadge.vue'
import SecretRevealDialog from '@/components/apiClients/SecretRevealDialog.vue'
import type { ApiClient } from '@/types/domain'

const router = useRouter()
const store = useApiClientsStore()
const { confirm } = useConfirm()
const { success, error } = useSnackbar()
const pagination = usePagination()
const { filters, debouncedFilters } = useFilters({ search: '' })
const withTrashed = ref(false)

const revealOpen = ref(false)
const revealedSecret = ref<string | null>(null)

const headers = [
  { title: 'ID', key: 'id', width: 80 },
  { title: 'Nombre', key: 'name' },
  { title: 'client_id', key: 'client_id' },
  { title: 'Scopes', key: 'scopes', sortable: false },
  { title: 'Estado', key: 'status', sortable: false },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' as const },
]

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

async function removeClient(client: ApiClient) {
  const ok = await confirm({
    title: 'Eliminar cliente API',
    message: `¿Eliminar el cliente "${client.name}"? Podrás restaurarlo después.`,
    confirmText: 'Eliminar',
  })
  if (!ok) return
  try {
    await store.remove(client.id)
    success('Cliente API eliminado')
    if (!withTrashed.value) load()
  } catch {
    error('No se pudo eliminar el cliente API')
  }
}

async function restoreClient(client: ApiClient) {
  try {
    await store.restore(client.id)
    success('Cliente API restaurado')
  } catch {
    error('No se pudo restaurar el cliente API')
  }
}

async function rotateSecret(client: ApiClient) {
  const ok = await confirm({
    title: 'Rotar secret',
    message: 'El secret actual quedará inválido y los sistemas que lo usen dejarán de autenticarse. ¿Continuar?',
    confirmText: 'Rotar secret',
  })
  if (!ok) return
  try {
    const res = await apiClientsApi.rotateSecret(client.id)
    if (res.client_secret) {
      revealedSecret.value = res.client_secret
      revealOpen.value = true
    } else {
      error('La API no devolvió el nuevo secret')
    }
  } catch {
    error('No se pudo rotar el secret')
  }
}

function onRevealClosed(open: boolean) {
  revealOpen.value = open
  if (!open) revealedSecret.value = null
}

onMounted(load)
</script>

<template>
  <div>
    <div class="d-flex align-center mb-4">
      <h1 class="text-h5">Clientes API</h1>
      <v-spacer />
      <v-btn color="primary" prepend-icon="mdi-plus" @click="router.push('/api-clients/create')">
        Nuevo cliente
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

      <template #[`item.scopes`]="{ item }">
        <div class="d-flex flex-wrap ga-1">
          <v-chip
            v-for="scope in (item as ApiClient).scopes"
            :key="scope"
            size="x-small"
            label
            variant="tonal"
            color="primary"
          >
            {{ scope }}
          </v-chip>
        </div>
      </template>

      <template #[`item.status`]="{ item }">
        <AppSoftDeleteBadge :deleted-at="(item as ApiClient).deleted_at" />
      </template>

      <template #[`item.actions`]="{ item }">
        <v-btn
          v-if="!(item as ApiClient).deleted_at"
          icon="mdi-key-change"
          size="small"
          variant="text"
          color="warning"
          title="Rotar secret"
          @click="rotateSecret(item as ApiClient)"
        />
        <v-btn
          v-if="!(item as ApiClient).deleted_at"
          icon="mdi-pencil"
          size="small"
          variant="text"
          @click="router.push(`/api-clients/${(item as ApiClient).id}/edit`)"
        />
        <v-btn
          v-if="!(item as ApiClient).deleted_at"
          icon="mdi-delete"
          size="small"
          variant="text"
          color="error"
          @click="removeClient(item as ApiClient)"
        />
        <v-btn
          v-else
          icon="mdi-restore"
          size="small"
          variant="text"
          color="primary"
          @click="restoreClient(item as ApiClient)"
        />
      </template>
    </AppDataTable>

    <SecretRevealDialog
      :model-value="revealOpen"
      :secret="revealedSecret"
      @update:model-value="onRevealClosed"
    />
  </div>
</template>
