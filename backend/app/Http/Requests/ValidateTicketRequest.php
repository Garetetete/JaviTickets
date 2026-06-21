<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida el payload de validación de tickets en puerta (POST /tickets/validate).
 * Consumido por el escáner bajo auth:admin + role:gate,admin.
 */
class ValidateTicketRequest extends FormRequest
{
    /**
     * La autorización se delega al middleware (auth:admin/role:gate,admin).
     */
    public function authorize(): bool
    {
        return true; // autorización vía auth:admin + role:gate,admin
    }

    /**
     * Reglas de validación del payload.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'qr_token' => ['required', 'string'],
            'device' => ['nullable', 'string', 'max:100'],
        ];
    }
}
