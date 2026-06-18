# CLAUDE.md — QR Ticketing API

Guía para agentes/colaboradores que trabajen en este repositorio. Resume **qué es**, **qué está hecho**, **qué falta** y **cómo continuar**.

> Metodología: **Spec-Driven Development (SDD)**. La especificación manda. Antes de codificar una entidad/feature nueva, revisa/actualiza su spec en `docs/specs/`. No avanzar de fase sin cumplir el criterio de aceptación de la anterior.

---

## 1. Qué es

API REST **autónoma** (Laravel 11) que es la **autoridad única de tickets/QR** para tours/eventos. Otros sistemas la consumen (tienda WordPress, escáner de puerta, etc.); **nadie genera ni valida QR fuera de aquí**.

Funciones núcleo:
- Generar tickets con **QR firmado** (HMAC, anti-falsificación, rotación de clave).
- Vender vía tienda externa con **dos flujos de pago**: manual (subir desprendible + verificación admin) y automático (webhook firmado e idempotente).
- **Aforo** por evento y cupo por tipo de ticket (validado atómicamente).
- **Validar en puerta** (anti-doble-entrada) + log de escaneos.
- Panel admin + métricas. Auth **JWT** (clientes máquina con scopes; usuarios admin/gate con roles).

Documento maestro: [`SDD-QrTicketing-Spec.md`](SDD-QrTicketing-Spec.md). Specs detalladas en [`docs/specs/`](docs/specs/).

### Decisiones cerradas
- **DB:** PostgreSQL 16.
- **Entorno:** dual — **Docker** para dev local + **Laragon** para despliegue. El código es Laravel estándar y **no depende de Docker**.
- **WordPress:** solo exponemos la API + contrato de consumo + reconciliación por `external_reference`. Sin plugin/sync bidireccional en el MVP.
- **No** hay pasarela de pago integrada (extensión futura).

---

## 2. Stack y estructura

- Laravel 11 (PHP 8.2), PostgreSQL 16, JWT (`php-open-source-saver/jwt-auth`), QR (`endroid/qr-code ^5`, vía gd).
- Arquitectura **por capas**: `Controller → Service → Repository (interface) → Repository (Eloquent) → Model`. Los Controllers nunca tocan Eloquent; los Services solo conocen interfaces.

```
qr-ticketing-api/
├── backend/                     # Laravel 11
│   ├── app/
│   │   ├── Models/              # 11 modelos Eloquent
│   │   ├── Repositories/{Contracts,Eloquent}/   # 12 interfaces + impl + base
│   │   ├── Services/            # QrService, TicketIssuance, Payment, Validation, Metrics
│   │   ├── Support/Qr/          # QrSigner (HMAC), QrVerifyResult
│   │   ├── DTOs/                # OrderData, ReceiptData, WebhookPaymentData, ValidateTicketData, ...
│   │   ├── Exceptions/          # DomainException base + concretas (status HTTP)
│   │   ├── Http/Controllers/{Api,Admin}/        # (Fase 5+)
│   │   └── Providers/RepositoryServiceProvider.php
│   ├── config/qr.php            # secreto HMAC, rotación, ventana anti-rebote
│   ├── database/migrations/     # 11 tablas de dominio
│   ├── database/seeders/        # tour demo + tipos + api_client + admin/gate
│   └── tests/{Unit,Feature}/    # 28 tests verdes
├── docker/                      # docker-compose.yml + Dockerfile + nginx.conf (entorno local)
├── scanner/                     # front de lectura de QR (pendiente, Fase 8)
└── docs/specs/                  # db/schema.md, architecture/{repositories,services}.md, api/endpoints.md
```

---

## 3. Cómo levantar

### Docker (dev local)
```bash
cd docker
docker compose up -d                 # app (php-fpm) + web (nginx :8090) + db (postgres :55432)
docker exec qr_app php artisan migrate --seed   # primera vez
```
- API: `http://localhost:8090/api/v1/...`  ·  health: `GET /api/v1/up`
- Postgres host: `localhost:55432`, db `qr_ticketing`, user `qr_user`, pass `qr_secret`
- Ejecutar artisan/composer: `docker exec qr_app php artisan ...` / `docker exec qr_app composer ...`
- **Nota Windows:** el contenedor corre como root (permisos del bind-mount). `composer` corre con `-e COMPOSER_PROCESS_TIMEOUT=0` (el unzip por bind-mount es lento). La plataforma está fijada a PHP 8.2 en `composer.json`.

