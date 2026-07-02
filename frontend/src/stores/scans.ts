import { defineStore } from 'pinia'
import { ref, type Ref } from 'vue'
import * as api from '@/api/scans'
import type { PaginatedResponse, PaginationParams, ScanLog } from '@/types/domain'

type ListParams = PaginationParams & Record<string, unknown>

/** Store de escaneos (solo lectura). Se lista filtrando por evento. */
export const useScansStore = defineStore('scans', () => {
  const items = ref<ScanLog[]>([]) as Ref<ScanLog[]>
  const loading = ref(false)
  const error = ref<string | null>(null)
  const meta = ref<PaginatedResponse<ScanLog>['meta'] | null>(null)
  const params = ref<ListParams>({ page: 1, per_page: 20 })

  async function fetchAll(p: ListParams = {}) {
    loading.value = true
    error.value = null
    params.value = { ...params.value, ...p }
    try {
      const res = await api.getScans(params.value)
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

  return { items, loading, error, meta, params, fetchAll }
})
