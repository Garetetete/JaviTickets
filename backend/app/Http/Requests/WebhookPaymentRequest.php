<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebhookPaymentRequest extends FormRequest
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
        return [
            'external_event_id' => ['required', 'string', 'max:191'],
            'external_reference' => ['required', 'string', 'max:191'],
            'status' => ['required', 'string', 'max:50'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
