import client from './client'
import type { PaginatedResponse, PaginationParams, TicketType } from '@/types/domain'
import type { TicketTypePayload } from '@/types/api'

type ListParams = PaginationParams & { with_trashed?: boolean; event_id?: number; search?: string }

export function getTicketTypes(params: ListParams = {}) {
  return client.get<PaginatedResponse<TicketType>>('/admin/ticket-types', { params }).then((r) => r.data)
}

export function getTicketType(id: number) {
  return client.get<TicketType>(`/admin/ticket-types/${id}`).then((r) => r.data)
}

export function createTicketType(data: TicketTypePayload) {
  return client.post<TicketType>('/admin/ticket-types', data).then((r) => r.data)
}

export function updateTicketType(id: number, data: TicketTypePayload) {
  return client.put<TicketType>(`/admin/ticket-types/${id}`, data).then((r) => r.data)
}

export function deleteTicketType(id: number) {
  return client.delete(`/admin/ticket-types/${id}`).then(() => undefined)
}

export function restoreTicketType(id: number) {
  return client.post<TicketType>(`/admin/ticket-types/${id}/restore`).then((r) => r.data)
}
