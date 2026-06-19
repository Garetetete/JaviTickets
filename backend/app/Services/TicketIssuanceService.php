<?php

namespace App\Services;

use App\Exceptions\CapacityExceededException;
use App\Exceptions\OrderNotVerifiedException;
use App\Exceptions\SeatUnavailableException;
use App\Models\Order;
use App\Models\Ticket;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\SeatRepositoryInterface;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Repositories\Contracts\TicketTypeRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Emite los tickets de una orden verificada, validando el aforo de forma
 * atómica (lock del evento dentro de una transacción) para que nunca se
 * emita por encima de la capacidad ni del cupo por tipo.
 */
class TicketIssuanceService
{
    public function __construct(
        private readonly EventRepositoryInterface $events,
        private readonly TicketTypeRepositoryInterface $ticketTypes,
        private readonly TicketRepositoryInterface $tickets,
        private readonly QrService $qr,
        private readonly SeatRepositoryInterface $seats,
    ) {}

    /**
     * @return Collection<int, Ticket>
     */
    public function issueForOrder(Order $order): Collection
    {
        if (! $order->isVerified()) {
            throw new OrderNotVerifiedException;
        }

        // Idempotencia: si ya tiene tickets, no re-emite.
        $alreadyIssued = $order->tickets()->get();
        if ($alreadyIssued->isNotEmpty()) {
            return $alreadyIssued;
        }

        return DB::transaction(function () use ($order) {
            $event = $this->events->lockForIssue($order->event_id);

            $issued = $this->events->countIssuedTickets($event->id);
            if ($issued + $order->quantity > $event->capacity) {
                throw new CapacityExceededException(
                    "Aforo del evento excedido (capacidad {$event->capacity}, emitidos {$issued})."
                );
            }

            $type = $this->ticketTypes->find($order->ticket_type_id);
            if ($type && $type->quota !== null) {
                $issuedType = $this->ticketTypes->countIssuedByType($type->id);
                if ($issuedType + $order->quantity > $type->quota) {
                    throw new CapacityExceededException(
                        "Cupo del tipo '{$type->slug}' excedido (cupo {$type->quota}, emitidos {$issuedType})."
                    );
                }
            }

            $snapshot = $this->customerSnapshot($order);
            $seatRequests = $order->seats ?? [];

            // Asignación de asientos (eventos numerados). La emisión ya está
            // serializada por el lock del evento; además el índice único parcial
            // garantiza a nivel de BD que un asiento no se vende dos veces.
            $hasInventory = $this->seats->forEvent($order->event_id)->isNotEmpty();
            $taken = array_flip($this->seats->takenSeatIds($order->event_id));
            $assignedNow = [];

            $rows = [];
            for ($i = 0; $i < $order->quantity; $i++) {
                $code = (string) Str::ulid();
                $section = $seatRequests[$i]['section'] ?? null;
                $label = $seatRequests[$i]['seat'] ?? null;
                $seatId = null;

                if ($label !== null && $hasInventory) {
                    $seat = $this->seats->findByLabel($order->event_id, $section ?? 'GENERAL', $label);

                    if ($seat === null || ! $seat->is_active) {
                        throw new SeatUnavailableException("Asiento '{$label}' no existe en el evento.");
                    }
                    if (isset($taken[$seat->id]) || isset($assignedNow[$seat->id])) {
                        throw new SeatUnavailableException("Asiento '{$label}' ya está ocupado.");
                    }

                    $seatId = $seat->id;
                    $assignedNow[$seat->id] = true;
                    $section = $seat->section;
                }

                $rows[] = [
                    'code' => $code,
                    'qr_token' => $this->qr->sign($code),
                    'key_version' => (int) config('qr.current_version', 1),
                    'order_id' => $order->id,
                    'ticket_type_id' => $order->ticket_type_id,
                    'event_id' => $order->event_id,
                    'customer_id' => $order->customer_id,
                    'status' => Ticket::STATUS_ACTIVE,
                    'section' => $section,
                    'seat' => $label,
                    'seat_id' => $seatId,
                    'metadata' => $snapshot,
                ];
            }

            return $this->tickets->createMany($rows);
        });
    }

    /**
     * Reemite un ticket (p. ej. extraviado): anula el anterior y crea uno
     * nuevo con code/qr_token frescos para la misma orden. No consume aforo
     * adicional (el anulado deja de contar).
     */
    public function reissue(Ticket $old, string $reason = 'reissue'): Ticket
    {
        return DB::transaction(function () use ($old, $reason) {
            $this->tickets->void($old->id, $reason);

            $code = (string) Str::ulid();

            return $this->tickets->createMany([[
                'code' => $code,
                'qr_token' => $this->qr->sign($code),
                'key_version' => (int) config('qr.current_version', 1),
                'order_id' => $old->order_id,
                'ticket_type_id' => $old->ticket_type_id,
                'event_id' => $old->event_id,
                'customer_id' => $old->customer_id,
                'status' => Ticket::STATUS_ACTIVE,
                'section' => $old->section,
                'seat' => $old->seat,
                'seat_id' => $old->seat_id, // mismo asiento (el anulado deja de contar)
                'metadata' => $old->metadata,
            ]])->first();
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function customerSnapshot(Order $order): array
    {
        $customer = $order->customer;

        if (! $customer) {
            return [];
        }

        return [
            'full_name' => $customer->full_name,
            'document_number' => $customer->document_number,
            'email' => $customer->email,
        ];
    }
}
