import { defineStore } from 'pinia'
import { ref, type Ref } from 'vue'
import * as api from '@/api/tickets'
import type { PaginatedResponse, PaginationParams, Ticket } from '@/types/domain'

type ListParams = PaginationParams & Record<string, unknown>

/**
 * Store de tickets. Los tickets no se crean/editan/eliminan como un recurso CRUD
 * normal: solo se listan y se operan vía `void`/`reissue` (que devuelven el ticket
 * actualizado). Por eso no usa `createCrudStore`.
 */
export const useTicketsStore = defineStore('tickets', () => {
  const items = ref<Ticket[]>([]) as Ref<Ticket[]>
  const loading = ref(false)
  const error = ref<string | null>(null)
  const meta = ref<PaginatedResponse<Ticket>['meta'] | null>(null)
  const params = ref<ListParams>({ page: 1, per_page: 20 })

  async function fetchAll(p: ListParams = {}) {
    loading.value = true
    error.value = null
    params.value = { ...params.value, ...p }
    try {
      const res = await api.getTickets(params.value)
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

  /** Reemplaza la fila con el ticket devuelto por la API, sin recargar. */
  function replaceRow(updated: Ticket) {
    const i = items.value.findIndex((x) => x.id === updated.id)
    if (i !== -1) items.value[i] = updated
  }

  async function voidTicket(id: number) {
    const updated = await api.voidTicket(id)
    replaceRow(updated)
    return updated
  }

  async function reissueTicket(id: number) {
    const updated = await api.reissueTicket(id)
    replaceRow(updated)
    return updated
  }

  return { items, loading, error, meta, params, fetchAll, voidTicket, reissueTicket }
})
