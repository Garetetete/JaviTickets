<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TourRequest extends FormRequest
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
        $tourId = $this->route('id');

        return [
            'slug' => [$req, 'string', 'max:191', Rule::unique('tours', 'slug')->ignore($tourId)],
            'name' => [$req, 'string', 'max:191'],
            'artist_name' => [$req, 'string', 'max:191'],
            'owner_name' => ['nullable', 'string', 'max:191'],
            'owner_email' => ['nullable', 'email', 'max:191'],
            'is_active' => ['boolean'],
        ];
    }
}
