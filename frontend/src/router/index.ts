import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const adminMeta = { requiresAuth: true, role: 'admin', layout: 'admin' as const }

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { requiresAuth: false },
  },
  { path: '/', redirect: '/dashboard' },

  { path: '/dashboard', name: 'dashboard', component: () => import('@/views/DashboardView.vue'), meta: adminMeta },

  { path: '/tours', name: 'tours', component: () => import('@/views/tours/ToursListView.vue'), meta: adminMeta },
  { path: '/tours/create', name: 'tours.create', component: () => import('@/views/tours/TourFormView.vue'), meta: adminMeta },
  { path: '/tours/:id/edit', name: 'tours.edit', component: () => import('@/views/tours/TourFormView.vue'), meta: adminMeta },

  { path: '/events', name: 'events', component: () => import('@/views/events/EventsListView.vue'), meta: adminMeta },
  { path: '/events/create', name: 'events.create', component: () => import('@/views/events/EventFormView.vue'), meta: adminMeta },
  { path: '/events/:id/edit', name: 'events.edit', component: () => import('@/views/events/EventFormView.vue'), meta: adminMeta },
  { path: '/events/:id/seats', name: 'events.seats', component: () => import('@/views/events/EventSeatsView.vue'), meta: adminMeta },

  { path: '/ticket-types', name: 'ticketTypes', component: () => import('@/views/ticketTypes/TicketTypesListView.vue'), meta: adminMeta },
  { path: '/ticket-types/create', name: 'ticketTypes.create', component: () => import('@/views/ticketTypes/TicketTypeFormView.vue'), meta: adminMeta },
  { path: '/ticket-types/:id/edit', name: 'ticketTypes.edit', component: () => import('@/views/ticketTypes/TicketTypeFormView.vue'), meta: adminMeta },

  { path: '/orders', name: 'orders', component: () => import('@/views/orders/OrdersListView.vue'), meta: adminMeta },
  { path: '/orders/:id', name: 'orders.detail', component: () => import('@/views/orders/OrderDetailView.vue'), meta: adminMeta },

  { path: '/tickets', name: 'tickets', component: () => import('@/views/tickets/TicketsListView.vue'), meta: adminMeta },

  { path: '/scans', name: 'scans', component: () => import('@/views/scans/ScansView.vue'), meta: adminMeta },
  { path: '/audit-logs', name: 'auditLogs', component: () => import('@/views/auditLogs/AuditLogsView.vue'), meta: adminMeta },

  { path: '/api-clients', name: 'apiClients', component: () => import('@/views/apiClients/ApiClientsListView.vue'), meta: adminMeta },
  { path: '/api-clients/create', name: 'apiClients.create', component: () => import('@/views/apiClients/ApiClientFormView.vue'), meta: adminMeta },
  { path: '/api-clients/:id/edit', name: 'apiClients.edit', component: () => import('@/views/apiClients/ApiClientFormView.vue'), meta: adminMeta },

  { path: '/users', name: 'users', component: () => import('@/views/users/UsersListView.vue'), meta: adminMeta },
  { path: '/users/create', name: 'users.create', component: () => import('@/views/users/UserFormView.vue'), meta: adminMeta },
  { path: '/users/:id/edit', name: 'users.edit', component: () => import('@/views/users/UserFormView.vue'), meta: adminMeta },

  {
    path: '/validate',
    name: 'validate',
    component: () => import('@/views/gate/ValidateView.vue'),
    meta: { requiresAuth: true, role: 'gate,admin', layout: 'gate' },
  },

  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

/** Ruta raíz según el rol del usuario. */
function homeFor(role: string | null): string {
  return role === 'gate' ? '/validate' : '/dashboard'
}

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  if (!auth.ready) await auth.init()

  const requiresAuth = to.meta.requiresAuth !== false

  // 1. Ruta protegida sin sesión -> login.
  if (requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: to.fullPath !== '/' ? { redirect: to.fullPath } : undefined }
  }

  // 3. Ya autenticado en /login -> raíz del rol.
  if (to.name === 'login' && auth.isAuthenticated) {
    return homeFor(auth.role)
  }

  // 2. Ruta con restricción de rol que el usuario no cumple.
  const required = to.meta.role as string | undefined
  if (required && auth.isAuthenticated) {
    const allowed = required.split(',').map((r) => r.trim())
    if (auth.role && !allowed.includes(auth.role)) {
      return homeFor(auth.role)
    }
  }

  return true
})

export default router
