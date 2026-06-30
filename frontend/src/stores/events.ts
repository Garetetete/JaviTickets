import { createCrudStore } from './createCrudStore'
import * as api from '@/api/events'
import type { Event } from '@/types/domain'
import type { EventPayload } from '@/types/api'

export const useEventsStore = createCrudStore<Event, EventPayload>('events', {
  getAll: api.getEvents,
  getOne: api.getEvent,
  create: api.createEvent,
  update: api.updateEvent,
  remove: api.deleteEvent,
  restore: api.restoreEvent,
})
