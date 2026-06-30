import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { createCrudStore } from '../createCrudStore'

interface Foo {
  id: number
  name: string
  deleted_at: string | null
}

function makeApi() {
  return {
    getAll: vi.fn(async () => ({
      data: [{ id: 1, name: 'a', deleted_at: null }] as Foo[],
      meta: { current_page: 1, last_page: 1, per_page: 20, total: 1 },
    })),
    getOne: vi.fn(async (id: number) => ({ id, name: 'a', deleted_at: null }) as Foo),
    create: vi.fn(async (d: { name: string }) => ({ id: 2, name: d.name, deleted_at: null }) as Foo),
    update: vi.fn(async (id: number, d: { name: string }) => ({ id, name: d.name, deleted_at: null }) as Foo),
    remove: vi.fn(async () => undefined),
    restore: vi.fn(async (id: number) => ({ id, name: 'a', deleted_at: null }) as Foo),
  }
}

describe('createCrudStore', () => {
  beforeEach(() => setActivePinia(createPinia()))

  it('fetchAll llena items y meta', async () => {
    const useStore = createCrudStore<Foo, { name: string }>('foo1', makeApi())
    const store = useStore()
    await store.fetchAll()
    expect(store.items).toHaveLength(1)
    expect(store.meta?.total).toBe(1)
    expect(store.loading).toBe(false)
  })

  it('create antepone el item', async () => {
    const useStore = createCrudStore<Foo, { name: string }>('foo2', makeApi())
    const store = useStore()
    await store.fetchAll()
    await store.create({ name: 'nuevo' })
    expect(store.items[0].name).toBe('nuevo')
  })

  it('remove marca deleted_at en vez de quitar la fila', async () => {
    const useStore = createCrudStore<Foo, { name: string }>('foo3', makeApi())
    const store = useStore()
    await store.fetchAll()
    await store.remove(1)
    expect(store.items[0].deleted_at).not.toBeNull()
  })

  it('update reemplaza el item por id', async () => {
    const useStore = createCrudStore<Foo, { name: string }>('foo4', makeApi())
    const store = useStore()
    await store.fetchAll()
    await store.update(1, { name: 'editado' })
    expect(store.items[0].name).toBe('editado')
  })
})
