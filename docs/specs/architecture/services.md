# Spec — Capa Service + Support/Qr (`docs/specs/architecture/services.md`)

Los Services contienen la lógica de negocio, orquestan repositorios (solo interfaces) y lanzan excepciones de dominio. Testeables con repositorios mockeados (salvo aforo/concurrencia, que requieren BD).

---

## QrService  (usa `app/Support/Qr/`)
Responsable de firma y representación del QR. **No** toca BD.

- `sign(string $code, int $keyVersion = current): string`
  → `qr_token = base64url(code) . "." . base64url(HMAC_SHA256(code . "|" . keyVersion, secret(keyVersion)))`.
- `verify(string $qrToken): QrVerifyResult` → `{ valid: bool, code: ?string, keyVersion: ?int }`.
  Recomputa HMAC con el secreto de esa `keyVersion`; rechaza si no coincide o el formato es inválido.
- `toImage(string $qrToken, string $format = 'png'): string` → bytes PNG/SVG (librería QR).
- **Rotación de clave:** `secret(version)` lee de config un mapa `version => secret`. Tokens viejos siguen verificando con su versión; los nuevos usan `QR_KEY_VERSION` actual.

**Tests:** firma determinista; token manipulado (1 byte cambiado) → `valid=false`; verificación con versión antigua tras rotación.

---

## TicketIssuanceService
Emite tickets tras pago verificado, con **aforo atómico**.

- `issueForOrder(Order $order): Collection<Ticket>`
  1. Abre transacción.
  2. `EventRepository::lockForIssue(order.event_id)` (FOR UPDATE).
  3. Valida `countIssuedTickets(event) + order.quantity <= event.capacity` → si no, `CapacityExceededException` (409).
  4. Si el `ticket_type.quota` no es null: valida `countIssuedByType + quantity <= quota` → si no, `CapacityExceededException`.
  5. Genera `code` (ULID) por ticket, `qr_token = QrService::sign(code)`, snapshot de datos del titular en `metadata`.
  6. `TicketRepository::createMany(...)` con `status = active`.
  7. Commit. Devuelve los tickets.
- Idempotente: si la orden ya tiene tickets emitidos, los devuelve sin re-emitir.

**Excepciones:** `CapacityExceededException`, `OrderNotVerifiedException`.
**Tests:** aforo límite exacto; exceso → excepción; concurrencia (dos emisiones simultáneas no superan capacity — test con BD).

---

## PaymentService
Registra órdenes y gestiona los **dos flujos de pago**.

- `createOrder(OrderData $data): Order`
  - `CustomerRepository::firstOrCreate(...)`; idempotencia por `external_reference` (si existe, devuelve la orden existente).
  - Crea orden en `pending_payment`. **No** emite tickets.
- `attachReceipt(int $orderId, ReceiptData $data): Order`  *(flujo manual)*
  - Guarda archivo (Storage privado) vía `PaymentReceiptRepository`; pasa orden a `pending_verification`.
- `verifyManually(int $orderId, int $adminUserId): Collection<Ticket>`  *(admin)*
  - Valida estado `pending_verification|pending_payment`; `markVerified`; delega en `TicketIssuanceService::issueForOrder`.
- `rejectManually(int $orderId, string $reason): Order`
- `handleWebhook(WebhookPaymentData $data): Collection<Ticket>`  *(flujo automático)*
  - Verifica idempotencia (`WebhookEventRepository::existsByExternalEventId`); registra `webhook_events`.
  - Verifica firma ya validada por middleware `verify.webhook` (o re-verifica aquí).
  - Resuelve orden por `external_reference` (o la crea si el webhook trae todo); `markVerified`; emite tickets; `markProcessed`.

**Excepciones:** `OrderNotFoundException`, `OrderAlreadyVerifiedException`, `InvalidPaymentStateException`, `DuplicateWebhookException`.
**Tests:** idempotencia de `createOrder` y de webhook; transición de estados; verify manual emite; reject no emite.

---

## ValidationService
Validación en puerta — **autoridad anti-doble-entrada**.

- `validate(ValidateTicketData $data): ValidationResult`
  1. `QrService::verify(qr_token)` → si inválido: registra `scan_log(invalid_signature)`, devuelve `invalid`.
  2. Transacción + `TicketRepository::lockByCodeForUpdate(code)`.
  3. Si no existe → `scan_log(not_found)`, `not_found`.
  4. Si `event` esperado ≠ ticket.event (operador asignado a otro evento) → `wrong_event`.
  5. Si `status = void` → `void`; si orden no pagada → `not_paid`.
  6. Si `status = used`:
     - dentro de ventana anti-rebote (config, ej. 5s) respecto al último `scan_log` válido del mismo `code` y mismo operador → tratar como repetición benigna (`valid`, sin nuevo conteo);
     - si no → `already_used` (con `used_at` original).
  7. Si `active/issued` → `markUsed(ticket, gateUserId)`; `scan_log(valid)`; commit; devuelve `valid` + nombre/tipo/evento.

**ValidationResult:** `{ result: enum, ticket?: {holder_name, ticket_type, event, used_at} }`.
**Tests:** válido → used; segundo escaneo → already_used; firma falsa → invalid; sin pago → not_paid; concurrencia (dos validaciones simultáneas del mismo code → una sola `used`).

---

## MetricsService
Agregados para el dashboard admin.

- `eventOverview(int $eventId): array` → `{ capacity, issued, used, available, no_show_rate }`.
- `salesByType(int $eventId): array` → por `ticket_type`: emitidos, usados, ingresos.
- `revenue(filters): array` → ingresos por tour/evento/rango.
- `scanTimeline(int $eventId): array` → validaciones por franja horaria.

---

## AuthService (JWT)
- `loginAdmin(email, password): {access, refresh, user}` — guard `admin`, valida `role`/`is_active`.
- `issueClientToken(clientId, clientSecret): {access, scopes}` — client-credentials para la tienda.
- `refresh(token)`, `logout(token)`, `me(token)`.
- Scopes (cliente): `orders:write`, `tickets:read`. Roles (usuario): `admin`, `gate`.

---

## DTOs
- `OrderData` (customer fields + ticket_type_id + event_id + quantity + external_reference + amount + currency + payment_method).
- `ReceiptData` (order_id + file + uploaded_by).
- `WebhookPaymentData` (external_event_id + external_reference + amount + status + raw payload).
- `IssueTicketData` (interno; derivado de Order).
- `ValidateTicketData` (qr_token + gate_user_id + expected_event_id? + ip/device).
- `TicketFilters`, `OrderFilters`.

## Excepciones de dominio (→ HTTP en Controllers)
`CapacityExceededException` (409), `TicketAlreadyUsedException` (409), `InvalidQrSignatureException` (422), `OrderNotFoundException` (404), `OrderNotVerifiedException` (409), `OrderAlreadyVerifiedException` (409), `InvalidPaymentStateException` (409), `DuplicateWebhookException` (200/idempotente), `City/EventNotFoundException` (404), `UnauthorizedScopeException` (403).

---

**Criterio de aceptación:** aforo, transiciones de estado de pago/ticket, firma y anti-doble-entrada viven 100% en Services, verificables con tests (mocks + BD para concurrencia). Ningún Controller decide reglas de negocio.
