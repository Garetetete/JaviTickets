# Spec — Migración a Laravel 10 + PHP 8.1.34

> Estado: **✅ COMPLETADA Y VERIFICADA** (Laravel Framework 10.50.2, PHP 8.1.34, jwt-auth 2.7.3,
> phpunit 10.5.63). Metodología SDD: este documento es el contrato de la migración.
> Objetivo: bajar el backend de **Laravel 11 / PHP 8.2** a **Laravel 10 / PHP 8.1.34**
> sin cambiar el comportamiento funcional ni romper ninguna propiedad de seguridad.
>
> **Resultado:** `php artisan test` → **82 passed (244 assertions)** (mismo conteo que en L11),
> incluida toda la auditoría de seguridad. `GET /api/v1/up` → 200 en vivo bajo L10.
> `php artisan config:cache` y `php artisan route:cache` funcionan (producción).
>
> **Mejora adicional (apta para prod):** las rutas `GET /` y `GET /api/v1/up` eran *closures*
> (impedían `route:cache`); se movieron a `App\Http\Controllers\HealthController`
> (`welcome()` / `up()`), preservando el comportamiento. Ahora `route:cache` cachea todo.

---

## 1. Motivación y criterio de aceptación

- **Por qué:** el entorno de despliegue objetivo corre PHP 8.1.34 y Laravel 10.
- **Criterio de aceptación:**
  1. `composer install` resuelve sobre PHP 8.1 sin conflictos.
  2. `php artisan test` → **todos los tests verdes** (misma suite, mismas aserciones).
  3. `GET /api/v1/up` → 200; el flujo completo (orden → pago → emisión → validación) funciona.
  4. Ninguna propiedad de seguridad del núcleo se degrada (ver `tests/Feature/SecurityAuditTest.php`).
  5. Documentación (CLAUDE.md, README, deployment, SDD) refleja L10/PHP 8.1.

---

## 2. Diferencias estructurales L11 → L10 (el grueso del trabajo)

Laravel 11 introdujo un esqueleto "slim" que consolida arranque, middleware, rutas y
excepciones en `bootstrap/app.php`. Laravel 10 usa el esqueleto clásico con Kernels y
Handler explícitos. La migración revierte esa consolidación.

| Concepto | Laravel 11 (actual) | Laravel 10 (destino) |
|---|---|---|
| Arranque | `bootstrap/app.php` con `Application::configure()` | `bootstrap/app.php` clásico (instancia + 3 singletons) |
| HTTP Kernel | (implícito) | `app/Http/Kernel.php` (grupos + aliases de middleware) |
| Console Kernel | `routes/console.php` (`Schedule::`) | `app/Console/Kernel.php` (`schedule()` + `commands()`) |
| Excepciones | `->withExceptions()` en bootstrap | `app/Exceptions/Handler.php` (`register()` + `renderable`) |
| Middleware por defecto | (incluidos en el framework) | clases en `app/Http/Middleware/*` |
| Aliases de middleware | `->withMiddleware()->alias([...])` | `$middlewareAliases` en `Http\Kernel` |
| Prioridad de middleware | `->withMiddleware()->priority([...])` | `$middlewarePriority` en `Http\Kernel` |
| Prefijo API (`api/v1`) | `withRouting(apiPrefix: 'api/v1')` | `RouteServiceProvider` (`prefix('api/v1')`) |
| Rate limiter `api` | implícito | `RouteServiceProvider::boot()` (`RateLimiter::for('api')`) |
| Registro de providers | `bootstrap/providers.php` | `config/app.php` (`'providers' => [...]`) |
| Health check | `withRouting(health: '/up')` | se conserva el `GET /api/v1/up` propio en `routes/api.php` |

### Config que L11 elimina y L10 necesita (CREAR)
- `config/view.php` — sin él, `config('view.paths')` es null y **cualquier** `response()` revienta
  al inicializar el view finder (`FileViewFinder`).
- `config/hashing.php` — define el driver y `BCRYPT_ROUNDS`.
- `config/cors.php` — para `HandleCors` (ver arriba).

