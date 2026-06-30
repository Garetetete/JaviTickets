import { describe, it, expect, beforeEach, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

const page = <T,>(rows: T[]) => ({
  data: rows,
  meta: { current_page: 1, last_page: 1, per_page: 20, total: rows.length },
})

vi.mock('@/api/orders', () => ({
  getOrders: vi.fn(async () => page([{ id: 1, external_reference: 'r1', payment_status: 'pending_verification' }])),
  getOrder: vi.fn(async () => ({ id: 1, external_reference: 'r1', payment_status: 'pending_verification' })),
  verifyOrder: vi.fn(async () => ({ id: 1, external_reference: 'r1', payment_status: 'verified', tickets: [] })),
  rejectOrder: vi.fn(async () => ({ id: 1, external_reference: 'r1', payment_status: 'rejected' })),
}))
vi.mock('@/api/tickets', () => ({
  getTickets: vi.fn(async () => page([{ id: 1, code: 'C1', status: 'active' }])),
  voidTicket: vi.fn(async () => ({ id: 1, code: 'C1', status: 'void' })),
  reissueTicket: vi.fn(async () => ({ id: 1, code: 'C2', status: 'active' })),
}))
vi.mock('@/api/scans', () => ({
  getScans: vi.fn(async () => page([{ id: 1, result: 'valid', created_at: 'x' }])),
}))
vi.mock('@/api/auditLogs', () => ({
  getAuditLogs: vi.fn(async () => page([{ id: 1, action: 'create', resource: 'tour' }])),
}))

import { useOrdersStore } from '../orders'
import { useTicketsStore } from '../tickets'
import { useScansStore } from '../scans'
import { useAuditLogsStore } from '../auditLogs'

describe('stores de recursos', () => {
  beforeEach(() => setActivePinia(createPinia()))

  it('orders: fetchAll, verify y reject actualizan el estado', async () => {
    const s = useOrdersStore()
    await s.fetchAll()
    expect(s.items).toHaveLength(1)
    expect(s.meta?.total).toBe(1)
    const v = await s.verify(1)
    expect(v.payment_status).toBe('verified')
    expect(s.item?.payment_status).toBe('verified')
    const r = await s.reject(1, 'motivo')
    expect(r.payment_status).toBe('rejected')
  })

  it('tickets: void y reissue reemplazan la fila', async () => {
    const s = useTicketsStore()
    await s.fetchAll()
    expect(s.items[0].status).toBe('active')
    await s.voidTicket(1)
    expect(s.items[0].status).toBe('void')
    await s.reissueTicket(1)
    expect(s.items[0].code).toBe('C2')
  })

  it('scans: fetchAll llena items y meta', async () => {
    const s = useScansStore()
    await s.fetchAll({ event_id: 1 })
    expect(s.items).toHaveLength(1)
    expect(s.items[0].result).toBe('valid')
  })

  it('auditLogs: fetchAll llena items', async () => {
    const s = useAuditLogsStore()
    await s.fetchAll()
    expect(s.items[0].action).toBe('create')
  })
})
