import client from './client'
import type { DashboardMetrics } from '@/types/api'

/** El backend exige `event_id` (ver docs/specs/frontend 02 · F-2). */
export function getMetrics(eventId: number) {
  return client
    .get<DashboardMetrics>('/admin/dashboard/metrics', { params: { event_id: eventId } })
    .then((r) => r.data)
}
