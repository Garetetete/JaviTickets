import client from './client'
import type { AdminUser } from '@/types/domain'

export interface LoginResponse {
  access_token: string
  token_type: string
  expires_in: number
  user: AdminUser
}

export function login(email: string, password: string) {
  return client.post<LoginResponse>('/admin/login', { email, password }).then((r) => r.data)
}

export function me() {
  return client.get<AdminUser>('/admin/me').then((r) => r.data)
}

export function logout() {
  return client.post('/admin/logout').then((r) => r.data)
}

export function refresh() {
  return client.post<LoginResponse>('/admin/refresh').then((r) => r.data)
}
