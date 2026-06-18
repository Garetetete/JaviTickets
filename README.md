# QR Ticketing API

API REST (Laravel 11 + PostgreSQL) que es la **autoridad única de tickets/QR** para tours/eventos: genera el QR firmado, gestiona el pago (manual o por webhook), controla el aforo y valida en puerta (anti-doble-entrada). Otros sistemas (tienda WordPress, escáner) la **consumen**.

- Contexto y arquitectura completa: [`CLAUDE.md`](CLAUDE.md)
- Especificación SDD: [`SDD-QrTicketing-Spec.md`](SDD-QrTicketing-Spec.md) y [`docs/specs/`](docs/specs/)
- **Despliegue (Docker / Laragon / producción):** [`docs/deployment.md`](docs/deployment.md)
- **Guía de integración (para quien consume la API):** [`docs/integration-guide.md`](docs/integration-guide.md)
- **Colección Postman:** [`docs/postman_collection.json`](docs/postman_collection.json)

---

## Arranque rápido (Docker)

Requisitos: Docker. No necesitas PHP/Composer/PostgreSQL en el host.

```bash
cd docker
docker compose up -d --build

# Primera vez: dependencias, claves y base de datos
docker exec qr_app composer install
docker exec qr_app php artisan key:generate
docker exec qr_app php artisan jwt:secret
docker exec qr_app php artisan migrate --seed
```

- API: <http://localhost:8090/api/v1>  ·  Health: `GET /api/v1/up`
- PostgreSQL (host): `localhost:55432` · db `qr_ticketing` · user `qr_user` / `qr_secret`

> En Windows el **primer** request puede tardar (arranque en frío + bind-mount); los siguientes son rápidos.

### Generar el secreto del QR (obligatorio antes de emitir tickets reales)
```bash
docker exec qr_app php -r "echo bin2hex(random_bytes(32));"
# pega el valor en .env -> QR_SECRET=...  y luego:
docker exec qr_app php artisan config:clear
```

## Tests
```bash
docker exec qr_db psql -U qr_user -d qr_ticketing -c "CREATE DATABASE qr_ticketing_test;"  # una vez
docker exec qr_app php artisan test
```

## Laragon (sin Docker)
El mismo `backend/` corre bajo Laragon (PHP 8.2 + PostgreSQL) configurando `.env` (`DB_HOST=127.0.0.1`). Ver [`docs/deployment.md`](docs/deployment.md).

## Credenciales DEV sembradas (cambiar en producción)
- Admin: `admin@qrtickets.test` / `password`
- Gate: `gate@qrtickets.test` / `password`
- API client: `wp-store-demo` / `dev-client-secret` (webhook secret `dev-webhook-secret`)
