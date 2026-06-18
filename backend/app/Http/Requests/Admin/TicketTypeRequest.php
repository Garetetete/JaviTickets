<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TicketTypeRequest extends FormRequest
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
