# Spec Frontend — Arquitectura (`docs/specs/frontend/01-architecture.md`)

Contratos de las capas transversales: cliente HTTP, stores Pinia, Vue Router y composables base.
Implementar en **Fase F-0 / F-1** antes de construir cualquier vista.

---

## 1. Cliente HTTP (`src/api/client.ts`)

### Instancia Axios
```
baseURL: import.meta.env.VITE_API_URL  // ej. http://javitickets.test/api/v1
timeout: 15_000
headers: { Accept: 'application/json' }
```

### Interceptor de petición
- Lee `authStore.token` y añade `Authorization: Bearer <token>` si existe.

### Interceptor de respuesta
- **401** con `config._retry` no marcado:
  1. Marca `config._retry = true`.
  2. Llama `authStore.refresh()`.
  3. Si el refresh falla → `authStore.logout()` → redirige a `/login`.
  4. Si el refresh tiene éxito → reintenta la petición original.
- **422** → normaliza `response.data.errors` (objeto `field: string[]`) y lo adjunta al error como `error.validationErrors`.
- El resto de errores se re-lanzan tal cual para que el composable los maneje.

### Módulos de recurso (contrato común)
Cada archivo en `src/api/` exporta funciones puras (sin estado) que devuelven una `Promise<AxiosResponse>`.
Ejemplo de contrato para `tours.ts`:
```ts
getTours(params: PaginationParams & { with_trashed?: boolean }): Promise<PaginatedResponse<Tour>>
getTour(id: number): Promise<Tour>
createTour(data: TourPayload): Promise<Tour>
updateTour(id: number, data: TourPayload): Promise<Tour>
deleteTour(id: number): Promise<void>
restoreTour(id: number): Promise<Tour>
```
El mismo patrón aplica para `events`, `ticketTypes`, `orders`, `tickets`, `seats`, `scans`, `apiClients`, `users`.

---

## 2. Tipos de dominio (`src/types/domain.ts`)

Tipos mínimos necesarios, derivados de los modelos de la API:

```ts
// Paginación genérica
interface PaginatedResponse<T> {
  data: T[]
  meta: { current_page: number; last_page: number; per_page: number; total: number }
}

interface PaginationParams {
  page?: number
  per_page?: number
  sort_by?: string
  sort_dir?: 'asc' | 'desc'
}

// Entidades de dominio (campos críticos; añadir los del spec de BD)
interface Tour      { id: number; name: string; description: string; deleted_at: string|null }
interface Event     { id: number; tour_id: number; name: string; date: string; capacity: number;
                      seating_type: 'general'|'seated'; city: string; deleted_at: string|null }
interface TicketType{ id: number; event_id: number; name: string; price: number; currency: string;
                      quota: number|null; deleted_at: string|null }
interface Order     { id: number; external_reference: string; payment_status: OrderStatus;
                      amount: number; currency: string; quantity: number;
                      customer: Customer; tickets: Ticket[]; deleted_at: string|null }
interface Ticket    { id: number; code: string; status: TicketStatus; qr_token: string;
                      section: string|null; seat: string|null; deleted_at: string|null }
interface Seat      { id: number; event_id: number; section: string; row: string|null;
                      seat_number: string; status: 'free'|'occupied' }
interface ScanLog   { id: number; ticket_id: number; scanned_at: string; result: string; gate_user_id: number|null }
interface AuditLog  { id: number; admin_user_id: number; action: string; resource: string;
                      resource_id: number|null; payload: Record<string,unknown>; created_at: string }
interface ApiClient { id: number; client_id: string; name: string; scopes: string[];
                      webhook_secret_hint: string; deleted_at: string|null }
interface AdminUser { id: number; name: string; email: string; role: 'admin'|'gate'; deleted_at: string|null }

type OrderStatus  = 'pending_payment'|'pending_verification'|'verified'|'rejected'|'expired'
type TicketStatus = 'active'|'used'|'void'|'expired'
```

---

## 3. Store de autenticación (`src/stores/auth.ts`)

Estado:
```ts
token: string | null      // JWT almacenado en localStorage
user: AdminUser | null    // datos del /admin/me
role: 'admin'|'gate'|null
```

Getters:
- `isAuthenticated`: `!!token`
- `isAdmin`: `role === 'admin'`
- `isGate`: `role === 'gate'`

Acciones:
- `login(email, password)` → POST `/admin/login` → guarda token + llama `fetchMe()`.
- `fetchMe()` → GET `/admin/me` → guarda `user` y `role`.
- `logout()` → POST `/admin/logout` → limpia estado + localStorage → redirige `/login`.
- `refresh()` → POST `/admin/refresh` → reemplaza `token`.

**Persistencia:** el token se guarda en `localStorage` (`key: 'admin_token'`). Al crear el store, si hay token en localStorage se carga y se llama `fetchMe()` (restaurar sesión en recarga).

---

## 4. Stores de recurso (patrón común)

Todos los stores de recurso siguen este patrón (ejemplo con `tours`):

```ts
// Estado
items: Tour[]
item: Tour | null          // ítem en detalle/edición
loading: boolean
error: string | null
meta: PaginatedResponse['meta'] | null
params: PaginationParams & { with_trashed?: boolean }

// Acciones
fetchAll(params?)           // GET /admin/tours — actualiza items + meta
fetchOne(id)                // GET /admin/tours/:id — actualiza item
create(data)                // POST — pushea a items si exitoso
update(id, data)            // PUT — reemplaza en items
remove(id)                  // DELETE — marca deleted_at en items (no quita del array)
restore(id)                 // POST /restore — limpia deleted_at en items
```

El store **nunca hace routing** ni muestra notificaciones; eso es responsabilidad de la vista.

---

