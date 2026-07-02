import { describe, it, expect } from 'vitest'
import { useConfirm } from '../useConfirm'

describe('useConfirm', () => {
  it('confirm abre el diálogo con las opciones dadas', () => {
    const { state, confirm } = useConfirm()
    confirm({ title: 'Eliminar', message: '¿Seguro?', confirmText: 'Sí' })
    expect(state.open).toBe(true)
    expect(state.title).toBe('Eliminar')
    expect(state.confirmText).toBe('Sí')
  })

  it('respond(true) resuelve la promesa en true y cierra', async () => {
    const { confirm, respond } = useConfirm()
    const p = confirm({ title: 't', message: 'm' })
    respond(true)
    await expect(p).resolves.toBe(true)
  })

  it('respond(false) resuelve en false', async () => {
    const { state, confirm, respond } = useConfirm()
    const p = confirm({ title: 't', message: 'm' })
    respond(false)
    await expect(p).resolves.toBe(false)
    expect(state.open).toBe(false)
  })

  it('usa textos por defecto cuando no se especifican', () => {
    const { state, confirm } = useConfirm()
    confirm({ title: 't', message: 'm' })
    expect(state.confirmText).toBe('Confirmar')
    expect(state.cancelText).toBe('Cancelar')
    expect(state.color).toBe('error')
  })
})
