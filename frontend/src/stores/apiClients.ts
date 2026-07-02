import { createCrudStore } from './createCrudStore'
import * as api from '@/api/apiClients'
import type { ApiClient } from '@/types/domain'
import type { ApiClientPayload } from '@/types/api'

export const useApiClientsStore = createCrudStore<ApiClient, ApiClientPayload>('apiClients', {
  getAll: api.getApiClients,
  getOne: api.getApiClient,
  create: api.createApiClient,
  update: api.updateApiClient,
  remove: api.deleteApiClient,
  restore: api.restoreApiClient,
})
