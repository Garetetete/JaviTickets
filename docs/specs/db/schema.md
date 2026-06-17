# Spec — Modelo de datos (`docs/specs/db/schema.md`)

Convención: inglés en nombres de tablas/columnas; contenido en español. Tablas de negocio con `id` (bigint PK), `created_at`, `updated_at`, `deleted_at` (SoftDeletes). Logs (`scan_logs`, `webhook_events`) son **append-only** (sin soft delete).

**Motor: PostgreSQL 16** (`DB_CONNECTION=pgsql`). Notas para Postgres:
- Donde la tabla dice `enum`, en migración se implementa como `string` + **CHECK constraint** (`$table->enum()` de Laravel en Postgres genera ese check) o como tipo enum nativo; usar el valor lógico en español/inglés según se indique. No depender del tipo `ENUM` de MySQL.
- `json` → tipo `jsonb` (mejor indexable en Postgres).
- Aforo y anti-doble-entrada usan `SELECT ... FOR UPDATE` (soportado por Postgres) dentro de transacción.
- `id` bigint → `bigIncrements` (Postgres `bigserial`). `decimal(10,2)` → `numeric(10,2)`.

---

## 1. `tours`
A qué tour/artista/dueño pertenece el ticket.

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| slug | string unique | `chica-mala-tour` |
| name | string | "CHICA MALA TOUR" |
| artist_name | string | "Dennis Fernando" |
| owner_name | string nullable | dueño/promotor del tour (para que Javi relacione la info) |
| owner_email | string nullable | contacto del dueño |
| is_active | boolean default true | |
| timestamps + deleted_at | | SoftDeletes |

---

## 2. `events`
Evento concreto del tour (ciudad/venue/fecha) y **aforo total**.

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| tour_id | FK tours | |
| slug | string | unique compuesto `tour_id+slug` (`bogota`, `lima`…) |
| name | string | "Bogotá" |
| country | string nullable | |
| venue | string nullable | |
| event_date | datetime nullable | |
| capacity | integer | **aforo total** del evento (límite duro de emisión) |
| is_active | boolean default true | |
| timestamps + deleted_at | | SoftDeletes |

**Índices:** `tour_id`, `(tour_id, slug)` unique.

---

## 3. `ticket_types`
Tipos y precios configurables por tour/evento (Normal/Premium/Diamante…).

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| tour_id | FK tours | tipo definido a nivel tour |
| event_id | FK events nullable | si se quiere precio/cupo específico por evento; null = aplica a todos los del tour |
| slug | string | `normal`, `premium`, `diamante` |
| name | string | "Normal" |
| price | decimal(10,2) | 300.00 / 500.00 / 1000.00 |
| currency | string(3) default 'USD' | configurable |
| quota | integer nullable | cupo opcional por tipo (null = limitado solo por `event.capacity`) |
| order | integer default 0 | orden de despliegue |
| is_active | boolean default true | |
| timestamps + deleted_at | | SoftDeletes |

**Índices:** `tour_id`, `event_id`, `(tour_id, slug)` unique (con `event_id` en el índice si se permite override por evento).

---

## 4. `customers`
Comprador y metadatos (entre más, mejor).

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| first_name | string | primer nombre |
| second_name | string nullable | segundo nombre |
| last_name | string | apellido |
| second_last_name | string nullable | segundo apellido |
| document_type | string nullable | `dni`, `passport`, `ce`… |
| document_number | string | DNI/identificación |
| email | string | |
| phone | string nullable | con código de país |
| address | string nullable | dirección |
| city_residence | string nullable | |
| country_residence | string nullable | |
| birth_date | date nullable | |
| metadata | json nullable | metadatos extra arbitrarios (extensible) |
| timestamps + deleted_at | | SoftDeletes |

**Índices:** `email`, `(document_type, document_number)`.

---

## 5. `orders`
Compra / intención de pago. Idempotente por `external_reference`.

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| customer_id | FK customers | |
| external_reference | string unique nullable | id de orden de la tienda WP (idempotencia) |
| api_client_id | FK api_clients nullable | qué cliente máquina la creó |
| payment_status | enum | `pending_payment`, `pending_verification`, `verified`, `rejected` |
| payment_method | string nullable | `transfer`, `gateway`, `manual`… |
| amount | decimal(10,2) | total de la orden |
| currency | string(3) default 'USD' | |
| quantity | integer default 1 | nº de tickets a emitir |
| ticket_type_id | FK ticket_types | tipo comprado |
| event_id | FK events | evento de la compra |
| verified_by | FK admin_users nullable | quién verificó (flujo manual) |
| verified_at | datetime nullable | |
| rejected_reason | string nullable | |
| notes | string nullable | |
| timestamps + deleted_at | | SoftDeletes |

