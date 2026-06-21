<?php

namespace App\Services;

use App\DTOs\ValidateTicketData;
use App\DTOs\ValidationResult;
use App\Models\Order;
use App\Models\ScanLog;
use App\Models\Ticket;
use App\Repositories\Contracts\ScanLogRepositoryInterface;
use App\Repositories\Contracts\TicketRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Valida un QR en puerta. Es la autoridad anti-doble-entrada: el primer
 * escaneo marca el ticket `used` (con lock); un segundo devuelve already_used.
 */
class ValidationService
{
    public function __construct(
        private readonly QrService $qr,
        private readonly TicketRepositoryInterface $tickets,
        private readonly ScanLogRepositoryInterface $scanLogs,
    ) {}

    /**
     * Valida un QR en puerta: verifica la firma, bloquea el ticket por code,
     * evalúa su estado (pago, anulación, evento, uso previo) y, si procede, lo
     * marca como usado de forma atómica. Registra siempre un scan_log.
     */
    public function validate(ValidateTicketData $data): ValidationResult
    {
        $verify = $this->qr->verify($data->qrToken);

        if (! $verify->valid) {
            $this->log($data, null, null, ScanLog::RESULT_INVALID_SIGNATURE);

            return new ValidationResult('invalid');
        }

        $code = $verify->code;

        return DB::transaction(function () use ($data, $code) {
            $ticket = $this->tickets->lockByCodeForUpdate($code);

            if ($ticket === null) {
                $this->log($data, null, $code, ScanLog::RESULT_NOT_FOUND);

                return new ValidationResult('not_found');
            }

            if ($data->expectedEventId !== null && $ticket->event_id !== $data->expectedEventId) {
                $this->log($data, $ticket->id, $code, ScanLog::RESULT_WRONG_EVENT, $ticket->event_id);

                return new ValidationResult('wrong_event');
            }

            if ($ticket->status === Ticket::STATUS_VOID) {
                $this->log($data, $ticket->id, $code, ScanLog::RESULT_VOID, $ticket->event_id);

                return new ValidationResult('void');
            }

            if (! $this->isPaid($ticket)) {
                $this->log($data, $ticket->id, $code, ScanLog::RESULT_NOT_PAID, $ticket->event_id);

                return new ValidationResult('not_paid');
            }

            if ($ticket->status === Ticket::STATUS_USED) {
                if ($this->withinRebounceWindow($code, $data)) {
                    // Repetición benigna: no se vuelve a contar.
                    return new ValidationResult('valid', $this->ticketInfo($ticket));
                }

                $this->log($data, $ticket->id, $code, ScanLog::RESULT_ALREADY_USED, $ticket->event_id);

                return new ValidationResult('already_used', $this->ticketInfo($ticket));
            }

            // issued | active -> marcar usado.
            $ticket = $this->tickets->markUsed($ticket->id, $data->gateUserId);
            $this->log($data, $ticket->id, $code, ScanLog::RESULT_VALID, $ticket->event_id);

            return new ValidationResult('valid', $this->ticketInfo($ticket));
        });
    }

    /**
     * Indica si el pago de la orden del ticket está verificado.
     */
    private function isPaid(Ticket $ticket): bool
    {
        return optional($ticket->order)->payment_status === Order::STATUS_VERIFIED;
    }

    /**
     * Determina si un re-escaneo del mismo code, por el mismo operador, cae
     * dentro de la ventana anti-rebote configurada (evita penalizar dobles
     * lecturas accidentales).
     */
    private function withinRebounceWindow(string $code, ValidateTicketData $data): bool
    {
        $window = (int) config('qr.scan_rebounce_seconds', 5);

        if ($window <= 0) {
            return false; // anti-rebote desactivado
        }

        $last = $this->scanLogs->findLastForCode($code);

        if ($last === null || $last->result !== ScanLog::RESULT_VALID) {
            return false;
        }

        $sameOperator = $last->scanned_by === $data->gateUserId;

        return $sameOperator && abs($last->created_at->diffInSeconds(now())) <= $window;
    }

    /**
     * Construye los datos mínimos del asistente para la respuesta de validación.
     *
     * @return array<string, mixed>
     */
    private function ticketInfo(Ticket $ticket): array
    {
        $ticket->loadMissing(['customer', 'ticketType', 'event']);

        return [
            'code' => $ticket->code,
            'holder_name' => optional($ticket->customer)->full_name,
            'ticket_type' => optional($ticket->ticketType)->name,
            'event' => optional($ticket->event)->name,
            'section' => $ticket->section,
            'seat' => $ticket->seat,
            'used_at' => optional($ticket->used_at)?->toIso8601String(),
        ];
    }

    /**
     * Registra un intento de validación en la bitácora append-only `scan_logs`.
     */
    private function log(
        ValidateTicketData $data,
        ?int $ticketId,
        ?string $code,
        string $result,
        ?int $eventId = null,
    ): void {
        $this->scanLogs->create([
            'ticket_id' => $ticketId,
            'code' => $code,
            'event_id' => $eventId,
            'result' => $result,
            'scanned_by' => $data->gateUserId,
            'ip' => $data->ip,
            'device' => $data->device,
        ]);
    }
}
