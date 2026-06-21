<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ScanLogResource;
use App\Repositories\Contracts\ScanLogRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Panel admin: consulta del log de escaneos (append-only) por evento.
 * Protegido por auth:admin + role:admin.
 */
class ScanController extends Controller
{
    public function __construct(
        private readonly ScanLogRepositoryInterface $scans,
    ) {}

    /**
     * GET /admin/scans — lista paginada de escaneos de un evento, filtrable por
     * result y rango de fechas. 422 si falta el event_id requerido.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['event_id' => ['required', 'integer']]);

        $logs = $this->scans->paginateByEvent(
            (int) $request->integer('event_id'),
            [
                'result' => $request->input('result'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
            ],
            (int) $request->integer('per_page', 20),
        );

        return ScanLogResource::collection($logs);
    }
}
