<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class EventRequest extends FormRequest
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

        return [
            'tour_id' => [$req, 'integer', 'exists:tours,id'],
            'slug' => [$req, 'string', 'max:191'],
            'name' => [$req, 'string', 'max:191'],
            'country' => ['nullable', 'string', 'max:120'],
            'venue' => ['nullable', 'string', 'max:191'],
            'event_date' => ['nullable', 'date'],
            'capacity' => [$req, 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
