<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly MetricsService $metrics,
    ) {}

    public function metrics(Request $request): JsonResponse
    {
        $request->validate(['event_id' => ['required', 'integer']]);
        $eventId = (int) $request->integer('event_id');

        return response()->json([
            'overview' => $this->metrics->eventOverview($eventId),
            'sales_by_type' => $this->metrics->salesByType($eventId),
            'revenue' => $this->metrics->revenue($eventId),
            'scan_results' => $this->metrics->scanResults($eventId),
        ]);
    }
}
