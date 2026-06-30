import client from './client'
import type { ApiClient, PaginatedResponse, PaginationParams } from '@/types/domain'
import type { ApiClientPayload } from '@/types/api'

type ListParams = PaginationParams & { with_trashed?: boolean; search?: string }

/** La respuesta de creación/rotación incluye `client_secret` una sola vez. */
export interface SecretResponse extends ApiClient {
  client_secret?: string
}

export function getApiClients(params: ListParams = {}) {
  return client.get<PaginatedResponse<ApiClient>>('/admin/api-clients', { params }).then((r) => r.data)
}

export function getApiClient(id: number) {
  return client.get<ApiClient>(`/admin/api-clients/${id}`).then((r) => r.data)
}

export function createApiClient(data: ApiClientPayload) {
  return client.post<SecretResponse>('/admin/api-clients', data).then((r) => r.data)
}

export function updateApiClient(id: number, data: ApiClientPayload) {
  return client.put<ApiClient>(`/admin/api-clients/${id}`, data).then((r) => r.data)
}

export function deleteApiClient(id: number) {
  return client.delete(`/admin/api-clients/${id}`).then(() => undefined)
}

export function restoreApiClient(id: number) {
  return client.post<ApiClient>(`/admin/api-clients/${id}/restore`).then((r) => r.data)
}

export function rotateSecret(id: number) {
  return client.post<SecretResponse>(`/admin/api-clients/${id}/rotate-secret`).then((r) => r.data)
}