### Archivos a CREAR (esqueleto L10)
- `app/Http/Kernel.php` — grupos `web`/`api`, `$middlewareAliases` (incluye los 6 custom:
  `auth.api_client`, `scope`, `verify.webhook`, `role`, `audit.admin`, `jwt.user`),
  `$middlewarePriority` con `EnsureUserToken` antes del guard `Authenticate`.
- `app/Console/Kernel.php` — `schedule()` con `tickets:expire dailyAt('04:00')`; `commands()`.
- `app/Exceptions/Handler.php` — `renderable(DomainException)` → JSON `{message, error}` con `status()`.
- `app/Http/Middleware/` por defecto: `Authenticate`, `EncryptCookies`,
  `PreventRequestsDuringMaintenance`, `RedirectIfAuthenticated`, `TrimStrings`,
  `TrustHosts`, `TrustProxies`, `ValidateSignature`, `VerifyCsrfToken`.
- `app/Providers/RouteServiceProvider.php` — `api/v1` + rate limiter + `web`.
- `app/Providers/EventServiceProvider.php`, `app/Providers/AuthServiceProvider.php`.

### Archivos a MODIFICAR
- `bootstrap/app.php` — reescritura a la forma clásica L10.
- `artisan` — L11 usa `->handleCommand(new ArgvInput)`; L10 instancia el Console Kernel
  (`$kernel->handle(...)` + `terminate`).
- `public/index.php` — L11 usa `->handleRequest(...)`; L10 instancia el HTTP Kernel
  (`$kernel->handle(Request::capture())->send()` + `terminate`).
- `tests/TestCase.php` + `tests/CreatesApplication.php` — L10 requiere el trait
  `CreatesApplication` (que implementa `createApplication()`); L11 lo eliminó.
- `config/database.php` — la clave `'migrations'` en L11 es un array
  (`['table' => 'migrations', 'update_date_on_publish' => true]`); L10 espera el **string**
  `'migrations'` (nombre de tabla). Con el array, `hasTable()` recibe un array y revienta
  en `PostgresBuilder::parseSchemaAndTable`.
- `routes/console.php` — quitar `Schedule::` (se mueve al Console Kernel); dejar `inspire`.
- `config/app.php` — añadir `'providers'` (lista L10) y `'aliases'`.
- `composer.json` — ver §3.

### Archivos a ELIMINAR
- `bootstrap/providers.php` — exclusivo de L11.

### Lo que NO cambia
- Controllers, Services, Repositories, Models, DTOs (salvo `readonly class`), migraciones,
  seeders, Form Requests, Resources, rutas (`routes/api.php`), config de dominio
  (`config/qr.php`, `config/jwt.php`, `config/auth.php`), tests.
- Los 6 middleware **custom** (`EnsureApiClient`, `EnsureScope`, `VerifyWebhookSignature`,
  `EnsureRole`, `AuditAdminActions`, `EnsureUserToken`) se conservan tal cual; solo cambia
  **dónde se registran sus aliases** (Kernel en vez de bootstrap).

---

## 3. Dependencias (composer.json)

| Paquete | Antes (L11) | Después (L10) |
|---|---|---|
| `php` | `^8.2` | `^8.1` |
| `laravel/framework` | `^11.31` | `^10.0` |
| `laravel/tinker` | `^2.9` | `^2.8` |
| `endroid/qr-code` | `^5.0` | `^5.0` (framework-agnóstico, PHP ≥8.1) |
| `php-open-source-saver/jwt-auth` | `^2.8` | `^2.1` (deja a composer elegir versión L10-compat) |
| `phpunit/phpunit` (dev) | `^11.0` | `^10.1` |
| `nunomaduro/collision` (dev) | `^8.1` | `^7.0` |
| `laravel/pail` (dev) | `^1.1` | **eliminar** (solo L11) |
| `spatie/laravel-ignition` (dev) | (incluido en core) | `^2.0` (añadir; L10 lo trae en require-dev) |
| `laravel/sail`, `laravel/pint`, `mockery/mockery`, `fakerphp/faker` | — | sin cambio relevante |
| `config.platform.php` | `8.2.31` | `8.1.34` |

`config.allow-plugins`: quitar `pestphp/pest-plugin` (no se usa) es opcional; se mantiene
para no introducir ruido.

---

## 4. Adaptación de código a PHP 8.1

