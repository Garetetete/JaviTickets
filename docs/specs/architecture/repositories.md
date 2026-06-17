# Spec — Capa Repository (`docs/specs/architecture/repositories.md`)

Una interface por agregado en `app/Repositories/Contracts/`; implementación Eloquent en `app/Repositories/Eloquent/`. Bindings en `RepositoryServiceProvider`. **Ningún acceso a Eloquent fuera de `Eloquent/`.**

Métodos base comunes (donde aplique): `find($id)`, `create(array $data)`, `update($id, array $data)`, `delete($id)` (soft), `restore($id)`, `paginateWithFilters(array $filters, int $perPage)`.

---

## TourRepositoryInterface
- `find(int $id): ?Tour`
- `findBySlug(string $slug): ?Tour`
- `allActive(): Collection`
- `create(array $data): Tour`
- `update(int $id, array $data): Tour`
- `delete(int $id): bool` / `restore(int $id): bool`

## EventRepositoryInterface
- `find(int $id): ?Event`
- `findBySlug(int $tourId, string $slug): ?Event`
- `allActiveByTour(int $tourId): Collection`
- `countIssuedTickets(int $eventId): int` — tickets en estado `issued|active|used` (cuenta para aforo).
- `lockForIssue(int $eventId): Event` — `SELECT ... FOR UPDATE` dentro de transacción (aforo atómico).
- CRUD + soft-delete/restore.

## TicketTypeRepositoryInterface
- `find(int $id): ?TicketType`
- `findBySlug(int $tourId, string $slug): ?TicketType`
- `allActiveByTour(int $tourId): Collection`
- `countIssuedByType(int $ticketTypeId): int` — para `quota` por tipo.
- CRUD + soft-delete/restore.

## CustomerRepositoryInterface
- `find(int $id): ?Customer`
- `findByDocument(string $type, string $number): ?Customer`
- `findByEmail(string $email): ?Customer`
- `firstOrCreate(array $data): Customer` — reuso de comprador en compras repetidas.
- CRUD + soft-delete/restore.

## OrderRepositoryInterface
- `find(int $id): ?Order`
- `findByExternalReference(string $ref): ?Order` — idempotencia/reconciliación.
- `paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator` — filtros: `payment_status`, `event_id`, `ticket_type_id`, `customer_id`, `date_from`, `date_to`.
- `create(array $data): Order` / `update(int $id, array $data): Order`
- `markVerified(int $id, ?int $adminUserId): Order`
- `markRejected(int $id, string $reason): Order`
- soft-delete/restore.

## PaymentReceiptRepositoryInterface
- `create(array $data): PaymentReceipt`
- `forOrder(int $orderId): Collection`

## TicketRepositoryInterface
- `find(int $id): ?Ticket`
- `findByCode(string $code): ?Ticket`
- `lockByCodeForUpdate(string $code): ?Ticket` — `FOR UPDATE` (marcado `used` atómico).
- `createMany(array $rows): Collection` — emisión en lote (cantidad de la orden).
- `markUsed(int $id, ?int $gateUserId): Ticket`
- `void(int $id, string $reason): Ticket`
- `paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator` — filtros: `event_id`, `ticket_type_id`, `status`, `customer_id`, `date_from`, `date_to`.
- soft-delete/restore.

## ScanLogRepositoryInterface  *(append-only)*
- `create(array $data): ScanLog`
- `findLastForCode(string $code): ?ScanLog` — ventana anti-rebote.
- `paginateByEvent(int $eventId, array $filters, int $perPage): LengthAwarePaginator`

## ApiClientRepositoryInterface
- `findByClientId(string $clientId): ?ApiClient`
- `create(array $data): ApiClient` / `rotateSecret(int $id): array` (devuelve nuevo secret en claro una sola vez)
- CRUD + soft-delete/restore.

## AdminUserRepositoryInterface
- `findByEmail(string $email): ?AdminUser`
- CRUD + soft-delete/restore.

## WebhookEventRepositoryInterface  *(append-only + idempotencia)*
- `existsByExternalEventId(string $id): bool`
- `create(array $data): WebhookEvent`
- `markProcessed(int $id): void`

---

**Criterio de aceptación de la capa:** todos los métodos arriba con firma estable y test unitario para los no triviales (aforo, `findByCode`, `lockForIssue`, `firstOrCreate`, idempotencia de `existsByExternalEventId`, soft-delete/restore).
