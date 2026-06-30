import { createCrudStore } from './createCrudStore'
import * as api from '@/api/ticketTypes'
import type { TicketType } from '@/types/domain'
import type { TicketTypePayload } from '@/types/api'

export const useTicketTypesStore = createCrudStore<TicketType, TicketTypePayload>('ticketTypes', {
  getAll: api.getTicketTypes,
  getOne: api.getTicketType,
  create: api.createTicketType,
  update: api.updateTicketType,
  remove: api.deleteTicketType,
  restore: api.restoreTicketType,
})
