# Spec Frontend — Pantallas y componentes (`docs/specs/frontend/02-screens.md`)

Cada sección documenta la vista, los endpoints que consume, el estado local, las acciones del usuario y el criterio de aceptación.
Las vistas se implementan en orden de fases (ver `00-overview.md §4`).

---

## F-1 · Auth

### `LoginView` (`/login`)

**Endpoints:** `POST /admin/login`

**Estado local:**
```ts
form: { email: string, password: string }
```

**UI:**
- Página centrada (sin `AdminLayout`).
- Tarjeta (`v-card`) con logo + `v-text-field` para email y contraseña (type=password con toggle de visibilidad) + botón "Ingresar".
- `v-btn :loading` mientras se procesa.

**Flujo:**
1. Submit → `authStore.login(email, password)`.
2. Si `role === 'admin'` → `router.push('/dashboard')`.
3. Si `role === 'gate'`  → `router.push('/validate')`.
4. Si error 401 → mostrar mensaje "Credenciales incorrectas" sobre el formulario.

**Criterio de aceptación:**
- Login correcto redirige al destino del rol.
- Login incorrecto muestra mensaje sin romper el formulario.
- Recarga con token válido en localStorage no vuelve a mostrar login.

---

## F-2 · Dashboard

### `DashboardView` (`/dashboard`)

**Endpoints:** `GET /admin/dashboard/metrics?event_id={opcional}`

**Respuesta esperada:**
```json
{
  "total_tickets": 0,
  "tickets_by_status": { "active": 0, "used": 0, "void": 0, "expired": 0 },
  "total_orders": 0,
  "orders_by_status": { "pending_payment": 0, "pending_verification": 0, "verified": 0, "rejected": 0 },
  "revenue": { "total": 0, "currency": "USD" },
  "occupancy_rate": 0.0,
  "recent_scans": []
}
```

**UI:**
- Fila de tarjetas (`v-card`) con `v-skeleton-loader` durante la carga:
  - Total de tickets / por estado (chips de color).
  - Total de órdenes / por estado.
  - Ingresos totales.
  - Tasa de ocupación (porcentaje).
- Selector de evento (`v-select` con todos los eventos) para filtrar métricas.
- Tabla de últimos escaneos (5 filas, no paginada).

**Criterio de aceptación:**
- Muestra datos reales de la API.
- Cambiar el selector de evento recarga las métricas.
- Estado vacío (sin datos) muestra `AppEmptyState` en lugar de ceros.

---

## F-3 · Tours

### `ToursListView` (`/tours`)

**Endpoints:** `GET /admin/tours`, `DELETE /admin/tours/{id}`, `POST /admin/tours/{id}/restore`

**Estado:**
- `tourStore.fetchAll(params)` al montar.
- Toggle "Mostrar eliminados" → `params.with_trashed = true`.

**UI:**
- `AppDataTable` (server-side) con columnas: ID, Nombre, Descripción (truncada), Creado, Estado (chip activo/eliminado), Acciones.
- Botón "Nuevo tour" → navega a `/tours/create`.
- Acciones por fila: Editar → `/tours/:id/edit`, Eliminar (`useConfirm`) o Restaurar si está eliminado.
- Campo de búsqueda (`v-text-field` con `useFilters`) filtra por nombre.

**Criterio de aceptación:**
- Paginación server-side funciona.
- Eliminar muestra confirmación; tras confirmar la fila muestra estado eliminado (si `with_trashed`) o desaparece.
- Restaurar vuelve el tour a estado activo sin recargar toda la página.

---

### `TourFormView` (`/tours/create` y `/tours/:id/edit`)

**Endpoints:** `POST /admin/tours`, `GET /admin/tours/{id}` (solo en edición), `PUT /admin/tours/{id}`

**Estado local:**
```ts
form: { name: string, description: string }
isEdit: boolean  // derivado de si hay :id en la ruta
```

**UI:**
- `v-form` con `v-text-field` para nombre y `v-textarea` para descripción.
- Botones "Guardar" (`:loading`) y "Cancelar" (→ `/tours`).
- En edición: carga el tour al montar; muestra skeleton durante la carga.

**Criterio de aceptación:**
- Crear exitoso → snackbar "Tour creado" → redirect a `/tours`.
- Editar exitoso → snackbar "Tour actualizado" → redirect a `/tours`.
- Errores 422 se mapean a los campos correspondientes.

---

## F-4 · Events

### `EventsListView` (`/events`)

**Endpoints:** `GET /admin/events`, `DELETE /admin/events/{id}`, `POST /admin/events/{id}/restore`

