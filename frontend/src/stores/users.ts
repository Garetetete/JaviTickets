import { createCrudStore } from './createCrudStore'
import * as api from '@/api/users'
import type { AdminUser } from '@/types/domain'
import type { UserPayload } from '@/types/api'

export const useUsersStore = createCrudStore<AdminUser, UserPayload>('users', {
  getAll: api.getUsers,
  getOne: api.getUser,
  create: api.createUser,
  update: api.updateUser,
  remove: api.deleteUser,
  restore: api.restoreUser,
})
