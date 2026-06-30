<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToursStore } from '@/stores/tours'
import { usePagination, type SortItem } from '@/composables/usePagination'
import { useFilters } from '@/composables/useFilters'
import { useConfirm } from '@/composables/useConfirm'
import { useSnackbar } from '@/composables/useSnackbar'
import AppDataTable from '@/components/common/AppDataTable.vue'
import AppSoftDeleteBadge from '@/components/common/AppSoftDeleteBadge.vue'
import type { Tour } from '@/types/domain'

const router = useRouter()
const store = useToursStore()
const { confirm } = useConfirm()
const { success, error } = useSnackbar()
const pagination = usePagination()
const { filters, debouncedFilters } = useFilters({ search: '' })
const withTrashed = ref(false)

const headers = [
  { title: 'ID', key: 'id', width: 80 },
  { title: 'Nombre', key: 'name' },
  { title: 'Artista', key: 'artist_name' },
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

async function removeTour(tour: Tour) {
  const ok = await confirm({
    title: 'Eliminar tour',
    message: `¿Eliminar el tour "${tour.name}"? Podrás restaurarlo después.`,
    confirmText: 'Eliminar',
  })
  if (!ok) return
  try {
    await store.remove(tour.id)
    success('Tour eliminado')
    if (!withTrashed.value) load()
  } catch {
    error('No se pudo eliminar el tour')
  }
}

async function restoreTour(tour: Tour) {
  try {
    await store.restore(tour.id)
    success('Tour restaurado')
  } catch {
    error('No se pudo restaurar el tour')
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div class="d-flex align-center mb-4">
      <h1 class="text-h5">Tours</h1>
      <v-spacer />
      <v-btn color="primary" prepend-icon="mdi-plus" @click="router.push('/tours/create')">
        Nuevo tour
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

      <template #[`item.status`]="{ item }">
        <AppSoftDeleteBadge :deleted-at="(item as Tour).deleted_at" />
      </template>

      <template #[`item.actions`]="{ item }">
        <v-btn
          v-if="!(item as Tour).deleted_at"
          icon="mdi-pencil"
          size="small"
          variant="text"
          @click="router.push(`/tours/${(item as Tour).id}/edit`)"
        />
        <v-btn
          v-if="!(item as Tour).deleted_at"
          icon="mdi-delete"
          size="small"
          variant="text"
          color="error"
          @click="removeTour(item as Tour)"
        />
        <v-btn
          v-else
          icon="mdi-restore"
          size="small"
          variant="text"
          color="primary"
          @click="restoreTour(item as Tour)"
        />
      </template>
    </AppDataTable>
  </div>
</template>
