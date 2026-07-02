import { describe, it, expect } from 'vitest'
import { useSnackbar } from '../useSnackbar'

describe('useSnackbar', () => {
  it('notify agrega un mensaje a la cola', () => {
    const { queue, notify } = useSnackbar()
    const before = queue.length
    notify({ message: 'hola' })
    expect(queue.length).toBe(before + 1)
    expect(queue[queue.length - 1].message).toBe('hola')
    expect(queue[queue.length - 1].color).toBe('success')
  })

  it('success y error usan el color correcto', () => {
    const { queue, success, error } = useSnackbar()
    success('ok')
    expect(queue[queue.length - 1].color).toBe('success')
    error('mal')
    expect(queue[queue.length - 1].color).toBe('error')
  })

  it('dismiss elimina el mensaje por id', () => {
    const { queue, notify, dismiss } = useSnackbar()
    notify({ message: 'temporal' })
    const id = queue[queue.length - 1].id
    const len = queue.length
    dismiss(id)
    expect(queue.length).toBe(len - 1)
    expect(queue.find((m) => m.id === id)).toBeUndefined()
  })
})
