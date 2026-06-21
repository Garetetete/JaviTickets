<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Http\Resources\TicketTypeResource;
use App\Http\Resources\TourResource;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\SeatRepositoryInterface;
use App\Repositories\Contracts\TicketTypeRepositoryInterface;
use App\Repositories\Contracts\TourRepositoryInterface;
use App\Services\MetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Catálogo de solo lectura para integradores (la tienda descubre los IDs,
 * la disponibilidad y los asientos libres). Solo expone elementos activos.
 * Scope: tickets:read.
 */
class CatalogController extends Controller
{
    public function __construct(
        private readonly TourRepositoryInterface $tours,
        private readonly EventRepositoryInterface $events,
        private readonly TicketTypeRepositoryInterface $ticketTypes,
        private readonly SeatRepositoryInterface $seats,
        private readonly MetricsService $metrics,
    ) {}

    /** Disponibilidad (aforo restante global y por tipo). */
    public function availability(int $id): JsonResponse
    {
        return response()->json($this->metrics->availability($id));
    }

    /** Asientos del evento con su estado (libre/ocupado). */
    public function seats(int $id, Request $request): JsonResponse
    {
        return response()->json([
            'event_id' => $id,
            'seats' => $this->seats->availabilityForEvent($id, $request->input('section')),
        ]);
    }

    /** Lista los tours activos. */
    public function tours(): AnonymousResourceCollection
    {
        return TourResource::collection($this->tours->allActive());
    }

    /** Lista los eventos activos, opcionalmente filtrados por tour_id. */
    public function events(Request $request): AnonymousResourceCollection
    {
        return EventResource::collection($this->events->paginate(
            filters: ['is_active' => true, 'tour_id' => $request->integer('tour_id') ?: null],
            perPage: (int) $request->integer('per_page', 50),
        ));
    }

    /** Lista los tipos de ticket activos, filtrables por tour_id y event_id. */
    public function ticketTypes(Request $request): AnonymousResourceCollection
    {
        return TicketTypeResource::collection($this->ticketTypes->paginate(
            filters: [
                'is_active' => true,
                'tour_id' => $request->integer('tour_id') ?: null,
                'event_id' => $request->integer('event_id') ?: null,
            ],
            perPage: (int) $request->integer('per_page', 50),
        ));
    }
}
