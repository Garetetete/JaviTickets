import { describe, it, expect } from 'vitest'
import { usePagination } from '../usePagination'

describe('usePagination', () => {
  it('expone parámetros por defecto', () => {
    const { page, itemsPerPage, toParams } = usePagination()
    expect(page.value).toBe(1)
    expect(itemsPerPage.value).toBe(20)
    expect(toParams()).toEqual({ page: 1, per_page: 20 })
  })

  it('respeta el per_page inicial', () => {
    const { itemsPerPage } = usePagination(50)
    expect(itemsPerPage.value).toBe(50)
  })

  it('incluye sort_by/sort_dir cuando hay orden', () => {
    const { sortBy, toParams } = usePagination()
    sortBy.value = [{ key: 'name', order: 'desc' }]
    expect(toParams()).toEqual({ page: 1, per_page: 20, sort_by: 'name', sort_dir: 'desc' })
  })

  it('setFromOptions sincroniza desde la tabla', () => {
    const p = usePagination()
    p.setFromOptions({ page: 3, itemsPerPage: 10, sortBy: [{ key: 'id', order: 'asc' }] })
    expect(p.page.value).toBe(3)
    expect(p.itemsPerPage.value).toBe(10)
    expect(p.toParams()).toMatchObject({ page: 3, per_page: 10, sort_by: 'id', sort_dir: 'asc' })
  })
})
