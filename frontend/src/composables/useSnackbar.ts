import { reactive } from 'vue'

export type SnackbarColor = 'success' | 'error' | 'warning' | 'info'

export interface SnackbarMessage {
  id: number
  message: string
  color: SnackbarColor
  timeout: number
}

// Estado singleton compartido: AppSnackbar (en App.vue) lo consume.
const state = reactive<{ queue: SnackbarMessage[] }>({ queue: [] })
let counter = 0

export function useSnackbar() {
  function notify(opts: { message: string; color?: SnackbarColor; timeout?: number }) {
    state.queue.push({
      id: ++counter,
      message: opts.message,
      color: opts.color ?? 'success',
      timeout: opts.timeout ?? 4000,
    })
  }

  function dismiss(id: number) {
    const i = state.queue.findIndex((m) => m.id === id)
    if (i !== -1) state.queue.splice(i, 1)
  }

  const success = (message: string) => notify({ message, color: 'success' })
  const error = (message: string) => notify({ message, color: 'error' })

  return { queue: state.queue, notify, dismiss, success, error }
}