**Estados (transiciones):** `pending_payment` → `pending_verification` (sube desprendible) → `verified` (admin o webhook) → emite tickets. Cualquiera → `rejected`. `verified` y `rejected` son terminales.

**Índices:** `external_reference` unique, `payment_status`, `event_id`, `ticket_type_id`, `customer_id`.

---

## 6. `payment_receipts`
Desprendibles subidos (historial; flujo manual). Soft delete para auditoría.

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| order_id | FK orders | |
| file_path | string | ruta en Storage (no público; acceso vía admin) |
| original_name | string nullable | |
| mime_type | string nullable | |
| uploaded_by | string nullable | `store` / email cliente |
| timestamps + deleted_at | | SoftDeletes |

**Índices:** `order_id`.

---

## 7. `tickets`
**El QR.** Una fila por entrada emitida.

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| code | string(40) unique | identificador interno único (ULID/UUID); base del `qr_token` |
| qr_token | text | token firmado embebido en el QR (`code.signature`) |
| key_version | smallint default 1 | versión de la clave HMAC usada (rotación) |
| order_id | FK orders | compra de origen |
| ticket_type_id | FK ticket_types | |
| event_id | FK events | |
| customer_id | FK customers | titular |
| status | enum | `issued`, `active`, `used`, `void`, `expired` |
| used_at | datetime nullable | momento de la validación en puerta |
| validated_by | FK admin_users nullable | operador `gate` que validó |
| voided_reason | string nullable | |
| metadata | json nullable | snapshot de datos del titular al emitir |
| timestamps + deleted_at | | SoftDeletes |

**Estados:** `issued`/`active` (válido para entrar) → `used` (entró) ; `void` (anulado por admin) ; `expired` (post-evento, opcional por job). Solo `issued`/`active` pasan a `used`.

**Índices:** `code` unique, `qr_token` (prefijo/hash), `event_id`, `ticket_type_id`, `customer_id`, `order_id`, `status`.

---

## 8. `scan_logs`  *(append-only, sin soft delete)*
Log de cada intento de validación en puerta.

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| ticket_id | FK tickets nullable | null si el token no resolvió a un ticket |
| code | string nullable | code decodificado (aunque sea inválido) |
| event_id | FK events nullable | |
| result | enum | `valid`, `already_used`, `invalid_signature`, `not_paid`, `void`, `not_found`, `wrong_event` |
| scanned_by | FK admin_users nullable | operador `gate` |
| ip | string nullable | |
| device | string nullable | |
| created_at | timestamp | (solo created_at) |

**Índices:** `ticket_id`, `event_id`, `result`, `created_at`.

---

## 9. `api_clients`
Clientes máquina que consumen la API (la tienda).

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| name | string | "Tienda WordPress Javi" |
| client_id | string unique | identificador público |
| client_secret_hash | string | hash del secret (nunca en claro) |
| webhook_secret | string nullable | secreto para firmar/verificar webhooks (HMAC) |
| scopes | json | ej. `["orders:write","tickets:read"]` |
| is_active | boolean default true | |
| timestamps + deleted_at | | SoftDeletes |

**Índices:** `client_id` unique.

---

## 10. `admin_users`
Admins y operadores de puerta.

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| name | string | |
| email | string unique | |
| password | string | hash |
| role | enum | `admin`, `gate` |
| event_id | FK events nullable | evento asignado a un operador `gate` (opcional) |
| is_active | boolean default true | |
| timestamps + deleted_at | | SoftDeletes |

**Índices:** `email` unique, `role`.

---

## 11. `webhook_events`  *(append-only, idempotencia)*
Log de notificaciones de pago entrantes.

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| api_client_id | FK api_clients | quién envió |
| external_event_id | string unique | id del evento en origen (idempotencia: dedup) |
| order_external_reference | string nullable | a qué orden refiere |
| payload | json | cuerpo recibido (auditoría) |
| signature_valid | boolean | resultado de verificación de firma |
| processed | boolean default false | si derivó en verificación/emisión |
| created_at | timestamp | (solo created_at) |

**Índices:** `external_event_id` unique, `order_external_reference`.

---

## Diagrama de relaciones (resumen)

```
tours 1─N events
tours 1─N ticket_types        events 1─N ticket_types (override opcional)
customers 1─N orders
orders 1─N tickets            orders 1─N payment_receipts
ticket_types 1─N tickets      events 1─N tickets
tickets 1─N scan_logs
api_clients 1─N orders        api_clients 1─N webhook_events
admin_users 1─N (verified orders, validated tickets, scan_logs)
```
