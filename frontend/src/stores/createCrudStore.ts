import { defineStore } from 'pinia'
import { ref, type Ref } from 'vue'
import type { PaginatedResponse, PaginationParams } from '@/types/domain'

/** Contrato mínimo del módulo API que consume el store CRUD genérico. */
export interface CrudApi<T, P> {
  getAll: (params: Record<string, unknown>) => Promise<PaginatedResponse<T>>
  getOne: (id: number) => Promise<T>
  create: (data: P) => Promise<T>
  update: (id: number, data: P) => Promise<T>
  remove: (id: number) => Promise<void>
  restore: (id: number) => Promise<T>
}

type WithId = { id: number; deleted_at?: string | null }
type ListParams = PaginationParams & Record<string, unknown>

/**
 * Fábrica de stores de recurso (ver docs/specs/frontend 01 §4). El store nunca
 * hace routing ni muestra notificaciones; eso es responsabilidad de la vista.
 */
export function createCrudStore<T extends WithId, P>(id: string, api: CrudApi<T, P>) {
  return defineStore(id, () => {
    const items = ref<T[]>([]) as Ref<T[]>
    const item = ref<T | null>(null) as Ref<T | null>
    const loading = ref(false)
    const error = ref<string | null>(null)
    const meta = ref<PaginatedResponse<T>['meta'] | null>(null)
    const params = ref<ListParams>({ page: 1, per_page: 20 })

    async function fetchAll(p: ListParams = {}) {
      loading.value = true
      error.value = null
      params.value = { ...params.value, ...p }
      try {
        const res = await api.getAll(params.value)
        items.value = res.data
        meta.value = res.meta
        return res
      } catch (e) {
        error.value = (e as Error).message
        throw e
      } finally {
        loading.value = false
      }
    }

    async function fetchOne(rid: number) {
      loading.value = true
      error.value = null
      try {
        item.value = await api.getOne(rid)
        return item.value
      } finally {
        loading.value = false
      }
    }

    async function create(data: P) {
      const created = await api.create(data)
      items.value.unshift(created)
      return created
    }

    async function update(rid: number, data: P) {
      const updated = await api.update(rid, data)
      const i = items.value.findIndex((x) => x.id === rid)
      if (i !== -1) items.value[i] = updated
      item.value = updated
      return updated
    }

    async function remove(rid: number) {
      await api.remove(rid)
      const i = items.value.findIndex((x) => x.id === rid)
      if (i !== -1 && 'deleted_at' in items.value[i]) {
        ;(items.value[i] as WithId).deleted_at = new Date().toISOString()
      } else if (i !== -1) {
        items.value.splice(i, 1)
      }
    }

    async function restore(rid: number) {
      const restored = await api.restore(rid)
      const i = items.value.findIndex((x) => x.id === rid)
      if (i !== -1) items.value[i] = restored
      return restored
    }

    return { items, item, loading, error, meta, params, fetchAll, fetchOne, create, update, remove, restore }
  })
}
