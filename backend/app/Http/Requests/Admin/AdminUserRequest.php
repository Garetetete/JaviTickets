<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUserRequest extends FormRequest
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
