<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\SeatRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Panel admin: inventario de asientos por evento (eventos numerados).
 * Protegido por auth:admin + role:admin. Soporta alta masiva, generación por
 * rango y soft-delete/restore.
 */
class SeatController extends Controller
{
    public function __construct(
        private readonly SeatRepositoryInterface $seats,
    ) {}

    /**
     * GET /admin/events/{id}/seats — asientos del evento con su estado
     * (libre/ocupado).
     */
    public function index(int $eventId): JsonResponse
    {
        return response()->json([
            'event_id' => $eventId,
            'seats' => $this->seats->availabilityForEvent($eventId),
        ]);
    }

    /**
     * POST /admin/events/{id}/seats — alta masiva de asientos para un evento.
     * Responde 201. 422 si la validación falla.
     */
    public function store(Request $request, int $eventId): JsonResponse
    {
        $data = $request->validate([
            'seats' => ['required', 'array', 'min:1'],
            'seats.*.section' => ['nullable', 'string', 'max:50'],
            'seats.*.label' => ['required', 'string', 'max:50'],
        ]);

        $created = $this->seats->bulkCreate($eventId, $data['seats']);

        return response()->json(['event_id' => $eventId, 'created' => $created], 201);
    }

    /**
     * POST /admin/events/{id}/seats/generate — genera asientos por rango: filas
     * (explícitas o de row_from..row_to) × números (seat_from..seat_to).
     * Etiquetas tipo "A-1". 422 si faltan filas o se exceden 5000 asientos.
     */
    public function generate(Request $request, int $eventId): JsonResponse
    {
        $data = $request->validate([
            'section' => ['nullable', 'string', 'max:50'],
            'rows' => ['nullable', 'array'],
            'rows.*' => ['string', 'max:10'],
            'row_from' => ['nullable', 'string', 'max:10'],
            'row_to' => ['nullable', 'string', 'max:10'],
            'seat_from' => ['required', 'integer', 'min:1'],
            'seat_to' => ['required', 'integer', 'gte:seat_from'],
        ]);

        $section = $data['section'] ?? 'GENERAL';
        $rows = $data['rows'] ?? null;

        if (! $rows) {
            if (empty($data['row_from']) || empty($data['row_to'])) {
                abort(422, 'Indica "rows" (lista) o "row_from" + "row_to".');
            }
            $rows = range($data['row_from'], $data['row_to']);
        }

        $numbers = range($data['seat_from'], $data['seat_to']);

        if (count($rows) * count($numbers) > 5000) {
            abort(422, 'Demasiados asientos en una sola operación (máx. 5000).');
        }

        $seatRows = [];
        foreach ($rows as $row) {
            foreach ($numbers as $n) {
                $seatRows[] = ['section' => $section, 'label' => "{$row}-{$n}"];
            }
        }

        $created = $this->seats->bulkCreate($eventId, $seatRows);

        return response()->json(['event_id' => $eventId, 'section' => $section, 'created' => $created], 201);
    }

    /**
     * DELETE /admin/seats/{id} — soft-delete de un asiento.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->seats->delete($id);

        return response()->json(['message' => 'Asiento eliminado.']);
    }

    /**
     * POST /admin/seats/{id}/restore — restaura un asiento borrado.
     */
    public function restore(int $id): JsonResponse
    {
        $this->seats->restore($id);

        return response()->json(['message' => 'Asiento restaurado.']);
    }
}
