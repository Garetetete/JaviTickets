<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Bindings interface (Contracts) -> implementación Eloquent.
 *
 * Cada repositorio nuevo se registra aquí, de modo que los Services solo
 * dependen de la interface (testeables con mocks). Ver
 * docs/specs/architecture/repositories.md.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Mapa Contract::class => Eloquent::class.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        \App\Repositories\Contracts\TourRepositoryInterface::class => \App\Repositories\Eloquent\EloquentTourRepository::class,
        \App\Repositories\Contracts\EventRepositoryInterface::class => \App\Repositories\Eloquent\EloquentEventRepository::class,
        \App\Repositories\Contracts\TicketTypeRepositoryInterface::class => \App\Repositories\Eloquent\EloquentTicketTypeRepository::class,
        \App\Repositories\Contracts\CustomerRepositoryInterface::class => \App\Repositories\Eloquent\EloquentCustomerRepository::class,
        \App\Repositories\Contracts\OrderRepositoryInterface::class => \App\Repositories\Eloquent\EloquentOrderRepository::class,
        \App\Repositories\Contracts\PaymentReceiptRepositoryInterface::class => \App\Repositories\Eloquent\EloquentPaymentReceiptRepository::class,
        \App\Repositories\Contracts\TicketRepositoryInterface::class => \App\Repositories\Eloquent\EloquentTicketRepository::class,
        \App\Repositories\Contracts\ScanLogRepositoryInterface::class => \App\Repositories\Eloquent\EloquentScanLogRepository::class,
        \App\Repositories\Contracts\ApiClientRepositoryInterface::class => \App\Repositories\Eloquent\EloquentApiClientRepository::class,
        \App\Repositories\Contracts\AdminUserRepositoryInterface::class => \App\Repositories\Eloquent\EloquentAdminUserRepository::class,
        \App\Repositories\Contracts\WebhookEventRepositoryInterface::class => \App\Repositories\Eloquent\EloquentWebhookEventRepository::class,
        \App\Repositories\Contracts\MetricsRepositoryInterface::class => \App\Repositories\Eloquent\EloquentMetricsRepository::class,
        \App\Repositories\Contracts\AuditLogRepositoryInterface::class => \App\Repositories\Eloquent\EloquentAuditLogRepository::class,
        \App\Repositories\Contracts\SeatRepositoryInterface::class => \App\Repositories\Eloquent\EloquentSeatRepository::class,
    ];

    public function register(): void
    {
        // $this->bindings se resuelve automáticamente por el contenedor.
    }

    public function boot(): void
    {
        //
    }
}
