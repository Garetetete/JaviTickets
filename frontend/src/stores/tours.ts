import { createCrudStore } from './createCrudStore'
import * as api from '@/api/tours'
import type { Tour } from '@/types/domain'
import type { TourPayload } from '@/types/api'

export const useToursStore = createCrudStore<Tour, TourPayload>('tours', {
  getAll: api.getTours,
  getOne: api.getTour,
  create: api.createTour,
  update: api.updateTour,
  remove: api.deleteTour,
  restore: api.restoreTour,
})