**UI:** igual al patrón de Tours. Columnas adicionales: Fecha, Ciudad, Capacidad, Tipo de asientos (`seating_type` como chip), Tour relacionado.
- Acciones adicionales: botón "Asientos" → `/events/:id/seats` (solo si `seating_type === 'seated'`).

---

### `EventFormView` (`/events/create` y `/events/:id/edit`)

**Endpoints:** `POST /admin/events`, `GET /admin/events/{id}`, `PUT /admin/events/{id}`

**Estado local:**
```ts
form: {
  tour_id: number|null
  name: string
  description: string
  date: string          // datetime ISO
  city: string
  capacity: number|null
  seating_type: 'general'|'seated'
}
```

**UI:**
- `v-select` para Tour (carga `GET /admin/tours` sin paginación, máx 100).
- `v-text-field` nombre, ciudad, descripción.
- `v-text-field type="datetime-local"` para fecha.
- `v-text-field type="number"` para capacidad.
- `v-radio-group` para `seating_type` (general / numerado).
- Si `seating_type === 'seated'` → capacidad queda bloqueada (se define por asientos).

**Criterio de aceptación:**
- Cambiar `seating_type` a `seated` desactiva el campo capacidad con tooltip explicativo.
- Formulario válido antes de enviar.

---

### `EventSeatsView` (`/events/:id/seats`)

**Endpoints:**
- `GET /admin/events/{id}/seats`
- `POST /admin/events/{id}/seats` (crear uno)
- `POST /admin/events/{id}/seats/generate` (rango)
- `DELETE /admin/seats/{id}`
- `POST /admin/seats/{id}/restore`

**UI:**
- Encabezado con nombre del evento + botones "Agregar asiento" y "Generar rango".
- Grilla visual (`v-chip-group` o tabla) con cada asiento coloreado:
  - Verde: libre.
  - Rojo/naranja: ocupado (ticket activo vinculado).
  - Gris: eliminado (soft).
- **Dialog "Agregar asiento":** campos `section`, `row`, `seat_number`.
- **Dialog "Generar rango":** campos `section`, `row_from`, `row_to`, `seats_per_row`, `prefix`.

**Criterio de aceptación:**
- Asiento ocupado no muestra botón de eliminar (o lo muestra deshabilitado con tooltip).
- Generar rango crea N asientos y refresca la grilla.
- Eliminar asiento libre pide confirmación.

---

## F-4 · Ticket Types

### `TicketTypesListView` (`/ticket-types`)

**Endpoints:** `GET /admin/ticket-types`, `DELETE /admin/ticket-types/{id}`, `POST /admin/ticket-types/{id}/restore`

**UI:** patrón estándar. Columnas: ID, Nombre, Evento, Precio + Moneda, Cupo (o "Sin límite"), Estado, Acciones.
Filtros: por evento (`v-select`).

---

### `TicketTypeFormView` (`/ticket-types/create` y `/ticket-types/:id/edit`)

**Endpoints:** `POST /admin/ticket-types`, `GET /admin/ticket-types/{id}`, `PUT /admin/ticket-types/{id}`

**Estado local:**
```ts
form: {
  event_id: number|null
  name: string
  price: number
  currency: string      // select: USD, PEN, EUR, etc.
  quota: number|null    // null = sin límite
}
```

---

## F-5 · Orders

### `OrdersListView` (`/orders`)

**Endpoints:** `GET /admin/orders`

**Filtros disponibles** (query params):
- `status` (select: todos / pending_payment / pending_verification / verified / rejected / expired)
- `event_id` (select de eventos)
- `search` (debounce, busca por external_reference o email de cliente)

**UI:**
- `AppDataTable` server-side. Columnas: ID, Ref. externa, Cliente (nombre + email), Evento, Monto, Estado (`OrderStatusChip`), Fecha, Acciones.
- Acciones: Ver detalle → `/orders/:id`.
- `OrderStatusChip`: colores por estado (pending=amarillo, verified=verde, rejected=rojo, expired=gris).

**Criterio de aceptación:**
- Filtros combinables; cambiar cualquier filtro reinicia a página 1.
- El listado muestra el nombre del cliente y el evento, no solo IDs.

---

### `OrderDetailView` (`/orders/:id`)

**Endpoints:**
- `GET /admin/orders/{id}`
- `POST /admin/orders/{id}/verify`
- `POST /admin/orders/{id}/reject`
- `GET /admin/orders/{id}/receipts`
- `GET /admin/orders/{orderId}/receipts/{receiptId}/download`

**UI — secciones:**

**1. Encabezado:** ID, referencia externa, estado (chip), fecha, botones de acción.

