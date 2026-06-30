# SDD Playbook — QR Ticketing API (autoridad de tickets/QR para tours)

Especificación funcional, técnica y plan de tareas para construir, con **Spec-Driven Development (SDD)**, una **API REST autónoma** que es la **fuente de verdad de los tickets/QR** de un tour: genera el QR, lo asocia a un comprador + metadatos + pago + evento, lo verifica y lo valida en puerta. Otros sistemas (la tienda WordPress de Javi, el escáner, el tour-platform) **consumen** esta API; nunca generan ni validan QR por su cuenta.

> Proyecto hermano de `SDD-TourPlatform-Spec.md`. Comparte metodología SDD, arquitectura por capas y Laragon, pero es **un servicio independiente** con su propia BD, su propio dominio API y su propia autenticación (JWT).

---

## 1. Reglas generales para el agente de IA

1. **No avanzar de fase sin completar la spec previa.** Cada fase tiene "Entradas", "Salidas" y "Criterios de aceptación".
2. **Autoridad única del QR.** La generación, firma, verificación y validación (marcado de usado) de un ticket ocurre **solo** en esta API. Ningún consumidor (tienda, escáner) puede emitir ni invalidar tickets fuera de aquí. Esto es el pilar de seguridad.
3. **QR firmado, no adivinable.** El contenido del QR es un token firmado por el servidor (HMAC-SHA256 sobre el `code` + versión de clave). Copiar la imagen no permite falsificar; el primer escaneo marca el ticket como usado (anti-doble-entrada).
4. **Una fuente de verdad de configuración:** todo lo variable por tour/evento (tipos de ticket, precios, aforo, datos del artista/dueño) vive en BD, administrable desde `/admin`, **nunca hardcodeado**.
5. **Backend = API REST (Laravel 10, stateless, JWT) con arquitectura por capas** (sección 3). Dos audiencias de auth: **clientes máquina** (la tienda) y **usuarios admin/operadores de puerta**, ambos con JWT y *scopes*/roles distintos.
6. **Doble flujo de pago desde el inicio:** (a) **manual** — subir desprendible + verificación humana en admin; (b) **automático** — webhook firmado desde la tienda/pasarela. El ticket solo se emite cuando el pago queda `verified`.
7. **Integración WordPress = solo exponer API.** Definimos el contrato y los endpoints que la tienda consume (registrar compra, subir desprendible, webhook de pago, reconciliación). No construimos plugin ni sync bidireccional en el MVP; la tienda guarda en su BD el `code`/`qr_token` que devolvemos para **reconciliación/confrontación**.
8. **Convención de nombres:** inglés en código (tablas, modelos, rutas API, capas), español en contenido/textos.
9. **Versionado de specs:** cada entidad nueva (modelo, servicio, repositorio, endpoint) se documenta primero en `docs/specs/` (propósito, campos/props, contrato request/response, validaciones, errores) antes de implementarse.
10. **Soft deletes obligatorios** en toda tabla de negocio (`tours`, `events`, `ticket_types`, `customers`, `orders`, `tickets`, `api_clients`, `admin_users`). Los logs de eventos (`scan_logs`, `webhook_events`) **no** usan soft delete (son append-only).
11. **Seeders:** todo dato configurable cargable vía `Seeder` (tour de ejemplo, evento, 3 tipos de ticket Normal/Premium/Diamante, un cliente API y un admin).
12. **Seguridad mínima:** rate limiting en endpoints públicos/consumidos, validación server-side estricta, idempotencia en creación de órdenes y webhooks, firma verificada en webhooks, aforo atómico (transacción + lock) en emisión.
13. **Entorno dual (Docker + Laragon):** el proyecto es un Laravel 10 estándar (PHP 8.1.34, **PostgreSQL 16**) que debe poder levantarse de dos formas equivalentes:
    - **Docker** (para desarrollo local en esta máquina): `docker compose up` levanta PHP-FPM/CLI + Postgres + servidor web, sin instalar nada en el host. Es también lo que permite hacer `composer`/`artisan` aquí (no hay PHP nativo en esta máquina).
    - **Laragon** (en la máquina de despliegue/desarrollo de Saul): el mismo código corre bajo Laragon (Apache/Nginx + PHP + PostgreSQL) leyendo el `.env`; **no debe haber ninguna dependencia de Docker en el código** (Docker es solo envoltorio de entorno, nunca requisito de la app).
    Toda config de entorno vive en `.env` (no hardcodear hosts/credenciales). Escáner front (Vue 3 + Vite, o front mínimo) que consume el endpoint de validación.
