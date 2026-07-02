<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida el payload de creación/actualización de tours (panel admin).
 * Consumido bajo auth:admin + role:admin.
 */
class TourRequest extends FormRequest
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
        $tourId = $this->route('id');

        return [
            'slug' => [$req, 'string', 'max:191', Rule::unique('tours', 'slug')->ignore($tourId)],
            'name' => [$req, 'string', 'max:191'],
            'artist_name' => [$req, 'string', 'max:191'],
            'owner_name' => ['nullable', 'string', 'max:191'],
            'owner_email' => ['nullable', 'email:strict', 'max:191'],
            'is_active' => ['boolean'],
        ];
    }
}
