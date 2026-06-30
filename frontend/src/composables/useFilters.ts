import { reactive, ref, watch } from 'vue'

/**
 * Estado de filtros con copia "debounced" para disparar fetch sin saturar la API.
 */
export function useFilters<T extends Record<string, unknown>>(initial: T, delay = 300) {
  const filters = reactive({ ...initial }) as T
  const debouncedFilters = ref<T>({ ...initial })
  let timer: ReturnType<typeof setTimeout> | undefined

  watch(
    () => ({ ...filters }),
    (val) => {
      if (timer) clearTimeout(timer)
      timer = setTimeout(() => {
        debouncedFilters.value = val as T
      }, delay)
    },
    { deep: true },
  )

  function reset() {
    Object.assign(filters, initial)
  }

  return { filters, debouncedFilters, reset }
}
