import { reactive } from 'vue'

export interface ConfirmOptions {
  title: string
  message: string
  confirmText?: string
  cancelText?: string
  color?: string
}

interface ConfirmState extends ConfirmOptions {
  open: boolean
  resolve: ((value: boolean) => void) | null
}

// Estado singleton: AppConfirmDialog (en App.vue) lo consume y resuelve la promesa.
const state = reactive<ConfirmState>({
  open: false,
  title: '',
  message: '',
  confirmText: 'Confirmar',
  cancelText: 'Cancelar',
  color: 'error',
  resolve: null,
})

export function useConfirm() {
  function confirm(options: ConfirmOptions): Promise<boolean> {
    state.title = options.title
    state.message = options.message
    state.confirmText = options.confirmText ?? 'Confirmar'
    state.cancelText = options.cancelText ?? 'Cancelar'
    state.color = options.color ?? 'error'
    state.open = true
    return new Promise<boolean>((resolve) => {
      state.resolve = resolve
    })
  }

  function respond(value: boolean) {
    state.open = false
    state.resolve?.(value)
    state.resolve = null
  }

  return { state, confirm, respond }
}
