import client from './client'
import type { AdminUser, PaginatedResponse, PaginationParams } from '@/types/domain'
import type { UserPayload } from '@/types/api'

type ListParams = PaginationParams & { with_trashed?: boolean; search?: string; role?: string }

export function getUsers(params: ListParams = {}) {
  return client.get<PaginatedResponse<AdminUser>>('/admin/users', { params }).then((r) => r.data)
}

export function getUser(id: number) {
  return client.get<AdminUser>(`/admin/users/${id}`).then((r) => r.data)
}

export function createUser(data: UserPayload) {
  return client.post<AdminUser>('/admin/users', data).then((r) => r.data)
}

export function updateUser(id: number, data: UserPayload) {
  return client.put<AdminUser>(`/admin/users/${id}`, data).then((r) => r.data)
}

export function deleteUser(id: number) {
  return client.delete(`/admin/users/${id}`).then(() => undefined)
}

export function restoreUser(id: number) {
  return client.post<AdminUser>(`/admin/users/${id}/restore`).then((r) => r.data)
}
