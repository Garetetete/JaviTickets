<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useUsersStore } from '@/stores/users'
import { useAuthStore } from '@/stores/auth'
import { usePagination, type SortItem } from '@/composables/usePagination'
import { useFilters } from '@/composables/useFilters'
import { useConfirm } from '@/composables/useConfirm'
import { useSnackbar } from '@/composables/useSnackbar'
import { ROLE_MAP } from '@/constants/status'
import AppDataTable from '@/components/common/AppDataTable.vue'
import AppSoftDeleteBadge from '@/components/common/AppSoftDeleteBadge.vue'
import AppStatusChip from '@/components/common/AppStatusChip.vue'
import type { AdminUser } from '@/types/domain'

const router = useRouter()
const store = useUsersStore()
const auth = useAuthStore()
const { confirm } = useConfirm()
const { success, error } = useSnackbar()
const pagination = usePagination()
const { filters, debouncedFilters } = useFilters({ search: '' })
const withTrashed = ref(false)

const headers = [
  { title: 'ID', key: 'id', width: 80 },
  { title: 'Nombre', key: 'name' },
  { title: 'Email', key: 'email' },
  { title: 'Rol', key: 'role', sortable: false },
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

function isSelf(user: AdminUser): boolean {
  return user.id === auth.user?.id
}

async function removeUser(user: AdminUser) {
  if (isSelf(user)) return
  const ok = await confirm({
    title: 'Eliminar usuario',
    message: `¿Eliminar al usuario "${user.name}"? Podrás restaurarlo después.`,
    confirmText: 'Eliminar',
  })
  if (!ok) return
  try {
    await store.remove(user.id)
    success('Usuario eliminado')
    if (!withTrashed.value) load()
  } catch {
    error('No se pudo eliminar el usuario')
  }
}

async function restoreUser(user: AdminUser) {
  try {
    await store.restore(user.id)
    success('Usuario restaurado')
  } catch {
    error('No se pudo restaurar el usuario')
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div class="d-flex align-center mb-4">
      <h1 class="text-h5">Usuarios</h1>
      <v-spacer />
      <v-btn color="primary" prepend-icon="mdi-plus" @click="router.push('/users/create')">
        Nuevo usuario
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
            label="Buscar por nombre o email"
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

      <template #[`item.role`]="{ item }">
        <AppStatusChip :value="(item as AdminUser).role" :map="ROLE_MAP" />
      </template>

      <template #[`item.status`]="{ item }">
        <AppSoftDeleteBadge :deleted-at="(item as AdminUser).deleted_at" />
      </template>

      <template #[`item.actions`]="{ item }">
        <v-btn
          v-if="!(item as AdminUser).deleted_at"
          icon="mdi-pencil"
          size="small"
          variant="text"
          @click="router.push(`/users/${(item as AdminUser).id}/edit`)"
        />
        <template v-if="!(item as AdminUser).deleted_at">
          <v-tooltip v-if="isSelf(item as AdminUser)" text="No puedes eliminar tu propio usuario" location="top">
            <template #activator="{ props }">
              <span v-bind="props">
                <v-btn
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  color="error"
                  disabled
                />
              </span>
            </template>
          </v-tooltip>
          <v-btn
            v-else
            icon="mdi-delete"
            size="small"
            variant="text"
            color="error"
            @click="removeUser(item as AdminUser)"
          />
        </template>
        <v-btn
          v-if="(item as AdminUser).deleted_at"
          icon="mdi-restore"
          size="small"
          variant="text"
          color="primary"
          @click="restoreUser(item as AdminUser)"
        />
      </template>
    </AppDataTable>
  </div>
</template>
