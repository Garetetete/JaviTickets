import { describe, it, expect } from 'vitest'
import { useApi } from '../useApi'
import type { ApiError } from '@/types/api'

function httpError(status: number, body: Record<string, unknown>): ApiError {
  return {
    response: { status, data: body },
    message: 'Request failed',
  } as unknown as ApiError
}

describe('useApi', () => {
  it('devuelve el dato y limpia loading en éxito', async () => {
    const { execute, data, loading, error } = useApi<number>()
    const result = await execute(() => Promise.resolve(42))
    expect(result).toBe(42)
    expect(data.value).toBe(42)
    expect(loading.value).toBe(false)
    expect(error.value).toBeNull()
  })

  it('captura 422 en validationErrors', async () => {
    const { execute, validationErrors } = useApi()
    const result = await execute(() =>
      Promise.reject(httpError(422, { message: 'Inválido', errors: { name: ['requerido'] } })),
    )
    expect(result).toBeNull()
    expect(validationErrors.value).toEqual({ name: ['requerido'] })
  })

  it('expone el mensaje en otros errores HTTP', async () => {
    const { execute, error } = useApi()
    const result = await execute(() => Promise.reject(httpError(500, { message: 'Boom' })))
    expect(result).toBeNull()
    expect(error.value).toBe('Boom')
  })
})
