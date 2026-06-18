<?php

namespace App\Http\Middleware;

use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registra en audit_logs cada acción de ESCRITURA del panel admin que termina
 * con éxito (2xx). Da trazabilidad de quién verificó/anuló/editó.
 */
class AuditAdminActions
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $audit,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $isWrite = in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
        $ok = $response->getStatusCode() < 400;

        if ($isWrite && $ok) {
            $this->audit->create([
                'admin_user_id' => Auth::guard('admin')->id(),
                'method' => $request->method(),
                'path' => $request->path(),
                'subject_id' => $request->route('id'),
                'status_code' => $response->getStatusCode(),
                'ip' => $request->ip(),
            ]);
        }

        return $response;
    }
}
