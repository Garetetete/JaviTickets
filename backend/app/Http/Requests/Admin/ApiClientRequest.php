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
            'client_id' => [$req, 'string', 'max:191', Rule::unique('api_clients', 'client_id')->ignore($id)],
            'scopes' => [$req, 'array'],
            'scopes.*' => ['string', 'in:orders:write,tickets:read'],
            'webhook_secret' => ['nullable', 'string', 'max:191'],
            'is_active' => ['boolean'],
        ];
    }
}
