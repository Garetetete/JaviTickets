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
