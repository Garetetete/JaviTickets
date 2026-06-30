import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { useFilters } from '../useFilters'

describe('useFilters', () => {
  beforeEach(() => vi.useFakeTimers())
  afterEach(() => vi.useRealTimers())

  it('expone los filtros iniciales', () => {
    const { filters, debouncedFilters } = useFilters({ search: '', status: '' })
    expect(filters.search).toBe('')
    expect(debouncedFilters.value.search).toBe('')
  })

  it('debouncedFilters se actualiza tras el delay', async () => {
    const { filters, debouncedFilters } = useFilters({ search: '' }, 300)
    filters.search = 'abc'
    // antes del delay no cambia
    expect(debouncedFilters.value.search).toBe('')
    await vi.advanceTimersByTimeAsync(300)
    expect(debouncedFilters.value.search).toBe('abc')
  })

  it('reset restaura los valores iniciales', () => {
    const { filters, reset } = useFilters({ search: 'x', status: 'on' })
    filters.search = 'cambiado'
    reset()
    expect(filters.search).toBe('x')
    expect(filters.status).toBe('on')
  })
})
