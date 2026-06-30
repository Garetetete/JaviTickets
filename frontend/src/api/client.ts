import axios, { type AxiosError, type InternalAxiosRequestConfig } from 'axios'
import type { ValidationErrors } from '@/types/api'

/** Clave del token JWT en localStorage (ver spec arquitectura §3). */
export const TOKEN_KEY = 'admin_token'

const client = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  timeout: 15_000,
  headers: { Accept: 'application/json' },
})

/* --- Interceptor de petición: añade el Bearer token si existe. --- */
client.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY)
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

/* --- Interceptor de respuesta: refresh en 401, normaliza 422. --- */
client.interceptors.response.use(
  (response) => response,
  async (error: AxiosError<{ message?: string; errors?: ValidationErrors }>) => {
    const original = error.config as (InternalAxiosRequestConfig & { _retry?: boolean }) | undefined
    const status = error.response?.status

    // Los endpoints de auth gestionan su propio 401: un login/refresh fallido NO
    // debe disparar el ciclo de refresh/logout (causaría una llamada extra y
    // ocultaría el error real de credenciales).
    const url = original?.url ?? ''
    const isAuthEndpoint = url.includes('/admin/login') || url.includes('/admin/refresh')

    // 401 -> intentar refresh una sola vez, luego logout.
    if (status === 401 && original && !original._retry && !isAuthEndpoint) {
      original._retry = true
      try {
        const { useAuthStore } = await import('@/stores/auth')
        const auth = useAuthStore()
        const ok = await auth.refresh()
        if (ok) {
          original.headers.Authorization = `Bearer ${localStorage.getItem(TOKEN_KEY)}`
          return client(original)
        }
        await auth.logout()
      } catch {
        const { useAuthStore } = await import('@/stores/auth')
        await useAuthStore().logout()
      }
    }

    // 422 -> adjunta los errores de validación normalizados al error.
    if (status === 422) {
      ;(error as AxiosError & { validationErrors?: ValidationErrors }).validationErrors =
        error.response?.data?.errors ?? {}
    }

    return Promise.reject(error)
  },
)

export default client
