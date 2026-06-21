<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida el payload de creación/actualización de tipos de ticket (panel admin).
 * Consumido bajo auth:admin + role:admin.
 */
class TicketTypeRequest extends FormRequest
{
    /**
     * La autorización se delega al middleware (auth:admin/role:admin).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación del payload (required en create POST, sometimes en update).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $req = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'tour_id' => [$req, 'integer', 'exists:tours,id'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'slug' => [$req, 'string', 'max:191'],
            'name' => [$req, 'string', 'max:191'],
            'price' => [$req, 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'quota' => ['nullable', 'integer', 'min:0'],
            'order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
        ];
    }
}
