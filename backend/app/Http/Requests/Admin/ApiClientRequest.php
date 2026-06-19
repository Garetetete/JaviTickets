<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApiClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $req = $this->isMethod('post') ? 'required' : 'sometimes';
        $id = $this->route('id');

        return [
            'name' => [$req, 'string', 'max:191'],
            // No puramente numérico: el sub del JWT de cliente debe distinguirse
            // del id numérico de un usuario admin (ver middleware jwt.user).
            'client_id' => [$req, 'string', 'max:191', 'not_regex:/^\d+$/', Rule::unique('api_clients', 'client_id')->ignore($id)],
            'scopes' => [$req, 'array'],
            'scopes.*' => ['string', 'in:orders:write,tickets:read'],
            'webhook_secret' => ['nullable', 'string', 'max:191'],
            'is_active' => ['boolean'],
        ];
    }
}
