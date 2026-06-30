import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import * as authApi from '@/api/auth'
import { TOKEN_KEY } from '@/api/client'
import type { AdminUser } from '@/types/domain'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem(TOKEN_KEY))
  const user = ref<AdminUser | null>(null)
  const ready = ref(false)

  const isAuthenticated = computed(() => !!token.value)
  const role = computed(() => user.value?.role ?? null)
  const isAdmin = computed(() => role.value === 'admin')
  const isGate = computed(() => role.value === 'gate')

  function setToken(value: string | null) {
    token.value = value
    if (value) localStorage.setItem(TOKEN_KEY, value)
    else localStorage.removeItem(TOKEN_KEY)
  }

  async function login(email: string, password: string) {
    const res = await authApi.login(email, password)
    setToken(res.access_token)
    user.value = res.user
    return res.user
  }

  async function fetchMe() {
    user.value = await authApi.me()
    return user.value
  }

  async function refresh(): Promise<boolean> {
    try {
      const res = await authApi.refresh()
      setToken(res.access_token)
      if (res.user) user.value = res.user
      return true
    } catch {
      return false
    }
  }

  async function logout() {
    try {
      if (token.value) await authApi.logout()
    } catch {
      /* ignora errores de red al cerrar sesión */
    } finally {
      setToken(null)
      user.value = null
      const { default: router } = await import('@/router')
      if (router.currentRoute.value.name !== 'login') {
        router.push({ name: 'login' })
      }
    }
  }

  /** Restaura la sesión en recarga: si hay token, recupera el usuario. */
  async function init() {
    if (token.value && !user.value) {
      try {
        await fetchMe()
      } catch {
        setToken(null)
      }
    }
    ready.value = true
  }

  return {
    token,
    user,
    ready,
    isAuthenticated,
    role,
    isAdmin,
    isGate,
    login,
    fetchMe,
    refresh,
    logout,
    init,
  }
})
