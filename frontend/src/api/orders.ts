import client from './client'
import type { Order, PaginatedResponse, PaginationParams, PaymentReceipt } from '@/types/domain'

type ListParams = PaginationParams & {
  status?: string
  event_id?: number
  search?: string
}

export function getOrders(params: ListParams = {}) {
  return client.get<PaginatedResponse<Order>>('/admin/orders', { params }).then((r) => r.data)
}

export function getOrder(id: number) {
  return client.get<Order>(`/admin/orders/${id}`).then((r) => r.data)
}

export function verifyOrder(id: number) {
  return client.post<Order>(`/admin/orders/${id}/verify`).then((r) => r.data)
}

export function rejectOrder(id: number, reason: string) {
  return client.post<Order>(`/admin/orders/${id}/reject`, { reason }).then((r) => r.data)
}

export function getReceipts(id: number) {
  return client.get<PaymentReceipt[]>(`/admin/orders/${id}/receipts`).then((r) => r.data)
}

/** URL absoluta de descarga del desprendible (se abre en pestaña con el token). */
export function receiptDownloadUrl(orderId: number, receiptId: number) {
  return `${import.meta.env.VITE_API_URL}/admin/orders/${orderId}/receipts/${receiptId}/download`
}

/** Descarga el desprendible como blob (incluye el Bearer token vía interceptor). */
export function downloadReceipt(orderId: number, receiptId: number) {
  return client
    .get(`/admin/orders/${orderId}/receipts/${receiptId}/download`, { responseType: 'blob' })
    .then((r) => r)
}
