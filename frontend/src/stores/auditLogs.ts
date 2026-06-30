import { defineStore } from 'pinia'
import { ref, type Ref } from 'vue'
import * as api from '@/api/auditLogs'
import type { AuditLog, PaginatedResponse, PaginationParams } from '@/types/domain'

type ListParams = PaginationParams & Record<string, unknown>

/** Store de logs de auditoría (solo lectura). */
export const useAuditLogsStore = defineStore('auditLogs', () => {
  const items = ref<AuditLog[]>([]) as Ref<AuditLog[]>
  const loading = ref(false)
  const error = ref<string | null>(null)
  const meta = ref<PaginatedResponse<AuditLog>['meta'] | null>(null)
  const params = ref<ListParams>({ page: 1, per_page: 20 })

  async function fetchAll(p: ListParams = {}) {
    loading.value = true
    error.value = null
    params.value = { ...params.value, ...p }
    try {
      const res = await api.getAuditLogs(params.value)
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
