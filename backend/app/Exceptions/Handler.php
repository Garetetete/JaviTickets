<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        // Traduce excepciones de dominio a JSON con su status HTTP (misma
        // semántica que tenía bootstrap/app.php bajo L11).
        $this->renderable(function (DomainException $e, Request $request) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => $e->errorCode(),
            ], $e->status());
        });

        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