### Laragon (despliegue)
Apuntar el mismo `backend/` a PHP + PostgreSQL de Laragon configurando `.env` (`DB_HOST=127.0.0.1`, credenciales locales). No se requiere Docker.

### Tests
```bash
docker exec qr_app php artisan test           # usa la DB qr_ticketing_test (phpunit.xml)
```
La DB de test se crea con: `docker exec qr_db psql -U qr_user -d qr_ticketing -c "CREATE DATABASE qr_ticketing_test;"`

### Credenciales DEV sembradas (cambiar en prod)
- Admin: `admin@qrtickets.test` / `password` (rol admin)
- Gate: `gate@qrtickets.test` / `password` (rol gate)
- API client: `client_id=wp-store-demo`, `client_secret=dev-client-secret`, `webhook_secret=dev-webhook-secret`

---

## 4. Modelo de datos (11 tablas)

`tours`, `events` (con `capacity`/aforo), `ticket_types` (precio + `quota`), `customers` (metadatos del comprador), `orders` (estado de pago + `external_reference`), `payment_receipts`, `tickets` (`code`, `qr_token`, `key_version`, `status`), `scan_logs` (append-only), `api_clients` (scopes + `webhook_secret`), `admin_users` (`role` admin/gate), `webhook_events` (append-only, idempotencia).

- SoftDeletes en tablas de negocio; logs append-only (sin soft delete).
- Detalle completo: [`docs/specs/db/schema.md`](docs/specs/db/schema.md).

---

## 5. Estado por fases (SDD)

| Fase | Descripción | Estado |
|---|---|---|
| 0 | Setup entorno (Docker + Laragon, Laravel, paquetes, esqueleto de capas, `/api/v1/up`) | ✅ Hecho |
| 1 | Specs de datos y contratos (`docs/specs/`) | ✅ Hecho |
| 2 | Migraciones + modelos + seeders (11 tablas, PostgreSQL) | ✅ Hecho |
| 3 | Capa Repository (12 interfaces + impl + bindings + tests) | ✅ Hecho |
| 4 | Services + DTOs + Excepciones + `Support/Qr` (Qr, Issuance, Payment, Validation, Metrics) | ✅ Hecho |
| 5 | **Controllers API pública** (consumida por la tienda) + Form Requests + Resources + rate limiting + `verify.webhook` | ✅ Hecho |
| 6 | **Auth JWT** (`AuthService`, login admin/gate, middleware roles/scopes) + **validación en puerta** (`POST /tickets/validate`) | ✅ Hecho |
| 7 | **Controllers Admin** (CRUD tours/events/ticket_types, verificación manual de pago, tickets, scans, métricas, api-clients, users, export CSV) | ✅ Hecho |
| 8 | **Escáner front** (cámara → decodifica → `POST /tickets/validate`) | ⬜ Pendiente (siguiente) |
| 9 | QA, seguridad (concurrencia de aforo, rate limiting, rotación de clave), README de despliegue | ⬜ Pendiente |

### Hecho — detalle
- **Entorno dual** funcionando; `GET /api/v1/up` → 200.
- **Dominio completo** en BD (11 migraciones, 11 modelos, seeders con CHICA MALA TOUR).
- **Repositorios** (12) con aforo (`countIssuedTickets`, `lockForIssue`), `findByCode`/`lockByCodeForUpdate`, `firstOrCreate`, idempotencia (`existsByExternalEventId`), `rotateSecret`, soft-delete/restore. Bindings en `RepositoryServiceProvider`.
- **Services**: firma/verificación QR con rotación; emisión con aforo atómico; pagos manual + webhook idempotente; validación anti-doble-entrada; métricas.
- **API pública (Fase 5):** `POST /client/token`, `POST /orders` (idempotente), `POST /orders/{id}/receipt`, `GET /orders/{ref}` (reconciliación), `POST /webhooks/payment` (firmado), `GET /tickets/{code}/verify|image`. Middlewares `auth.api_client` (JWT cliente), `scope:*`, `verify.webhook` (HMAC + anti-replay). Rate limiting. Resources sin envoltura `data`.
- **Auth admin/gate (Fase 6):** guard JWT `admin` (provider `admin_users`), `POST /admin/login|me|logout|refresh`, middleware `role:...`, y `POST /tickets/validate` (gate atado a su evento, admin sin restricción).
- **Panel admin (Fase 7):** `Http/Controllers/Admin/` — CRUD `tours`/`events`/`ticket-types` (con soft-delete + `restore` y `?with_trashed=1`), `orders` (listado filtrable + `verify`/`reject` manual de pago), `tickets` (listado, `void`, `reissue`, `export` CSV en streaming), `scans`, `dashboard/metrics` (vía `MetricsService`), `api-clients` (CRUD + `rotate-secret`, devuelve secret una sola vez), `users` admin/gate. Todo bajo `auth:admin` + `role:admin`. `ExportService` usa cursor lazy (CSV nativo; XLSX queda como futuro con maatwebsite/excel).
- **Mejoras post-Fase 7 (endurecimiento):**
  - **Ver/descargar desprendible** en admin: `GET /admin/orders/{id}/receipts` y `.../receipts/{receiptId}/download` (seguro, valida pertenencia).
  - **Validación server-side del monto + integridad**: `amount == price*quantity` y el tipo de ticket debe pertenecer al evento/tour (rechaza 422); el monto guardado es el autoritativo.
  - **Catálogo para integradores** (scope `tickets:read`): `GET /catalog/tours|events|ticket-types`.
  - **Expiración automática**: comando `tickets:expire` (estado `expired`) + schedule diario.
  - **Auditoría admin**: middleware `audit.admin` registra toda escritura en `audit_logs`; `GET /admin/audit-logs`.
  - **Asiento/sección**: columnas `section`/`seat` en `tickets`; la orden acepta `seats[]` y se asignan al emitir.
