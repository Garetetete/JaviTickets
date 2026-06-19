# Guía de integración

Para quien **consume** la API: la tienda (cliente máquina) y el escáner de puerta. La API es la autoridad del QR; los consumidores nunca generan ni validan tickets por su cuenta.

Base URL: `https://api.tudominio.com/api/v1` (dev: `http://localhost:8090/api/v1`).
Todas las respuestas son JSON. Auth por header `Authorization: Bearer <token>`.

---

## 0. Pre-requisitos

| Necesita | Cómo se obtiene |
|---|---|
| `client_id` + `client_secret` | te los entrega el admin (`POST /admin/api-clients`) |
| `webhook_secret` | idem (solo si usas pago automático) |
| `event_id` / `ticket_type_id` | con el **catálogo** (abajo); la tienda mapea su producto → estos IDs |

Errores estándar: `401` (sin/invalid token), `403` (scope/rol), `404`, `409` (estado/aforo), `422` (validación/monto), `429` (rate limit).

---

## A. Tienda (cliente máquina)

### 1. Token (client-credentials)
```http
POST /client/token
{ "client_id": "...", "client_secret": "..." }
→ { "access_token": "eyJ...", "token_type": "Bearer", "expires_in": 3600, "scopes": [...] }
```

### 2. Catálogo (descubrir IDs, precios, disponibilidad y asientos) — scope `tickets:read`
```http
GET /catalog/tours
GET /catalog/events?tour_id=1
GET /catalog/ticket-types?event_id=1
GET /catalog/events/{id}/availability   → { capacity, sold, available, sold_out, by_type:[{price, sold, available}] }
GET /catalog/events/{id}/seats          → [{ id, section, label, available }]  (eventos numerados)
```
Devuelven solo elementos activos. Usa `availability` para mostrar "quedan X / agotado" y `seats` para pintar el mapa de asientos libres.

### 3. Crear la orden — scope `orders:write`
```http
POST /orders
{
  "external_reference": "WP-10231",     // id de la orden en TU sistema (idempotente)
  "event_id": 1,
  "ticket_type_id": 3,
  "quantity": 1,
  "amount": 1000.00,                     // DEBE = price * quantity (se valida server-side)
  "currency": "USD",                     // opcional
  "payment_method": "transfer",          // opcional
  "customer": {
    "first_name": "Ana", "last_name": "Pérez",
    "document_type": "dni", "document_number": "12345678",
    "email": "ana@example.com", "phone": "+51999...",
    "address": "...", "city_residence": "Lima", "country_residence": "Perú",
    "metadata": {}                        // opcional, JSON libre
  },
  "seats": [                              // opcional (eventos numerados); su nº = quantity
    { "section": "VIP", "seat": "A-14" }
  ]
}
→ 201 { "order_id": 55, "payment_status": "pending_payment", ... }
   (200 si la external_reference ya existía — no duplica)
```
> El `amount` se valida contra `price × quantity`; si no cuadra → `422`. El monto guardado es el del servidor.

### 4a. Pago MANUAL (sube comprobante; el admin lo verifica)
```http
POST /orders/55/receipt        (multipart/form-data)
file=<jpg|png|pdf>
→ { "order_id": 55, "payment_status": "pending_verification" }
```
Cuando el admin lo aprueba en el panel, se emiten los tickets.

### 4b. Pago AUTOMÁTICO (webhook firmado) — emite al instante
```http
POST /webhooks/payment
X-Timestamp: 1718724000
X-Signature: <hmac>
{ "external_event_id": "evt_abc", "external_reference": "WP-10231", "status": "paid", "amount": 1000.00 }
→ { "payment_status": "verified", "tickets": [ { "code": "...", "qr_token": "...", "qr_image_url": "..." } ] }
```
**Firma (PHP):**
```php
$timestamp = (string) time();
$body      = $rawJsonExacto;                 // el MISMO string que envías
$signature = hash_hmac('sha256', $timestamp.'.'.$body, $webhook_secret);
// headers: X-Timestamp: $timestamp, X-Signature: $signature
```
Idempotente por `external_event_id`. Ventana anti-replay: 5 min.

### 5. Mostrar el QR al comprador
Con lo devuelto: usar `qr_image_url` (`GET /tickets/{code}/image` → PNG) **o** renderizar el `qr_token`. Guarda `code` + `qr_token` en tu BD.

### 6. Verificar / reconciliar — scope `tickets:read`
```http
GET /tickets/{code}/verify        → estado y validez (sin marcar usado)
GET /orders/{external_reference}  → estado + tickets (confrontar tu BD con la nuestra)
```

---

## B. Escáner de puerta

### 1. Login (usuario admin o gate)
```http
POST /admin/login
{ "email": "gate@...", "password": "..." }
→ { "access_token": "eyJ...", "user": { "role": "gate", "event_id": 1 } }
```

### 2. Validar el QR
El escáner lee la imagen → obtiene el string `qr_token` → lo envía:
```http
POST /tickets/validate
Authorization: Bearer <token-gate>
{ "qr_token": "v1.MDF...firma", "device": "puerta-01" }
→ { "result": "valid", "ticket": { "holder_name", "ticket_type", "event", "section", "seat", "used_at" } }
```
`result` ∈ `valid` · `already_used` · `invalid` · `not_paid` · `void` · `not_found` · `wrong_event`.
Un operador **gate** solo valida su evento asignado; **admin** valida cualquiera.

---

## Resumen

| Rol | Tiene | Hace |
|---|---|---|
| Tienda | `client_id`/`secret`, `webhook_secret` | token → catálogo → crear orden → confirmar pago (manual/webhook) → mostrar QR → reconciliar |
| Escáner | usuario gate/admin | login → leer QR → `POST /tickets/validate` |

Colección lista para probar: [`postman_collection.json`](postman_collection.json).
