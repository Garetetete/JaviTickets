<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\SeatRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeatController extends Controller
{
    public function __construct(
        private readonly SeatRepositoryInterface $seats,
    ) {}

    /** Asientos del evento con su estado (libre/ocupado). */
    public function index(int $eventId): JsonResponse
    {
        return response()->json([
            'event_id' => $eventId,
            'seats' => $this->seats->availabilityForEvent($eventId),
        ]);
    }

    /** Alta masiva de asientos para un evento. */
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
     * Genera asientos por rango: filas (explícitas o de row_from..row_to)
     * × números (seat_from..seat_to). Etiquetas tipo "A-1".
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

    public function destroy(int $id): JsonResponse
    {
        $this->seats->delete($id);

        return response()->json(['message' => 'Asiento eliminado.']);
    }

    public function restore(int $id): JsonResponse
    {
        $this->seats->restore($id);

        return response()->json(['message' => 'Asiento restaurado.']);
    }
}
