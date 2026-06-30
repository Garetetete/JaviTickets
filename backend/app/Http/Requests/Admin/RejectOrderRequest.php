<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida el payload del rechazo manual de pago de una orden (panel admin).
 * Consumido bajo auth:admin + role:admin.
 */
class RejectOrderRequest extends FormRequest
{
    /**
     * La autorización se delega al middleware (auth:admin/role:admin).
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
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
