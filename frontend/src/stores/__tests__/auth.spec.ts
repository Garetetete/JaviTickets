import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

vi.mock('@/api/auth', () => ({
  login: vi.fn(async () => ({
    access_token: 'tok-admin',
    token_type: 'Bearer',
    expires_in: 3600,
    user: { id: 1, name: 'Admin', email: 'a@x.test', role: 'admin', deleted_at: null },
  })),
  me: vi.fn(async () => ({ id: 1, name: 'Admin', email: 'a@x.test', role: 'admin', deleted_at: null })),
  logout: vi.fn(async () => ({})),
  refresh: vi.fn(async () => ({ access_token: 'tok-refreshed' })),
}))

vi.mock('@/router', () => ({
  default: { currentRoute: { value: { name: 'dashboard' } }, push: vi.fn() },
}))

import { useAuthStore } from '../auth'

describe('store auth', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
  })

  it('login guarda token y usuario y marca autenticado/admin', async () => {
    const auth = useAuthStore()
    expect(auth.isAuthenticated).toBe(false)
    await auth.login('a@x.test', 'pw')
    expect(auth.token).toBe('tok-admin')
    expect(auth.user?.name).toBe('Admin')
    expect(auth.isAuthenticated).toBe(true)
    expect(auth.isAdmin).toBe(true)
    expect(auth.isGate).toBe(false)
    expect(localStorage.getItem('admin_token')).toBe('tok-admin')
  })

  it('refresh reemplaza el token y devuelve true', async () => {
    const auth = useAuthStore()
    await auth.login('a@x.test', 'pw')
    const ok = await auth.refresh()
    expect(ok).toBe(true)
    expect(auth.token).toBe('tok-refreshed')
  })

  it('logout limpia token, usuario y localStorage', async () => {
    const auth = useAuthStore()
    await auth.login('a@x.test', 'pw')
    await auth.logout()
    expect(auth.token).toBeNull()
    expect(auth.user).toBeNull()
    expect(auth.isAuthenticated).toBe(false)
    expect(localStorage.getItem('admin_token')).toBeNull()
  })

  it('init restaura el usuario si hay token en localStorage', async () => {
    localStorage.setItem('admin_token', 'tok-persistido')
    setActivePinia(createPinia())
    const auth = useAuthStore()
    await auth.init()
    expect(auth.ready).toBe(true)
    expect(auth.user?.name).toBe('Admin')
  })
})
