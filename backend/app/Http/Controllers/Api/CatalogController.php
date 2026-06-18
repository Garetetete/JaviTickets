<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Http\Resources\TicketTypeResource;
use App\Http\Resources\TourResource;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\TicketTypeRepositoryInterface;
use App\Repositories\Contracts\TourRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Catálogo de solo lectura para integradores (la tienda descubre los IDs).
 * Solo expone elementos activos. Scope: tickets:read.
 */
class CatalogController extends Controller
{
    public function __construct(
        private readonly TourRepositoryInterface $tours,
        private readonly EventRepositoryInterface $events,
        private readonly TicketTypeRepositoryInterface $ticketTypes,
    ) {}

    public function tours(): AnonymousResourceCollection
    {
        return TourResource::collection($this->tours->allActive());
    }

    public function events(Request $request): AnonymousResourceCollection
    {
        return EventResource::collection($this->events->paginate(
            filters: ['is_active' => true, 'tour_id' => $request->integer('tour_id') ?: null],
            perPage: (int) $request->integer('per_page', 50),
        ));
    }

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
