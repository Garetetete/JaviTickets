import client from './client'
import type { Event, PaginatedResponse, PaginationParams } from '@/types/domain'
import type { EventPayload } from '@/types/api'

type ListParams = PaginationParams & { with_trashed?: boolean; search?: string; tour_id?: number }

export function getEvents(params: ListParams = {}) {
  return client.get<PaginatedResponse<Event>>('/admin/events', { params }).then((r) => r.data)
}

export function getEvent(id: number) {
  return client.get<Event>(`/admin/events/${id}`).then((r) => r.data)
}

export function createEvent(data: EventPayload) {
  return client.post<Event>('/admin/events', data).then((r) => r.data)
}

export function updateEvent(id: number, data: EventPayload) {
  return client.put<Event>(`/admin/events/${id}`, data).then((r) => r.data)
}

export function deleteEvent(id: number) {
  return client.delete(`/admin/events/${id}`).then(() => undefined)
}

export function restoreEvent(id: number) {
  return client.post<Event>(`/admin/events/${id}/restore`).then((r) => r.data)
}
