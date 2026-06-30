<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida el payload de creación/actualización de usuarios admin/gate (panel admin).
 * Consumido bajo auth:admin + role:admin.
 */
class AdminUserRequest extends FormRequest
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
        $isCreate = $this->isMethod('post');
        $req = $isCreate ? 'required' : 'sometimes';
        $id = $this->route('id');

        return [
            'name' => [$req, 'string', 'max:191'],
            'email' => [$req, 'email', 'max:191', Rule::unique('admin_users', 'email')->ignore($id)],
            'password' => [$isCreate ? 'required' : 'nullable', 'string', 'min:8'],
            'role' => [$req, Rule::in(['admin', 'gate'])],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'is_active' => ['boolean'],
        ];
    }
}