- **59 tests verdes** + smoke tests en vivo.

### Pendiente / dónde seguir
1. **Fase 8 (siguiente):** `scanner/` (Vue 3 + Vite o front mínimo) — login gate, cámara, decodifica QR, `POST /tickets/validate`, feedback verde/rojo/amarillo.
2. **Fase 9:** QA, concurrencia de aforo, rotación de clave, README de despliegue. Considerar `maatwebsite/excel` para XLSX.
3. **Extensiones futuras** (sección 11 del spec): sync bidireccional WP, pasarela integrada, validación offline, multi-tenant, antifraude.

### Endpoints admin (Fase 7, bajo `auth:admin` + `role:admin`, prefijo `/api/v1/admin`)
- `tours`, `events`, `ticket-types`: `GET` (`?with_trashed=1`), `POST`, `GET/PUT/DELETE {id}`, `POST {id}/restore`.
- `orders`: `GET` (filtros), `GET {id}`, `POST {id}/verify`, `POST {id}/reject`.
- `tickets`: `GET`, `GET export` (CSV), `POST {id}/void`, `POST {id}/reissue`.
- `scans` (`?event_id=`), `dashboard/metrics` (`?event_id=`).
- `api-clients`: CRUD + restore + `POST {id}/rotate-secret`.
- `users`: CRUD + restore.

### Endpoints disponibles (Fases 5-6)
- Cliente/tienda (`auth.api_client` + scope): `POST /client/token`, `POST /orders`, `POST /orders/{id}/receipt`, `GET /orders/{ref}`, `POST /webhooks/payment` (+`verify.webhook`), `GET /tickets/{code}/verify`, `GET /tickets/{code}/image`.
- Admin/gate (`auth:admin`): `POST /admin/login`, `GET /admin/me`, `POST /admin/logout`, `POST /admin/refresh`, `POST /tickets/validate` (`role:gate,admin`).

---

## 6. Convenciones

- **Nombres:** inglés en código (tablas, modelos, rutas, capas); español en contenido/comentarios.
- **Capas:** ningún `Model::query()` fuera de `app/Repositories/Eloquent/`. Controllers delgados → Services → Repositories.
- **Excepciones de dominio:** extienden `App\Exceptions\DomainException` (con `status()`); el handler en `bootstrap/app.php` las traduce a JSON.
- **QR:** el contenido es un token firmado `v{version}.{base64url(code)}.{base64url(hmac)}`. Rotar clave = subir `QR_KEY_VERSION` y añadir el secreto nuevo a `config/qr.php`.
- **Tests:** Feature/Unit con PHPUnit + `RefreshDatabase` sobre `qr_ticketing_test`. Cada Service/endpoint nuevo lleva test.
- **Commits:** Conventional Commits (`feat(scope): ...`), atómicos por tarea.

---

## 7. Seguridad (recordatorios)

- `QR_SECRET` y `JWT_SECRET` viven en `.env` (no commitear). El `.env` está en `.gitignore`.
- `QR_SECRET` sembrado en dev es un placeholder: **generar uno real antes de producción**.
- Webhooks: verificar firma HMAC + timestamp (anti-replay) en el middleware `verify.webhook` (Fase 5).
- Aforo y validación usan `SELECT ... FOR UPDATE` dentro de transacción: no romper esa atomicidad.
