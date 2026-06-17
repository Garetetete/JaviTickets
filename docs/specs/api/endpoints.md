# Spec — Contrato de la API (`docs/specs/api/endpoints.md`)

Prefijo `/api/v1`. Respuestas JSON. Errores estándar: `422` validación, `401` sin/invalid token, `403` rol/scope, `404` no encontrado, `409` conflicto de estado/aforo, `429` rate limit.

Audiencias de auth:
- **`auth:api_client`** — JWT client-credentials (la tienda). Requiere scope.
- **`auth:admin`** — JWT usuario, rol `admin`.
- **`auth:gate`** — JWT usuario, rol `gate` (o `admin`).

---

## A. Salud
### `GET /up` → `200 { "status": "ok" }`

---

## B. Consumido por la tienda / clientes máquina  (`auth:api_client`)

### `POST /orders`  — scope `orders:write`
Registra una compra. **Idempotente** por `external_reference`.
**Request**
```json
{
  "external_reference": "WP-10231",
  "event_id": 1,
  "ticket_type_id": 3,
  "quantity": 1,
  "amount": 1000.00,
  "currency": "USD",
  "payment_method": "transfer",
  "customer": {
    "first_name": "Ana", "second_name": null,
    "last_name": "Pérez", "second_last_name": "Gómez",
    "document_type": "dni", "document_number": "12345678",
    "email": "ana@example.com", "phone": "+51999...",
    "address": "Av...", "city_residence": "Lima", "country_residence": "Perú",
    "metadata": {}
  }
}
```
**Response 201**
```json
{ "order_id": 55, "payment_status": "pending_payment", "external_reference": "WP-10231" }
```
Si `external_reference` ya existe → `200` con la orden existente (idempotente, no duplica).
**Errores:** `422` (datos/ tipo/evento inválidos), `409` (tipo no pertenece al evento).

### `POST /orders/{id}/receipt`  — scope `orders:write`  *(flujo manual)*
`multipart/form-data`: `file` (jpg/png/pdf, máx N MB), `uploaded_by`.
**Response 200** `{ "order_id": 55, "payment_status": "pending_verification" }`.

### `POST /webhooks/payment`  — scope `orders:write` + middleware `verify.webhook`  *(flujo automático)*
Header `X-Signature: <hmac>`, `X-Timestamp`.
**Request**
```json
{ "external_event_id": "evt_abc", "external_reference": "WP-10231", "status": "paid", "amount": 1000.00 }
```
**Response 200** (idempotente; si ya procesado, devuelve el mismo resultado)
```json
{
  "order_id": 55, "payment_status": "verified",
  "tickets": [
    { "code": "01J...", "qr_token": "MDFK....abc", "qr_image_url": "/api/v1/tickets/01J.../image", "ticket_type": "diamante", "event": "Lima" }
  ]
}
```
**Errores:** `401` firma inválida, `409` estado de orden inválido.

### `GET /orders/{external_reference}`  — scope `tickets:read`  *(reconciliación)*
**Response 200**
```json
{
  "order_id": 55, "external_reference": "WP-10231", "payment_status": "verified",
  "tickets": [ { "code": "01J...", "status": "active", "qr_token": "MDFK....abc" } ]
}
```
**Errores:** `404` si no existe.

### `GET /tickets/{code}/verify`  — scope `tickets:read`
Verifica validez **sin** marcar usado.
**Response 200** `{ "code":"01J...", "status":"active", "valid": true, "event":"Lima", "ticket_type":"diamante" }`.
`valid=false` con motivo si `used|void|not_paid|expired`. `404` si no existe.

### `GET /tickets/{code}/image`  — scope `tickets:read`
Devuelve la imagen del QR (PNG/SVG).

---

## C. Validación en puerta  (`auth:gate`)

### `POST /tickets/validate`
**Request** `{ "qr_token": "MDFK....abc", "device": "gate-01" }` (el `gate_user_id` y `expected_event_id` salen del token/usuario).
**Response 200**
```json
{ "result": "valid", "ticket": { "holder_name": "Ana Pérez", "ticket_type": "Diamante", "event": "Lima", "used_at": "2026-..." } }
```
`result` ∈ `valid | already_used | invalid | not_paid | void | not_found | wrong_event`.
Siempre `200` con el `result` (no se usan códigos de error HTTP para resultados de negocio de validación; `401/403` solo por auth). Registra un `scan_log` por intento.

---

## D. Panel admin  (`auth:admin`)

### Auth
- `POST /admin/login` `{ email, password }` → `{ access, refresh, user }`.
- `POST /admin/refresh`, `POST /admin/logout`, `GET /admin/me`.

### CRUD configuración (todos con paginado + soft-delete/restore)
- `tours`: `GET/POST/PUT/DELETE /admin/tours` (+ `POST /admin/tours/{id}/restore`).
- `events`: `GET/POST/PUT/DELETE /admin/events` (filtro `tour_id`) (+ restore).
- `ticket_types`: `GET/POST/PUT/DELETE /admin/ticket-types` (filtro `tour_id`/`event_id`) (+ restore). Campos: `name`, `price`, `currency`, `quota`, `order`, `is_active`.

### Órdenes y verificación manual
- `GET /admin/orders` — filtros: `payment_status`, `event_id`, `ticket_type_id`, `customer`, `date_from`, `date_to`; paginado.
- `GET /admin/orders/{id}` — incluye `payment_receipts` (links a desprendibles).
- `POST /admin/orders/{id}/verify` → emite tickets; `200 { tickets: [...] }`. Errores `409` si estado inválido o aforo excedido.
- `POST /admin/orders/{id}/reject` `{ reason }`.

### Tickets
- `GET /admin/tickets` — filtros: `event_id`, `ticket_type_id`, `status`, `customer`, `date_from`, `date_to`.
- `POST /admin/tickets/{id}/void` `{ reason }`; `POST /admin/tickets/{id}/reissue`.
- `GET /admin/tickets/export?format=xlsx|csv` (filtros aplicables).

### Escaneos y métricas
- `GET /admin/scans` — filtros `event_id`, `result`, `date_from/to`.
- `GET /admin/dashboard/metrics` — aforo vendido vs usado por evento/tipo, ingresos, no-show, timeline de escaneos.

### Clientes API y usuarios
- `GET/POST/PUT/DELETE /admin/api-clients` (+ `POST /admin/api-clients/{id}/rotate-secret` → devuelve secret en claro una sola vez; gestiona `scopes`, `webhook_secret`).
- `GET/POST/PUT/DELETE /admin/users` (admins/operadores `gate`, asignación de `event_id`).

---

**Criterio por endpoint:** cada uno mapeado a `Controller (delgado) → Service → Repository`, con Form Request de validación y Resource de salida, y al menos un test Pest (caso feliz + un error). Documentar request/response y errores aquí antes de implementar.
