# QR Ticketing — Panel de administración (frontend)

SPA de administración que consume la API REST de QR Ticketing. Implementa el spec
[`docs/specs/frontend/`](../docs/specs/frontend/).

## Stack
Vue 3 (`<script setup>` + Composition API) · Vuetify 3 · Pinia · Vue Router 4 · Axios · Vite 5 · TypeScript · Vitest.

## Arranque
```bash
cd frontend
npm install
cp .env.example .env          # ajusta VITE_API_URL si hace falta
npm run dev                   # http://localhost:5173
```
- `VITE_API_URL` apunta a la API con su prefijo, p. ej. `http://localhost:8090/api/v1` (Docker)
  o `http://javitickets.test/api/v1` (Laragon).
- Credenciales DEV (sembradas por el backend): `admin@qrtickets.test` / `password` (admin),
  `gate@qrtickets.test` / `password` (gate).

## Scripts
| Script | Acción |
|---|---|
| `npm run dev` | Servidor de desarrollo (HMR) |
| `npm run build` | Type-check (`vue-tsc`) + build de producción a `dist/` |
| `npm run typecheck` | Solo verificación de tipos |
| `npm test` | Tests unitarios (Vitest) |
| `npm run preview` | Sirve el build de `dist/` |

## Arquitectura (resumen)
- `src/api/` — un módulo por recurso; `client.ts` es la instancia axios con interceptor JWT
  (refresh en 401, normaliza 422).
- `src/stores/` — Pinia. `auth.ts` (token + sesión) y `createCrudStore.ts` (fábrica CRUD).
- `src/composables/` — `useApi`, `useConfirm`, `useSnackbar`, `usePagination`, `useFilters`.
- `src/components/common/` — `AppDataTable` (server-side), diálogos y chips reutilizables.
- `src/layouts/` — `AdminLayout` (nav + toolbar) y `GateLayout` (pantalla de puerta).
- `src/router/` — rutas + guards por autenticación y rol (admin / gate).
- `src/views/` — una carpeta por recurso (ver spec de pantallas).

## Estado por fases (SDD)
F-0 setup, F-1 auth, F-2 dashboard, F-3 tours, F-4 events/ticket-types/asientos,
F-5 orders, F-6 tickets, F-7 scans/audit-logs, F-8 api-clients/users, F-9 gate: **implementadas**.
F-10 QA: **completa** — ESLint + 30 tests unitarios (~91% cobertura de statements en
composables/stores) + suite E2E Playwright (auth, navegación, CRUD, gate, export CSV) +
CI (lint + typecheck + tests/cobertura + build).

## Tests
```bash
npm run lint            # ESLint
npm run typecheck       # vue-tsc
npm run test            # unitarios (Vitest)
npm run test:coverage   # unitarios + cobertura
npm run test:e2e        # E2E Playwright (requiere backend en VITE_API_URL)
npm run test:e2e:headed # E2E con navegador visible
```

## Notas de integración con la API real
El frontend está alineado con la forma **real** de la API (que difiere del borrador del spec
en algunos campos): `Event.event_date` (no `date`), `slug` requerido en eventos y tipos de
ticket, `TicketType.tour_id` requerido, `ApiClient.webhook_secret` (no `webhook_url`), y las
formas de `dashboard/metrics` (`{overview, sales_by_type, revenue, scan_results}`) y
`tickets/validate` (`{result, ticket}`).