14. **Commits atómicos** por tarea (Conventional Commits). **Testing:** cada endpoint con al menos un test de feature (Pest); cada Service/Repository no trivial con test unitario (repos mockeados).

---

## 2. Arquitectura general

```
qr-ticketing-api/
├── backend/                          # Laravel 10 (API, arquitectura en capas)
│   ├── app/
│   │   ├── Models/                   # Eloquent puros (persistencia + relaciones + casts)
│   │   ├── Repositories/
│   │   │   ├── Contracts/            # Interfaces (TicketRepositoryInterface, etc.)
│   │   │   └── Eloquent/             # Implementaciones concretas
│   │   ├── Services/                 # Lógica de negocio (QrService, TicketIssuanceService, ...)
│   │   ├── Http/
│   │   │   ├── Controllers/Api/      # Consumido por la tienda/clientes máquina (JWT cliente)
│   │   │   ├── Controllers/Admin/    # Panel admin/operadores (JWT usuario)
│   │   │   ├── Requests/             # Form Requests (validación)
│   │   │   ├── Resources/            # API Resources (salida)
│   │   │   └── Middleware/           # auth:api_client, auth:admin, scopes, verify.webhook
│   │   ├── DTOs/                     # OrderData, IssueTicketData, ValidateTicketData, ...
│   │   ├── Exceptions/               # CapacityExceededException, TicketAlreadyUsedException, ...
│   │   ├── Support/Qr/               # QrSigner (HMAC), QrEncoder (imagen PNG/SVG)
│   │   └── Providers/                # RepositoryServiceProvider (bindings)
│   ├── database/migrations
│   ├── database/seeders
│   ├── routes/api.php
│   └── tests/Feature, tests/Unit
├── scanner/                          # Front mínimo de lectura de QR (Vue 3 + Vite o equivalente)
│   └── src/                          # cámara → decodifica → POST /tickets/validate → muestra resultado
├── docker/                           # SOLO envoltorio de entorno local (no requisito de la app)
│   ├── docker-compose.yml            # php-fpm/cli + postgres:16 + nginx
│   ├── Dockerfile                    # imagen PHP 8.1.34 con extensiones (pdo_pgsql, gd/imagick para QR)
│   └── nginx.conf
└── docs/specs/                       # Specs SDD por entidad/feature/capa
    ├── db/                           # schema.md (todas las tablas)
    ├── architecture/                 # repositories.md, services.md
    └── api/                          # endpoints.md (contratos request/response)
```

- API en `api.qrtickets.test`.
- Escáner en `scanner.qrtickets.test` (o servido aparte).
- **Entorno dual:** el `backend/` es Laravel estándar; corre bajo **Laragon** (PostgreSQL) en la máquina de Saul y bajo **Docker** (`docker/docker-compose.yml`, Postgres 16) en local. La app solo conoce el `.env`; nada de su código depende de Docker.
- **Capas backend — flujo de dependencia:** `Controller → Service → Repository (interface) → Repository (Eloquent) → Model`. Controllers nunca llaman a Eloquent. Services solo conocen interfaces de repositorio (testeables con mocks).

---

## 3. Arquitectura por capas (resumen; detalle en `docs/specs/architecture/`)

- **Model:** Eloquent puro (`$fillable`, `$casts`, relaciones, `SoftDeletes`, scopes simples). Sin lógica de negocio.
- **Repository:** una interface por agregado en `Contracts/`, implementación en `Eloquent/`. Métodos `find`, `findByCode`, `paginateWithFilters`, `create`, `update`, `delete` (soft), `restore`, + métodos de aforo (`countIssuedByEvent`, `lockEventForIssue`).
- **Service:** lógica de negocio y orquestación. Lanza **excepciones de dominio** que los Controllers traducen a HTTP. Servicios clave:
  - `QrService`: firma (`sign(code)`), verificación (`verify(token)`), codificación a imagen.
  - `TicketIssuanceService`: emite ticket(s) tras pago verificado, **validando aforo de forma atómica**.
  - `PaymentService`: registra órdenes, recibe desprendibles, verifica pagos (manual y webhook), idempotencia.
  - `ValidationService`: valida un QR en puerta (decodifica → verifica firma → estado → marca `used` atómicamente → registra `scan_log`).
  - `MetricsService`: aforo vendido/usado por evento/tipo, ingresos, tasa de no-show.
  - `AuthService`: emisión/validación de JWT para admin y clientes máquina.
