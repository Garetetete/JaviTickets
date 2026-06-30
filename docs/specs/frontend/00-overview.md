# Spec Frontend — Visión general y fases (`docs/specs/frontend/00-overview.md`)

> **Metodología SDD.** Esta spec manda. No se codifica una pantalla sin tener su spec aprobada.
> No avanzar de fase sin cumplir el criterio de aceptación de la anterior.
>
> **Estado:** implementado en [`frontend/`](../../../frontend/). Fases F-0…F-9 completas
> (build `vue-tsc + vite` en verde; 11 tests unitarios de composables/stores; CI en
> `.github/workflows/frontend-ci.yml`). F-10 con cobertura base, ampliable.
> El frontend se alineó con la forma **real** de la API, que difiere del borrador de tipos de
> esta spec en varios campos (ver `frontend/README.md` § Notas de integración).

---

## 1. Qué es

Panel de administración **SPA** (Single Page Application) que consume la API REST del sistema QR Ticketing.
Dos roles de usuario:
- **admin** — acceso completo al panel (todas las rutas bajo `/admin/*`).
- **gate** — solo acceso a la pantalla de validación de tickets en puerta (`/validate`).

La SPA **no genera ni valida QR** directamente; todo lo delega a la API.

---

## 2. Stack — decisiones cerradas

| Capa | Tecnología | Versión mínima |
|---|---|---|
| Runtime | Node.js | 20 LTS |
| Framework UI | Vue | 3.4 + Composition API + `<script setup>` |
| Componentes | Vuetify | 3.x (Material Design 3) |
| Estado global | Pinia | 2.x |
| Enrutamiento | Vue Router | 4.x |
| Build | Vite | 5.x |
| HTTP | Axios | 1.x |
| Iconos | Material Design Icons (`@mdi/font`) | vía Vuetify |
| Validación formularios | Vuetify built-in rules + composable `useValidation` | — |
| Testing | Vitest + Vue Test Utils | — |
| Linting | ESLint + plugin `vue` + `@typescript-eslint` (opcional) | — |

**Decisiones de diseño:**
- TypeScript opcional pero recomendado desde la fase 1 (los stores usan tipado explícito).
- Sin SSR ni SSG; la app es cliente puro desplegada como archivos estáticos.
- `<script setup>` obligatorio en todos los componentes.
- Sin librerías de formularios externas (Vuetify cubre el 100% del caso de uso).
- Los composables son la unidad de lógica reutilizable; los componentes solo orquestan UI.

---

## 3. Estructura de carpetas

```
frontend/
├── public/
│   └── favicon.ico
├── src/
│   ├── api/                    # Capa HTTP — un archivo por recurso de la API
│   │   ├── client.ts           # Instancia axios + interceptores JWT
│   │   ├── auth.ts
│   │   ├── tours.ts
│   │   ├── events.ts
│   │   ├── ticketTypes.ts
│   │   ├── orders.ts
│   │   ├── tickets.ts
│   │   ├── seats.ts
│   │   ├── scans.ts
│   │   ├── dashboard.ts
│   │   ├── auditLogs.ts
│   │   ├── apiClients.ts
│   │   └── users.ts
│   ├── stores/                 # Pinia stores
│   │   ├── auth.ts             # token JWT, usuario, rol
│   │   ├── tours.ts
│   │   ├── events.ts
│   │   ├── ticketTypes.ts
│   │   ├── orders.ts
│   │   ├── tickets.ts
│   │   ├── seats.ts
│   │   ├── scans.ts
│   │   ├── dashboard.ts
│   │   ├── apiClients.ts
│   │   └── users.ts
│   ├── composables/            # Lógica reutilizable sin estado global
│   │   ├── useApi.ts           # wrapper genérico: loading, error, execute
│   │   ├── useConfirm.ts       # diálogo de confirmación imperativo
│   │   ├── useSnackbar.ts      # notificaciones toast
│   │   ├── usePagination.ts    # estado de paginación (page, itemsPerPage, sortBy)
│   │   └── useFilters.ts       # estado de filtros con debounce
│   ├── router/
│   │   └── index.ts            # rutas + guards de autenticación y rol
│   ├── layouts/
│   │   ├── AdminLayout.vue     # nav lateral + toolbar + outlet
│   │   └── GateLayout.vue      # layout minimalista para la vista de puerta
│   ├── views/
│   │   ├── LoginView.vue
│   │   ├── DashboardView.vue
│   │   ├── tours/
│   │   │   ├── ToursListView.vue
│   │   │   └── TourFormView.vue
│   │   ├── events/
│   │   │   ├── EventsListView.vue
│   │   │   ├── EventFormView.vue
│   │   │   └── EventSeatsView.vue
│   │   ├── ticketTypes/
│   │   │   ├── TicketTypesListView.vue
│   │   │   └── TicketTypeFormView.vue
│   │   ├── orders/
│   │   │   ├── OrdersListView.vue
│   │   │   └── OrderDetailView.vue
│   │   ├── tickets/
│   │   │   └── TicketsListView.vue
│   │   ├── scans/
│   │   │   └── ScansView.vue
│   │   ├── auditLogs/
│   │   │   └── AuditLogsView.vue
│   │   ├── apiClients/
│   │   │   ├── ApiClientsListView.vue
│   │   │   └── ApiClientFormView.vue
│   │   ├── users/
│   │   │   ├── UsersListView.vue
│   │   │   └── UserFormView.vue
│   │   └── gate/
│   │       └── ValidateView.vue
│   ├── components/             # Componentes reutilizables
│   │   ├── common/
│   │   │   ├── AppSnackbar.vue
│   │   │   ├── AppConfirmDialog.vue
│   │   │   ├── AppDataTable.vue    # v-data-table-server wrapeado
│   │   │   ├── AppStatusChip.vue
│   │   │   ├── AppSoftDeleteBadge.vue
│   │   │   └── AppEmptyState.vue
│   │   ├── tours/
│   │   │   └── TourFormDialog.vue  # form en modal (alternativo a view)
│   │   ├── orders/
│   │   │   ├── OrderStatusChip.vue
│   │   │   └── ReceiptUploadDialog.vue
│   │   └── tickets/
│   │       └── TicketStatusChip.vue
│   ├── types/                  # Tipos TypeScript compartidos
│   │   ├── api.ts              # tipos de respuesta de la API
│   │   └── domain.ts           # Tour, Event, Order, Ticket, etc.
│   ├── plugins/
│   │   ├── vuetify.ts          # configuración tema Vuetify
│   │   └── axios.ts            # alias re-exporta api/client.ts
│   ├── App.vue
│   └── main.ts
├── index.html
├── vite.config.ts
├── tsconfig.json               # (si se usa TS)
└── package.json
```