**Botones de acción según estado:**
| Estado actual | Acciones visibles |
|---|---|
| `pending_payment` / `pending_verification` | "Verificar pago" + "Rechazar" |
| `verified` | — |
| `rejected` | — |

- **"Verificar pago":** `useConfirm` → `POST /verify` → snackbar + refresca la orden + muestra los tickets emitidos.
- **"Rechazar":** dialog con campo `reason` (requerido) → `POST /reject` → snackbar.

**2. Datos del cliente:** tarjeta con todos los campos de `customer`.

**3. Desprendibles:** lista de `payment_receipts`. Por cada uno: tipo, fecha, botón "Descargar" → `GET .../download` (abre en nueva pestaña o descarga directa).

**4. Tickets emitidos:** tabla con `code`, `status`, `section`, `seat`. Solo visible si la orden está `verified`.

**Criterio de aceptación:**
- Verificar → tickets aparecen en la sección 4 sin recargar la página.
- Descargar desprendible abre/descarga el archivo correcto.
- Los botones de acción se ocultan en estados donde no aplican.

---

## F-6 · Tickets

### `TicketsListView` (`/tickets`)

**Endpoints:**
- `GET /admin/tickets`
- `GET /admin/tickets/export` (descarga CSV)
- `POST /admin/tickets/{id}/void`
- `POST /admin/tickets/{id}/reissue`

**Filtros:** `status`, `event_id`, `search` (por code).

**UI:**
- `AppDataTable` server-side. Columnas: Code (monospace, truncado + tooltip completo), Tipo, Evento, Cliente, Sección/Asiento, Estado (`TicketStatusChip`), Acciones.
- Botón "Exportar CSV" en la toolbar: llama a `GET /tickets/export` → descarga el archivo vía `<a>` con `download`.
- Acciones por fila:
  - `void`: solo si `status === 'active'`. Pide confirmación.
  - `reissue`: solo si `status === 'void' || status === 'expired'`. Pide confirmación.

**Criterio de aceptación:**
- Export CSV descarga el archivo real (no un JSON).
- `void` deshabilita el botón y actualiza el chip de estado en la fila sin recargar.
- `reissue` actualiza el `code` y `qr_token` en la fila.

---

## F-7 · Scans

### `ScansView` (`/scans`)

**Endpoints:** `GET /admin/scans`

**Filtros:** `event_id` (requerido para filtrar), `date_from`, `date_to`.

**UI:**
- Selector de evento (obligatorio para ver datos; sin evento seleccionado muestra `AppEmptyState` con mensaje guía).
- `AppDataTable` server-side. Columnas: Code del ticket, Evento, Resultado (chip: válido/inválido/ya-usado), Escaneado por (nombre del gate user), Fecha/hora.

**Criterio de aceptación:**
- Sin evento seleccionado no se llama a la API.
- Paginación y ordenamiento por fecha funcionan.

---

## F-7 · Audit Logs

### `AuditLogsView` (`/audit-logs`)

**Endpoints:** `GET /admin/audit-logs`

**Filtros:** `resource`, `admin_user_id`, `date_from`, `date_to`.

**UI:**
- `AppDataTable` server-side. Columnas: Usuario admin, Acción, Recurso + ID, Fecha.
- Fila expandible (`:expand-on-click`) que muestra el `payload` como JSON formateado (`<pre>`).

**Criterio de aceptación:**
- Expandir fila muestra el payload completo.
- Filtros combinables.

---

## F-8 · API Clients

### `ApiClientsListView` (`/api-clients`)

**Endpoints:** `GET /admin/api-clients`, `DELETE /admin/api-clients/{id}`, `POST /admin/api-clients/{id}/restore`

**UI:** patrón estándar. Columnas: ID, Nombre, `client_id`, Scopes (chips), Estado, Acciones.
Acción adicional por fila: "Rotar secret" (solo si activo).

**Flujo "Rotar secret":**
1. `useConfirm` con advertencia "El secret actual quedará inválido".
2. `POST /api-clients/{id}/rotate-secret`.
3. La respuesta incluye `client_secret` (solo aparece esta vez).
4. Abre **`SecretRevealDialog`**: muestra el `client_secret` en un `v-text-field readonly` con botón "Copiar al portapapeles". Advierte que no se volverá a mostrar.
5. Cerrar el dialog elimina el secret de memoria.

**Criterio de aceptación:**
- El secret solo se muestra en el dialog; nunca en la tabla.
- Cerrar el dialog sin copiar muestra un warning "¿Estás seguro? No podrás ver este secret de nuevo."
- Copiar al portapapeles funciona.

