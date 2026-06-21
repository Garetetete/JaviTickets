<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la subida del desprendible de pago manual (POST /orders/{id}/receipt).
 * Consumido por la tienda externa bajo auth.api_client + scope orders:write.
 */
class UploadReceiptRequest extends FormRequest
{
    /**
     * La autorización se delega al middleware (auth.api_client/scope).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación del payload.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'], // 8 MB
            'uploaded_by' => ['nullable', 'string', 'max:191'],
        ];
    }
}
