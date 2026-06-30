<?php

namespace App\Http\Controllers\Api;

use App\DTOs\ValidateTicketData;
use App\Http\Controllers\Controller;
use App\Http\Requests\ValidateTicketRequest;
use App\Models\AdminUser;
use App\Services\ValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Endpoint de validación de tickets en puerta (guard admin, rol gate o admin).
 * Es la autoridad anti-doble-entrada; delega la lógica en ValidationService.
 */
class ValidationController extends Controller
{
    public function __construct(
        private readonly ValidationService $validation,
    ) {}

    /**
     * POST /tickets/validate — escaneo en puerta (anti-doble-entrada).
     * Un operador 'gate' valida solo su evento asignado; 'admin' valida cualquiera.
     */
    public function validateTicket(ValidateTicketRequest $request): JsonResponse
    {
        /** @var AdminUser $user */
        $user = Auth::guard('admin')->user();

        // gate atado a su evento; admin sin restricción de evento.
        $expectedEventId = $user->role === 'gate' ? $user->event_id : null;

        $result = $this->validation->validate(new ValidateTicketData(
            qrToken: $request->validated('qr_token'),
            gateUserId: $user->id,
            expectedEventId: $expectedEventId,
            ip: $request->ip(),
            device: $request->input('device'),
        ));

        return response()->json([
            'result' => $result->result,
            'ticket' => $result->ticket,
        ]);
    }
}