---

### `ApiClientFormView` (`/api-clients/create` y `/api-clients/:id/edit`)

**Endpoints:** `POST /admin/api-clients`, `GET /admin/api-clients/{id}`, `PUT /admin/api-clients/{id}`

**Estado local:**
```ts
form: {
  name: string
  client_id: string
  scopes: string[]     // checkboxes: orders:write, tickets:read
  webhook_url: string  // opcional
}
```
En **creación**: la respuesta incluye `client_secret` → abrir `SecretRevealDialog` automáticamente.

---

## F-8 · Users

### `UsersListView` (`/users`)

**Endpoints:** `GET /admin/users`, `DELETE /admin/users/{id}`, `POST /admin/users/{id}/restore`

**UI:** patrón estándar. Columnas: ID, Nombre, Email, Rol (chip: admin=azul, gate=verde), Estado, Acciones.
No se puede eliminar el usuario actualmente autenticado (deshabilitar botón con tooltip).

---

### `UserFormView` (`/users/create` y `/users/:id/edit`)

**Endpoints:** `POST /admin/users`, `GET /admin/users/{id}`, `PUT /admin/users/{id}`

**Estado local:**
```ts
form: {
  name: string
  email: string
  password: string          // solo en creación
  password_confirmation: string  // solo en creación
  role: 'admin'|'gate'
}
```

**Criterio de aceptación:**
- En edición no se muestran los campos de contraseña (la API no los acepta aquí).
- El rol no se puede cambiar a sí mismo si es el usuario activo.

---

## F-9 · Gate

### `ValidateView` (`/validate`)

**Endpoints:** `POST /tickets/validate`

**Request:**
```json
{ "qr_token": "string", "event_id": number }
```

**Respuesta exitosa (200):**
```json
{
  "valid": true,
  "ticket": { "code": "...", "status": "active", "ticket_type": "...", "holder": "..." },
  "scan_result": "ok"
}
```

**Respuesta inválida (422 / 200 con `valid: false`):**
```json
{ "valid": false, "reason": "already_used" | "not_found" | "void" | "expired" }
```

**UI:**
- Layout `GateLayout` (pantalla completa, pensado para tablet).
- `v-select` para elegir el evento que se está validando (carga `GET /catalog/events`).
- Campo de texto `v-text-field` para ingresar el `qr_token` manualmente (o pegar desde escáner USB).
- Botón "Validar" + shortcut `Enter`.
- **Resultado visual:**
  - ✅ Verde (`v-alert color="success"`) con nombre del titular y tipo de ticket → desaparece en 5 s.
  - ❌ Rojo (`v-alert color="error"`) con motivo traducido → desaparece en 5 s.
  - ⚠️ Naranja para `already_used` con timestamp del último escaneo.
- Historial local de los últimos 10 escaneos de la sesión (en memoria, no persistido).

**Criterio de aceptación:**
- Seleccionar evento es obligatorio; sin evento el botón validar está deshabilitado.
- El campo qr_token se limpia automáticamente tras cada validación para el siguiente escaneo.
- El resultado visual es perceptible desde 1 m de distancia (tamaño de fuente grande).
- Funciona en orientación landscape (tablet en soporte de mostrador).

---

## Componentes compartidos

### `AppDataTable.vue`
Wrapper de `v-data-table-server` que integra:
- Props: `headers`, `items`, `totalItems`, `loading`, `modelValue:options` (page, itemsPerPage, sortBy).
- Slot `top` para toolbar (búsqueda + botón principal).
- Slot `actions` por fila.
- Emit `update:options` al cambiar página/orden.

### `AppConfirmDialog.vue`
Dialog de confirmación global. Consumido por `useConfirm`.
Props: `title`, `message`, `confirmText` (def. "Confirmar"), `cancelText` (def. "Cancelar"), `color` (def. "error").

### `AppSnackbar.vue`
Snackbar global montado en `App.vue`. Se controla desde `useSnackbar`.
Apila múltiples notificaciones en cola.

### `AppStatusChip.vue`
`v-chip` genérico con mapa `value → { label, color }` por recurso:
- `OrderStatusChip`: usa colores semánticos por estado de pago.
- `TicketStatusChip`: active=verde, used=azul, void=gris, expired=naranja.

### `AppSoftDeleteBadge.vue`
Wrapper que aplica `opacity: 0.5` y un chip "Eliminado" cuando el ítem tiene `deleted_at !== null`.

### `AppEmptyState.vue`
Ilustración + título + subtítulo + slot para acción primaria.
Usado cuando un listado retorna 0 elementos.