- **Controller:** delgado; Request validado → Service → Resource/JSON.
- **DTOs:** `OrderData`, `ReceiptData`, `WebhookPaymentData`, `IssueTicketData`, `ValidateTicketData`, `TicketFilters`.

**Criterio de aceptación de capa:** ningún Controller contiene queries Eloquent; todo acceso a datos pasa por Repository; toda regla de negocio (aforo, transición de estados de pago/ticket, firma) vive en un Service.

---

## 4. Modelo de datos (spec primero) — detalle en `docs/specs/db/schema.md`

Tablas de negocio con `id`, `created_at`, `updated_at`, `deleted_at` (SoftDeletes). Logs (`scan_logs`, `webhook_events`) son append-only (sin soft delete).

Resumen de tablas:
1. `tours` — tour/artista/dueño (a quién pertenece el ticket).
2. `events` — evento concreto del tour (ciudad/venue/fecha) + **aforo total** (`capacity`).
3. `ticket_types` — Normal/Premium/Diamante: precio, moneda, **cupo opcional por tipo**, configurable por tour/evento.
4. `customers` — comprador y metadatos (nombres, apellidos, DNI, dirección, correo, teléfono…).
5. `orders` — compra/intención de pago: estado de pago, método, monto, referencia externa (WP), idempotencia.
6. `payment_receipts` — desprendibles subidos (historial, para flujo manual).
7. `tickets` — **el QR**: `code` único, `qr_token` firmado, tipo, evento, comprador, orden, estado (`issued`/`active`/`used`/`void`/`expired`).
8. `scan_logs` — log append-only de cada intento de validación en puerta.
9. `api_clients` — clientes máquina que consumen la API (la tienda), credenciales + scopes.
10. `admin_users` — admin/operadores de puerta (`role`: `admin`/`gate`).
11. `webhook_events` — log + idempotencia de notificaciones de pago entrantes.

**Relaciones clave:** `tour 1—N events`, `tour/event 1—N ticket_types`, `customer 1—N orders`, `order 1—N tickets`, `ticket_type 1—N tickets`, `event 1—N tickets`, `ticket 1—N scan_logs`.

---

## 5. Contrato de la API (detalle en `docs/specs/api/endpoints.md`)

Prefijo `/api/v1`.

### 5.1 Consumido por la tienda / clientes máquina (`auth:api_client`, JWT cliente + scopes)
- `POST /orders` — la tienda registra una compra. Body: datos del comprador + `ticket_type_id` + `external_reference` (id de orden WP) + cantidad. **Idempotente** por `external_reference`. Crea `customer` (o lo reusa) y `order` en `pending_payment`. Devuelve `order_id` y estado.
- `POST /orders/{id}/receipt` — (flujo manual) sube desprendible (multipart). Pasa la orden a `pending_verification`.
- `POST /webhooks/payment` — (flujo automático) notificación firmada de pago confirmado. Verifica firma + idempotencia → marca `verified` → **emite tickets** → devuelve `tickets[]` con `code` + `qr_token` + URL de imagen QR.
- `GET /orders/{external_reference}` — **reconciliación**: la tienda confronta estado/tickets contra su propia BD.
- `GET /tickets/{code}/verify` — verificación de validez (existe, pagado, no usado, no anulado) **sin** marcar usado. Para confirmaciones/consultas.

### 5.2 Validación en puerta (`auth:admin` rol `gate` o `admin`, JWT usuario)
- `POST /tickets/validate` — body `{ qr_token }`. Decodifica → verifica firma → valida estado → **marca `used` atómicamente** → registra `scan_log`. Devuelve `valid|already_used|invalid|not_paid` + datos mínimos del asistente. Idempotente ante doble lectura inmediata (ventana configurable).