## 5. Vue Router (`src/router/index.ts`)

### Estructura de rutas

```
/login                          → LoginView          (sin layout)
/                               → redirect → /dashboard
/dashboard                      → DashboardView       [AdminLayout, rol: admin]
/tours                          → ToursListView       [AdminLayout, rol: admin]
/tours/create                   → TourFormView        [AdminLayout, rol: admin]
/tours/:id/edit                 → TourFormView        [AdminLayout, rol: admin]
/events                         → EventsListView      [AdminLayout, rol: admin]
/events/create                  → EventFormView       [AdminLayout, rol: admin]
/events/:id/edit                → EventFormView       [AdminLayout, rol: admin]
/events/:id/seats               → EventSeatsView      [AdminLayout, rol: admin]
/ticket-types                   → TicketTypesListView [AdminLayout, rol: admin]
/ticket-types/create            → TicketTypeFormView  [AdminLayout, rol: admin]
/ticket-types/:id/edit          → TicketTypeFormView  [AdminLayout, rol: admin]
/orders                         → OrdersListView      [AdminLayout, rol: admin]
/orders/:id                     → OrderDetailView     [AdminLayout, rol: admin]
/tickets                        → TicketsListView     [AdminLayout, rol: admin]
/scans                          → ScansView           [AdminLayout, rol: admin]
/audit-logs                     → AuditLogsView       [AdminLayout, rol: admin]
/api-clients                    → ApiClientsListView  [AdminLayout, rol: admin]
/api-clients/create             → ApiClientFormView   [AdminLayout, rol: admin]
/api-clients/:id/edit           → ApiClientFormView   [AdminLayout, rol: admin]
/users                          → UsersListView       [AdminLayout, rol: admin]
/users/create                   → UserFormView        [AdminLayout, rol: admin]
/users/:id/edit                 → UserFormView        [AdminLayout, rol: admin]
/validate                       → ValidateView        [GateLayout, rol: gate|admin]
```

### Guards

Guard global `beforeEach`:
1. Si la ruta requiere auth (`meta.requiresAuth`) y `!authStore.isAuthenticated` → redirect `/login`.
2. Si la ruta requiere rol `meta.role` y el usuario no lo tiene → redirect a la ruta raíz de su rol (`/validate` para gate, `/dashboard` para admin).
3. Si está en `/login` y ya autenticado → redirect a raíz del rol.

Metadata de ruta:
```ts
meta: {
  requiresAuth: boolean
  role?: 'admin' | 'gate' | 'gate,admin'   // vacío = cualquier autenticado
  layout?: 'admin' | 'gate'
}
```

---

## 6. Composables base

### `useApi<T>` (`src/composables/useApi.ts`)
Abstrae el ciclo loading/error/data de una llamada API.
```ts
function useApi<T>() {
  const data    = ref<T | null>(null)
  const loading = ref(false)
  const error   = ref<string | null>(null)
  const validationErrors = ref<Record<string, string[]>>({})

  async function execute(apiFn: () => Promise<T>): Promise<T | null> {
    loading.value = true
    error.value = null
    validationErrors.value = {}
    try {
      data.value = await apiFn()
      return data.value
    } catch (e) {
      // Si es 422, expone validationErrors
      // Si es otro error HTTP, expone el mensaje
      // Siempre devuelve null en error
    } finally {
      loading.value = false
    }
  }

  return { data, loading, error, validationErrors, execute }
}
```

### `useConfirm` (`src/composables/useConfirm.ts`)
Expone `confirm(options: { title, message, confirmText?, color? }): Promise<boolean>`.
Internamente abre `AppConfirmDialog` mediante `provide/inject` o un store liviano.

### `useSnackbar` (`src/composables/useSnackbar.ts`)
Expone `notify({ message, color?: 'success'|'error'|'warning'|'info' })`.
`AppSnackbar` (montado en `App.vue`) se suscribe al estado interno.

### `usePagination` (`src/composables/usePagination.ts`)
```ts
const { page, itemsPerPage, sortBy, toParams } = usePagination()
// toParams() → { page, per_page, sort_by, sort_dir } listo para el store
```
Sincronizable con query params de la URL (`useRoute`).

### `useFilters` (`src/composables/useFilters.ts`)
Estado de filtros + debounce configurable (300 ms por defecto).
```ts
const { filters, debouncedFilters } = useFilters({ search: '', status: '', event_id: null })
// debouncedFilters se usa para disparar fetch
```

---

## 7. Layouts

### `AdminLayout.vue`
- `v-navigation-drawer` izquierdo (persistente en desktop, temporal en mobile) con menú de navegación.
- `v-app-bar` con título de sección, nombre del usuario y botón de logout.
- `<router-view>` como outlet principal.
- Menú de nav refleja las secciones: Dashboard / Tours / Eventos / Tipos de Ticket / Órdenes / Tickets / Escaneos / Logs de auditoría / API Clients / Usuarios.
- Ítem activo resaltado con `v-list-item :active`.

### `GateLayout.vue`
- Sin nav drawer. Solo `v-app-bar` con logo y botón logout.
- `<router-view>` centrado en pantalla.
- Pensado para pantalla de escaneo en tablets/móvil.

---

## 8. Manejo de errores de validación

Cuando la API devuelve `422`:
```json
{ "message": "...", "errors": { "name": ["El campo name es requerido."], "price": ["..."] } }
```
El interceptor adjunta los errores al objeto error de Axios.
`useApi` los expone en `validationErrors.value`.
Los formularios consumen `validationErrors['campo']?.[0]` en la prop `:error-messages` de `v-text-field`.

Ejemplo de binding:
```vue
<v-text-field
  v-model="form.name"
  label="Nombre"
  :error-messages="validationErrors['name']"
/>
```
