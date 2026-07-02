<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida el payload del login de administradores/gate (POST /admin/login).
 * El acceso es público (genera el JWT); las credenciales se verifican en el servicio.
 */
class LoginRequest extends FormRequest
{
    /**
     * La autorización se delega al middleware (ruta pública de login).
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
            'email' => ['required', 'email:strict'],
            'password' => ['required', 'string'],
        ];
    }
}