`readonly class` es exclusivo de PHP 8.2. Convertir los 6 a **propiedades `readonly`**
(soportadas desde 8.1) sin cambiar semántica:

```
final readonly class X {            ->   final class X {
    public function __construct(            public function __construct(
        public int $a,                          public readonly int $a,
        public ?string $b = null,               public readonly ?string $b = null,
    ) {}                                    ) {}
```

Archivos afectados:
- `app/DTOs/OrderData.php`
- `app/DTOs/ReceiptData.php`
- `app/DTOs/ValidateTicketData.php`
- `app/DTOs/ValidationResult.php`
- `app/DTOs/WebhookPaymentData.php`
- `app/Support/Qr/QrVerifyResult.php`

No hay otra sintaxis 8.2 en el código (verificado: sin DNF types, sin tipos
`true`/`false`/`null` standalone, sin enums problemáticos).

---

## 5. Entorno y CI

- `docker/Dockerfile`: `FROM php:8.2-fpm` → `php:8.1.34-fpm` (fallback `php:8.1-fpm` si el
  tag exacto no existe); actualizar comentario de cabecera.
- `docker/docker-compose.yml`: actualizar comentario (Laravel 11 → 10) si lo menciona.
- `.github/workflows/ci.yml`: `php-version: '8.2'` → `'8.1'`; nombre del job a "PHP 8.1".

---

## 6. Plan de verificación

```bash
cd docker && docker compose up -d --build           # reconstruye imagen 8.1
docker exec qr_app composer update --no-interaction  # resuelve deps L10
docker exec qr_app php artisan key:generate
docker exec qr_app php artisan jwt:secret --force
docker exec qr_db psql -U qr_user -d qr_ticketing -c "CREATE DATABASE qr_ticketing_test;"
docker exec qr_app php artisan migrate --seed
docker exec qr_app php artisan test                  # criterio de aceptación #2
```

---

## 6.bis Seguridad: advisories y versiones EOL

Laravel 10 (soporte de seguridad finalizado feb-2025) y PHP 8.1 (EOL dic-2025) están **fuera
de su ventana de parches de seguridad**. `composer audit` reporta 3 advisories de
`laravel/framework` **sin parche disponible en la línea 10.x** (corregidos solo en 12.60+/13.x):

| Advisory | Sev. | Exposición en esta app | Estado |
|---|---|---|---|
| Temporary Signed URL Path Confusion | media | **Ninguna** (la app no usa signed URLs) | aceptado |
| CRLF injection en la regla `email` (CVE-2026-48019) | alta | Baja (se valida `email`; `MAIL_MAILER=log`, sin inyección en cabeceras) | **mitigado** |

**Mitigación aplicada (sin cambiar versión):** la regla `email` se endureció a **`email:strict`**
(`NoRFCWarningsValidation`, rechaza CRLF/caracteres de control) en los 4 Form Requests:
`LoginRequest`, `StoreOrderRequest` (`customer.email`), `Admin/AdminUserRequest`,
`Admin/TourRequest` (`owner_email`). 82 tests siguen verdes.

**Operativo (obligatorio al correr versiones EOL):** TLS/WAF delante (ya en `docs/deployment.md`),
`APP_DEBUG=false`, y revisar `composer audit` periódicamente. `doctrine/annotations` aparece como
*abandoned*: es dependencia transitiva de l5-swagger 8.x, no es una vulnerabilidad.

## 7. Riesgos y mitigaciones

- **Resolución de dependencias:** relajar constraints (usar `^` con límite inferior bajo en
  jwt-auth) y dejar a `composer update` elegir. Si jwt-auth/endroid no resuelven, fijar la
  versión L10-compat conocida.
- **`config/app.php` sin `providers`:** en L10 los providers de la app se cargan desde ahí; si
  falta, `RepositoryServiceProvider` no se registra y la inyección de repos rompe. Crítico.
- **Prioridad de middleware:** `EnsureUserToken` debe seguir corriendo antes del guard
  `Authenticate` (replicar `$middlewarePriority`). Cubierto por `AdminAuthTest`.
- **Tag de imagen 8.1.34:** si `php:8.1.34-fpm` no existe en el registry, usar `php:8.1-fpm`
  (última 8.1.x) y mantener `platform.php = 8.1.34` solo si el runtime es ≥; si no, alinear.
