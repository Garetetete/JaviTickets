<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida el payload del webhook de confirmación de pago (POST /webhooks/payment).
 * La firma HMAC y el anti-replay se verifican en el middleware verify.webhook.
 */
class WebhookPaymentRequest extends FormRequest
{
    /**
     * La autorización se delega al middleware (verify.webhook: HMAC + anti-replay).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación del payload.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'external_event_id' => ['required', 'string', 'max:191'],
            'external_reference' => ['required', 'string', 'max:191'],
            'status' => ['required', 'string', 'max:50'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
