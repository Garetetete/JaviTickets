import { ref } from 'vue'
import type { ApiError, ValidationErrors } from '@/types/api'

/**
 * Abstrae el ciclo loading/error/data de una llamada API. Captura 422 en
 * `validationErrors` y el resto de errores en `error`. Devuelve null en error.
 */
export function useApi<T = unknown>() {
  const data = ref<T | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const validationErrors = ref<ValidationErrors>({})

  async function execute<R = T>(apiFn: () => Promise<R>): Promise<R | null> {
    loading.value = true
    error.value = null
    validationErrors.value = {}
    try {
      const result = await apiFn()
      data.value = result as unknown as T
      return result
    } catch (e) {
      const err = e as ApiError
      if (err.response?.status === 422) {
        validationErrors.value = err.validationErrors ?? err.response?.data?.errors ?? {}
        error.value = err.response?.data?.message ?? 'Datos inválidos.'
      } else {
        error.value =
          err.response?.data?.message ?? err.message ?? 'Ocurrió un error inesperado.'
      }
      return null
    } finally {
      loading.value = false
    }
  }

  return { data, loading, error, validationErrors, execute }
}