### 5.3 Panel admin (`auth:admin` rol `admin`, JWT usuario)
- `POST /admin/login`, `POST /admin/logout`, `GET /admin/me`, `POST /admin/refresh`.
- CRUD `tours`, `events`, `ticket_types` (precios, aforo, cupos) — todos con restore.
- `GET /admin/orders` con filtros (estado de pago, evento, tipo, fechas, cliente) + paginado.
- `POST /admin/orders/{id}/verify` / `POST /admin/orders/{id}/reject` — **verificación manual de pago** (revisa desprendible) → emite o rechaza.
- `GET /admin/tickets` con filtros (evento, tipo, estado, comprador, fechas) + soft-delete/restore; `POST /admin/tickets/{id}/void` (anular), `POST /admin/tickets/{id}/reissue` (reemitir).
- `GET /admin/scans` — log de validaciones por evento.
- `GET /admin/dashboard/metrics` — aforo vendido vs usado por evento/tipo, ingresos, no-show.
- CRUD `admin/api-clients` (alta de clientes máquina + emisión/rotación de credenciales y scopes).
- CRUD `admin/users` (admins/operadores de puerta).

**Criterio por endpoint:** documentado en `docs/specs/api/endpoints.md` (request/response, errores 422/401/403/404/409/429) y mapeado a Controller→Service→Repository antes de codificar.

---

## 6. Seguridad — modelo del QR y del aforo

- **Firma del QR:** `qr_token = base64url(code) . "." . base64url(HMAC_SHA256(code, APP_QR_SECRET, key_version))`. `QrService::verify()` recomputa el HMAC y rechaza cualquier token manipulado. `key_version` permite **rotar el secreto** sin invalidar tickets viejos (se guardan ambos secretos durante la rotación).
- **Anti-doble-entrada:** `tickets.status` pasa a `used` dentro de una transacción con `SELECT ... FOR UPDATE`; un segundo escaneo del mismo `code` devuelve `already_used` con el `scan_log` original. Ventana anti-rebote configurable (ej. 5s) para no penalizar doble lectura accidental.
- **Aforo atómico:** la emisión bloquea el `event` (lock) y compara `countIssued(event) + n` contra `capacity` (y `quota` por `ticket_type`); si excede → `CapacityExceededException` (409). Nunca se emite por encima del aforo.
- **Idempotencia:** `orders` por `external_reference` único; `webhook_events` por `event_id` externo único. Reintentos de la tienda no duplican órdenes ni tickets.
- **Webhooks firmados:** header `X-Signature` (HMAC del body con secreto del `api_client`); middleware `verify.webhook` rechaza firmas inválidas o timestamps fuera de ventana (anti-replay).
- **JWT:** access token corto + refresh; scopes para clientes máquina (`orders:write`, `tickets:read`); roles para usuarios (`admin`, `gate`). El `gate` solo puede `tickets/validate` y leer su evento asignado.
- **Rate limiting:** por `api_client` y por IP en endpoints públicos/validación.

---

## 7. Flujos end-to-end

### 7.1 Compra con verificación manual (sin pasarela)
1. Javi publica el producto/tipo de ticket en la tienda WordPress.
2. Cliente compra en la tienda → la tienda llama `POST /orders` con comprador + tipo + `external_reference`. Orden queda `pending_payment`.
3. El comprador paga por transferencia y **sube el desprendible** → `POST /orders/{id}/receipt` → `pending_verification`.
4. Un admin revisa el desprendible en `/admin/orders` → `POST /admin/orders/{id}/verify` → `PaymentService` marca `verified` y `TicketIssuanceService` **emite el/los ticket(s)** validando aforo.
5. La API devuelve `code` + `qr_token` + imagen QR; la tienda los guarda en su BD (reconciliación) y se los entrega al comprador.

### 7.2 Compra automática (webhook)
1-2. Igual hasta crear la orden (o la tienda crea orden + confirma en un solo webhook).
3. La pasarela/tienda confirma el pago → `POST /webhooks/payment` (firmado) → `PaymentService` verifica firma + idempotencia → `verified` → emite tickets → responde con tickets.

### 7.3 Validación en puerta
1. Operador de puerta (rol `gate`) abre el **escáner**, escanea el QR.
2. El front decodifica el `qr_token` y llama `POST /tickets/validate`.
3. `ValidationService` verifica firma → estado pagado/no usado → marca `used` atómicamente → registra `scan_log` → responde `valid` con nombre/tipo. Repetir el mismo QR → `already_used`.

