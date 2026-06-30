import client from './client'
import type { Seat } from '@/types/domain'
import type { SeatPayload, SeatRangePayload } from '@/types/api'

export function getSeats(eventId: number) {
  return client.get<Seat[]>(`/admin/events/${eventId}/seats`).then((r) => r.data)
}

export function createSeat(eventId: number, data: SeatPayload) {
  return client.post<Seat>(`/admin/events/${eventId}/seats`, data).then((r) => r.data)
}

export function generateSeats(eventId: number, data: SeatRangePayload) {
  return client.post<Seat[]>(`/admin/events/${eventId}/seats/generate`, data).then((r) => r.data)
}

export function deleteSeat(id: number) {
  return client.delete(`/admin/seats/${id}`).then(() => undefined)
}

export function restoreSeat(id: number) {
  return client.post<Seat>(`/admin/seats/${id}/restore`).then((r) => r.data)
}
