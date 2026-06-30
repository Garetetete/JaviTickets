import client from './client'
import type { PaginatedResponse, PaginationParams, Ticket } from '@/types/domain'

type ListParams = PaginationParams & {
  status?: string
  event_id?: number
  search?: string
}

export function getTickets(params: ListParams = {}) {
  return client.get<PaginatedResponse<Ticket>>('/admin/tickets', { params }).then((r) => r.data)
}

export function voidTicket(id: number) {
  return client.post<Ticket>(`/admin/tickets/${id}/void`).then((r) => r.data)
}

export function reissueTicket(id: number) {
  return client.post<Ticket>(`/admin/tickets/${id}/reissue`).then((r) => r.data)
}

/** Descarga el CSV de tickets como blob (respeta filtros). */
export function exportTickets(params: Omit<ListParams, 'page' | 'per_page'> = {}) {
  return client.get('/admin/tickets/export', { params, responseType: 'blob' }).then((r) => r.data)
}