### 7.4 Reconciliación (confrontación de BDs)
- La tienda periódicamente o ante duda llama `GET /orders/{external_reference}` y compara su `code`/`qr_token`/estado contra los de esta API. Discrepancias se resuelven con esta API como autoridad.

---

## 8. Plan de tareas por fases (SDD)

### Fase 0 — Setup (entorno dual: Docker local + Laragon)
- Crear Laravel 10 en `backend/`, `.env` (`DB_CONNECTION=pgsql`, DB `qr_ticketing`, dominio `api.qrtickets.test`), instalar paquete JWT (`tymon/jwt-auth` o `php-open-source-saver/jwt-auth`) y librería QR (`simplesoftwareio/simple-qrcode` o `bacon/bacon-qr-code`).
- **Docker local:** `docker/docker-compose.yml` con `php` (8.1.34, extensiones `pdo_pgsql` + `gd`/`imagick`), `postgres:16`, `nginx`. Permite ejecutar `composer`/`php artisan` aquí (esta máquina no tiene PHP nativo). `.env.example` documenta credenciales para Docker y un perfil alterno para Laragon.
- **Compatibilidad Laragon:** el mismo `backend/` debe correr bajo Laragon apuntando su PostgreSQL vía `.env`; ninguna ruta/clase del código asume Docker.
- Esqueleto de capas: `Repositories/{Contracts,Eloquent}`, `Services`, `DTOs`, `Exceptions`, `Support/Qr`, `Controllers/{Api,Admin}`, `Middleware`.
- `RepositoryServiceProvider` (bindings) registrado en `config/app.php` (`'providers'`, esqueleto clásico L10). CORS para escáner/consumidores. `APP_QR_SECRET` + `QR_KEY_VERSION` en `.env`.
- **Salida:** API corre tanto con `docker compose up` como bajo Laragon; `GET /api/v1/up` → 200; estructura de capas presente.

### Fase 1 — Specs de datos y contratos
- Completar `docs/specs/db/schema.md`, `docs/specs/architecture/repositories.md`, `docs/specs/architecture/services.md`, `docs/specs/api/endpoints.md`.
- **Criterio:** specs aprobadas antes de migraciones/clases.

### Fase 2 — Modelos, migraciones, seeders (SoftDeletes)
- Migraciones de las 11 tablas (sección 4); modelos Eloquent con relaciones/casts.
- Seeders: tour de ejemplo + 1 evento + 3 `ticket_types` (Normal 300 / Premium 500 / Diamante 1000) + 1 `api_client` + 1 `admin_user`.
- **Criterio:** `php artisan migrate --seed` sin error; `deleted_at` en tablas de negocio.

### Fase 3 — Capa Repository
- Interfaces + implementaciones Eloquent (incl. métodos de aforo y `findByCode`). Bindings. Tests unitarios (crear, buscar por code, soft-delete/restore, conteo de aforo).
- **Criterio:** ningún `Model::query()` fuera de `Eloquent/`.

### Fase 4 — Capa Service + DTOs + Excepciones + Support/Qr
- `QrService` (firma/verificación/imagen) con tests unitarios deterministas (firma estable, rechazo de token manipulado, rotación de clave).
- `TicketIssuanceService` (aforo atómico), `PaymentService` (manual + webhook + idempotencia), `ValidationService` (marcado usado atómico), `MetricsService`, `AuthService` (JWT).
- DTOs y excepciones de dominio. Tests unitarios con repos mockeados.
- **Criterio:** aforo, transiciones de estado y firma 100% en Services, verificables sin BD real (salvo tests de concurrencia de aforo, que usan BD).

### Fase 5 — Controllers API (consumido por tienda) + JWT cliente
- `Api/OrderController`, `Api/WebhookController`, `Api/TicketController` (verify). Form Requests, Resources, rate limiting, middleware `verify.webhook`, idempotencia.
- Tests Pest: `POST /orders` (idempotente) → receipt/webhook → emisión → `GET /orders/{ref}` reconcilia; verify de un code.
- **Criterio:** flujo de compra (manual y webhook) emite tickets correctos; colección Thunder/Postman documentada.

