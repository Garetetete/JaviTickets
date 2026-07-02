import client from './client'
import type { PaginatedResponse, PaginationParams, ScanLog } from '@/types/domain'

type ListParams = PaginationParams & {
  event_id?: number
  date_from?: string
  date_to?: string
}

export function getScans(params: ListParams = {}) {
  return client.get<PaginatedResponse<ScanLog>>('/admin/scans', { params }).then((r) => r.data)
}
