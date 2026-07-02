import client from './client'
import type { PaginatedResponse, PaginationParams, Tour } from '@/types/domain'
import type { TourPayload } from '@/types/api'

type ListParams = PaginationParams & { with_trashed?: boolean; search?: string }

export function getTours(params: ListParams = {}) {
  return client.get<PaginatedResponse<Tour>>('/admin/tours', { params }).then((r) => r.data)
}

export function getTour(id: number) {
  return client.get<Tour>(`/admin/tours/${id}`).then((r) => r.data)
}

export function createTour(data: TourPayload) {
  return client.post<Tour>('/admin/tours', data).then((r) => r.data)
}

export function updateTour(id: number, data: TourPayload) {
  return client.put<Tour>(`/admin/tours/${id}`, data).then((r) => r.data)
}

export function deleteTour(id: number) {
  return client.delete(`/admin/tours/${id}`).then(() => undefined)
}

export function restoreTour(id: number) {
  return client.post<Tour>(`/admin/tours/${id}/restore`).then((r) => r.data)
}
