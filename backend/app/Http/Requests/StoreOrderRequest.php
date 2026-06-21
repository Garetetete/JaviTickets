<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida el payload de creación de órdenes (POST /orders).
 * Consumido por la tienda externa bajo auth.api_client + scope orders:write.
 */
class StoreOrderRequest extends FormRequest
{
    /**
     * La autorización se delega al middleware (auth.api_client/scope).
     */
    public function authorize(): bool
    {
        return true; // autorización vía middleware auth.api_client + scope
    }

    /**
     * Reglas de validación del payload.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'external_reference' => ['nullable', 'string', 'max:191'],
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'ticket_type_id' => ['required', 'integer', 'exists:ticket_types,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'payment_method' => ['nullable', 'string', 'max:50'],

            'customer' => ['required', 'array'],
            'customer.first_name' => ['required', 'string', 'max:191'],
            'customer.second_name' => ['nullable', 'string', 'max:191'],
            'customer.last_name' => ['required', 'string', 'max:191'],
            'customer.second_last_name' => ['nullable', 'string', 'max:191'],
            'customer.document_type' => ['nullable', 'string', 'max:30'],
            'customer.document_number' => ['required', 'string', 'max:50'],
            'customer.email' => ['required', 'email', 'max:191'],
            'customer.phone' => ['nullable', 'string', 'max:30'],
            'customer.address' => ['nullable', 'string', 'max:255'],
            'customer.city_residence' => ['nullable', 'string', 'max:120'],
            'customer.country_residence' => ['nullable', 'string', 'max:120'],
            'customer.metadata' => ['nullable', 'array'],

            // Asignación opcional de asientos/secciones (eventos numerados).
            'seats' => ['nullable', 'array'],
            'seats.*.section' => ['nullable', 'string', 'max:50'],
            'seats.*.seat' => ['nullable', 'string', 'max:50'],
        ];
    }
}
