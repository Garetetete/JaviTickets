<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida el payload de creación/actualización de eventos (panel admin).
 * Consumido bajo auth:admin + role:admin.
 */
class EventRequest extends FormRequest
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
            'slug' => [$req, 'string', 'max:191'],
            'name' => [$req, 'string', 'max:191'],
            'country' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'venue' => ['nullable', 'string', 'max:191'],
            'event_date' => ['nullable', 'date'],
            'capacity' => [$req, 'integer', 'min:0'],
            'seating_type' => ['nullable', 'in:general,seated'],
            'is_active' => ['boolean'],
        ];
    }
}
