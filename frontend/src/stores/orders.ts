import { defineStore } from 'pinia'
import { ref, type Ref } from 'vue'
import * as api from '@/api/orders'
import type { Order, PaginatedResponse, PaginationParams } from '@/types/domain'

type ListParams = PaginationParams & Record<string, unknown>

/**
 * Store de órdenes. A diferencia de los recursos CRUD, las órdenes no se crean,
 * editan ni eliminan desde el admin: solo se listan, consultan y se verifica o
 * rechaza el pago. Por eso no usa `createCrudStore`.
 */
export const useOrdersStore = defineStore('orders', () => {
  const items = ref<Order[]>([]) as Ref<Order[]>
  const item = ref<Order | null>(null) as Ref<Order | null>
  const loading = ref(false)
  const error = ref<string | null>(null)
  const meta = ref<PaginatedResponse<Order>['meta'] | null>(null)
  const params = ref<ListParams>({ page: 1, per_page: 20 })

  async function fetchAll(p: ListParams = {}) {
    loading.value = true
    error.value = null
    params.value = { ...params.value, ...p }
    try {
      const res = await api.getOrders(params.value)
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

  async function fetchOne(id: number) {
    loading.value = true
    error.value = null
    try {
      item.value = await api.getOrder(id)
      return item.value
    } finally {
      loading.value = false
    }
  }

  async function verify(id: number) {
    const updated = await api.verifyOrder(id)
    item.value = updated
    return updated
  }

  async function reject(id: number, reason: string) {
    const updated = await api.rejectOrder(id, reason)
    item.value = updated
    return updated
  }

  return { items, item, loading, error, meta, params, fetchAll, fetchOne, verify, reject }
})
