import client from './client'
import type { AuditLog, PaginatedResponse, PaginationParams } from '@/types/domain'

type ListParams = PaginationParams & {
  resource?: string
  admin_user_id?: number
  date_from?: string
  date_to?: string
}

export function getAuditLogs(params: ListParams = {}) {
  return client.get<PaginatedResponse<AuditLog>>('/admin/audit-logs', { params }).then((r) => r.data)
}