### Fase 6 — Validación en puerta + auth admin/operador
- `AuthController` (JWT admin/gate), middleware roles. `Api/ValidationController` (`POST /tickets/validate`) con marcado atómico y `scan_logs`.
- Tests Pest: escaneo válido → `used`; segundo escaneo → `already_used`; token falsificado → `invalid`; sin pago → `not_paid`; concurrencia (dos escaneos simultáneos → una sola entrada).
- **Criterio:** anti-doble-entrada y firma verificados bajo concurrencia.

### Fase 7 — Controllers Admin (CRUD + verificación manual + métricas)
- `Admin/*Controller` para tours/events/ticket_types/orders/tickets/scans/metrics/api-clients/users. Verificación/rechazo manual de pago. Soft-delete/restore. Export opcional.
- **Criterio:** todos los endpoints privados de sección 5.3 implementados y testeados (401 sin token, 403 por rol, 200 con admin).

### Fase 8 — Escáner front
- Front mínimo (Vue 3 + Vite): login `gate`, acceso a cámara, decodificación de QR, `POST /tickets/validate`, feedback claro (verde/rojo/amarillo), modo offline-tolerante opcional (cola de reintentos).
- **Criterio:** escanear un QR real emitido por la API muestra `valid` y bloquea el segundo intento.

### Fase 9 — QA, seguridad y entrega local
- Pruebas de carga ligera de aforo (concurrencia), revisión de rate limiting, rotación de `QR_KEY_VERSION`, `docs/README.md` (cómo levantar en Laragon, dominios, `migrate --seed`).
- **Criterio:** checklist (sección 9) en verde; reproducible desde cero.

---

## 9. Checklist de aceptación global

- [ ] La emisión, firma, verificación y validación de tickets ocurre solo en esta API (autoridad única).
- [ ] El QR es un token firmado (HMAC); un token manipulado se rechaza; soporta rotación de clave.
- [ ] Anti-doble-entrada: el primer escaneo marca `used`; el segundo devuelve `already_used`, verificado bajo concurrencia.
- [ ] Aforo atómico por evento (+ cupo por tipo); nunca se emite por encima de `capacity`.
- [ ] Doble flujo de pago: manual (desprendible + verificación admin) y automático (webhook firmado + idempotente).
- [ ] La tienda consume la API para registrar compra, subir desprendible, recibir webhook y **reconciliar** por `external_reference`; guarda `code`/`qr_token` en su BD.
- [ ] Tipos de ticket y precios configurables por tour/evento desde admin, sin tocar código.
- [ ] Tickets relacionados a comprador (con metadatos: nombres, DNI, dirección, correo…), tipo, evento, tour y referencia de pago.
- [ ] JWT con scopes (clientes máquina) y roles (`admin`/`gate`); rate limiting activo.
- [ ] Soft deletes + restore en tablas de negocio; logs (`scan_logs`, `webhook_events`) append-only.
- [ ] Tests backend (Pest) y del escáner cubren compra, emisión, validación, aforo y firma.
- [ ] Proyecto levantable con Docker (local) **y** bajo Laragon (despliegue), ambos sobre PostgreSQL, reproducible con README; el código no depende de Docker.

---

## 10. Notas de decisión (cerradas)

1. **Alcance:** API REST autónoma; otros sistemas la consumen. Autoridad única del QR.
2. **Pago:** ambos flujos desde el inicio (manual con desprendible + automático por webhook).
3. **WordPress:** solo exponemos API + contrato de consumo + reconciliación; sin plugin/sync bidireccional en el MVP.
4. **Auth:** JWT (clientes máquina con scopes; usuarios admin/gate con roles).
5. **Base de datos:** PostgreSQL 16.
6. **Entorno:** dual — Docker para desarrollo local (esta máquina no tiene PHP nativo) y Laragon para la máquina de despliegue de Saul; el código no depende de Docker.

## 11. Extensiones futuras (fuera del MVP)

- **11.1 Sync bidireccional real con WordPress** (plugin/webhooks salientes, reconciliación automática).
- **11.2 Pasarela de pago integrada** (link de pago real + confirmación automática), reemplazando/complementando el flujo manual.
- **11.3 Validación offline en puerta** con sincronización diferida y firma local verificable.
- **11.4 Multi-tour / multi-tenant** con aislamiento por dueño de tour y métricas por artista.
- **11.5 Antifraude avanzado:** detección de patrones de escaneo, geocerca de puerta, límite de reintentos por dispositivo.
