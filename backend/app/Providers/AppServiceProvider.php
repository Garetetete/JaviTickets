<?php

namespace App\Providers;

use App\Support\Qr\QrSigner;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(QrSigner::class, function () {
            return new QrSigner(
                secrets: (array) config('qr.secrets', []),
                currentVersion: (int) config('qr.current_version', 1),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // API sin envoltura "data": las respuestas exponen los campos al nivel superior.
        JsonResource::withoutWrapping();
    }
}