---

## 4. Fases SDD

| Fase | Descripción | Criterio de aceptación |
|---|---|---|
| F-0 | Setup: Vite + Vue 3 + Vuetify 3 + Pinia + Vue Router + Axios. Estructura de carpetas. `api/client.ts` con interceptor JWT. | `npm run dev` levanta; ruta `/` muestra placeholder con Vuetify funcional. |
| F-1 | Auth: `LoginView`, store `auth`, guard de router, `AdminLayout` + `GateLayout`. | Login exitoso → redirige por rol. Logout limpia token. Refresh automático. Ruta protegida sin token → `/login`. |
| F-2 | Dashboard: `DashboardView` con tarjetas de métricas (`GET /admin/dashboard/metrics`). | Datos reales de la API. Loading skeleton visible. |
| F-3 | Tours: CRUD completo + soft-delete + restore. | Listado paginado, formulario inline (dialog), acciones void/restore con confirmación. |
| F-4 | Events + Ticket Types: CRUD + soft-delete. Gestión de asientos (`EventSeatsView`). | Formulario de evento incluye `seating_type`. Vista de asientos muestra estado libre/ocupado + generador por rango. |
| F-5 | Orders: listado con filtros, detalle, verificar/rechazar pago manual, ver desprendibles. | Flujo completo verificar → muestra tickets emitidos. Descarga de desprendible funciona. |
| F-6 | Tickets: listado + void + reissue + export CSV. | Export descarga un `.csv` real. Acciones con confirmación. |
| F-7 | Scans + Audit Logs: listado de escaneos y logs. | Filtros por evento funcionan. Paginación server-side. |
| F-8 | API Clients + Users: CRUD + rotate-secret. | Rotate secret muestra el nuevo secret **una sola vez** en un modal no copiable por defecto. |
| F-9 | Gate: `ValidateView` (`POST /tickets/validate`). | Escaneo QR manual o por cámara → resultado visual inmediato (verde/rojo). |
| F-10 | QA + pulido: tests unitarios de composables y stores, tests E2E críticos (login, crear orden, validar ticket). | ≥ 80% cobertura en composables/stores. CI pasa. |

---

## 5. Convenciones globales

- **Nombres de archivos:** PascalCase para componentes/vistas (`TourFormView.vue`), camelCase para el resto (`useApi.ts`, `tours.ts`).
- **Props:** tipadas siempre; definir con `defineProps<{...}>()`.
- **Emits:** tipados con `defineEmits<{...}>()`.
- **Sin `Options API`** en ningún componente nuevo.
- **Comunicación:** padre → hijo por props; hijo → padre por emits; estado global por Pinia; comunicación cross-component por store o `provide/inject` cuando sea local.
- **Manejo de errores HTTP:** el interceptor de Axios captura 401 (refresh o logout) y 422 (mapea errores a campos). Los errores no capturados llegan al composable `useApi` que los expone en `error`.
- **Paginación:** siempre server-side (`v-data-table-server`). Nunca cargar toda la colección.
- **Soft delete:** los listados muestran botón "Ver eliminados" que agrega `?with_trashed=1`. Las filas eliminadas tienen fondo diferente (`AppSoftDeleteBadge`).
- **Confirmaciones destructivas:** toda acción de eliminar, void, reissue, rotate-secret pasa por `useConfirm` (dialog modal antes de ejecutar).
- **Feedback:** toda mutación (POST/PUT/DELETE) muestra un snackbar de éxito o error vía `useSnackbar`.
