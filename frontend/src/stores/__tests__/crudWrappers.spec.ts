import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

const page = <T,>(rows: T[]) => ({
  data: rows,
  meta: { current_page: 1, last_page: 1, per_page: 20, total: rows.length },
})

// Cada wrapper solo mapea su módulo API a createCrudStore; basta con instanciarlo
// y ejecutar fetchAll/create para cubrir el cableado.
vi.mock('@/api/tours', () => ({
  getTours: vi.fn(async () => page([{ id: 1, name: 't', deleted_at: null }])),
  getTour: vi.fn(), createTour: vi.fn(async () => ({ id: 2, name: 'n', deleted_at: null })),
  updateTour: vi.fn(), deleteTour: vi.fn(async () => undefined), restoreTour: vi.fn(),
}))
vi.mock('@/api/events', () => ({
  getEvents: vi.fn(async () => page([{ id: 1, name: 'e', deleted_at: null }])),
  getEvent: vi.fn(), createEvent: vi.fn(), updateEvent: vi.fn(), deleteEvent: vi.fn(), restoreEvent: vi.fn(),
}))
vi.mock('@/api/ticketTypes', () => ({
  getTicketTypes: vi.fn(async () => page([{ id: 1, name: 'tt', deleted_at: null }])),
  getTicketType: vi.fn(), createTicketType: vi.fn(), updateTicketType: vi.fn(), deleteTicketType: vi.fn(), restoreTicketType: vi.fn(),
}))
vi.mock('@/api/apiClients', () => ({
  getApiClients: vi.fn(async () => page([{ id: 1, name: 'c', client_id: 'c1', scopes: [], deleted_at: null }])),
  getApiClient: vi.fn(), createApiClient: vi.fn(), updateApiClient: vi.fn(), deleteApiClient: vi.fn(), restoreApiClient: vi.fn(),
}))
vi.mock('@/api/users', () => ({
  getUsers: vi.fn(async () => page([{ id: 1, name: 'u', email: 'u@x', role: 'admin', deleted_at: null }])),
  getUser: vi.fn(), createUser: vi.fn(), updateUser: vi.fn(), deleteUser: vi.fn(), restoreUser: vi.fn(),
}))

import { useToursStore } from '../tours'
import { useEventsStore } from '../events'
import { useTicketTypesStore } from '../ticketTypes'
import { useApiClientsStore } from '../apiClients'
import { useUsersStore } from '../users'

describe('stores CRUD (wrappers)', () => {
  beforeEach(() => setActivePinia(createPinia()))

  it('cada wrapper instancia y hace fetchAll', async () => {
    for (const useStore of [
      useToursStore,
      useEventsStore,
      useTicketTypesStore,
      useApiClientsStore,
      useUsersStore,
    ]) {
      const s = useStore()
      await s.fetchAll()
      expect(s.items.length).toBe(1)
      expect(s.meta?.total).toBe(1)
    }
  })
})
