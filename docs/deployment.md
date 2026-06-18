# Despliegue

El `backend/` es un Laravel 11 estándar (PHP 8.2 + PostgreSQL 16). Se levanta de dos formas equivalentes: **Docker** (dev local) y **Laragon** (despliegue). El código no depende de Docker.

---

## 1. Docker (desarrollo local)

```bash
cd docker
docker compose up -d --build      # app (php-fpm) + web (nginx :8090) + db (postgres :55432)

docker exec qr_app composer install
docker exec qr_app php artisan key:generate
docker exec qr_app php artisan jwt:secret
docker exec qr_app php artisan migrate --seed
```

Servicios (`docker/docker-compose.yml`, proyecto `qrticketing`):
| Servicio | Contenedor | Puerto host |
|---|---|---|
| nginx | `qr_web` | `8090 → 80` |
| php-fpm | `qr_app` | — |
| postgres 16 | `qr_db` | `55432 → 5432` |

El código (`../backend`) se monta como volumen: editas en el host, los contenedores solo aportan runtime.

**Comandos útiles**
```bash
docker exec qr_app php artisan migrate:fresh --seed
docker exec qr_app php artisan tickets:expire
docker exec qr_app php artisan test
docker compose -f docker/docker-compose.yml logs -f app
```

---

## 2. Laragon (Windows, sin Docker)

1. Instalar PHP 8.2+ y PostgreSQL en Laragon (o apuntar a uno existente).
2. Crear la base `qr_ticketing`.
3. En `backend/.env`:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=qr_ticketing
   DB_USERNAME=postgres
   DB_PASSWORD=...
   ```
4. ```bash
   composer install
   php artisan key:generate
   php artisan jwt:secret
   php artisan migrate --seed
   ```
5. Dominio virtual (ej. `api.qrtickets.test`) apuntando a `backend/public`.
6. Programar el scheduler (Tarea de Windows cada minuto):
   `php C:\ruta\backend\artisan schedule:run`

---

## 3. Checklist de PRODUCCIÓN (obligatorio)

- [ ] **HTTPS/TLS** delante de la API (JWT, secrets y webhooks NUNCA en claro).
- [ ] `APP_ENV=production`, `APP_DEBUG=false`.
- [ ] **`QR_SECRET`** real y único (`php -r "echo bin2hex(random_bytes(32));"`), distinto por entorno, fuera de git. No cambiarlo tras emitir tickets (rotar con `QR_KEY_VERSION` + secreto nuevo manteniendo el viejo en `config/qr.php`).
- [ ] `APP_KEY` y `JWT_SECRET` generados en el servidor.
- [ ] Credenciales reales (no las sembradas `password` / `dev-*`). Rotar `client_secret` de cada api-client.
- [ ] **CORS** restringido a los dominios de la tienda y el escáner (`config/cors.php`), no `*`.
- [ ] `config/filesystems.php`: los desprendibles en disco privado (no público). Considerar S3 con backups.
- [ ] Backups de PostgreSQL + retención.
- [ ] `php artisan config:cache route:cache` en deploy.
- [ ] Scheduler activo (cron `* * * * * php artisan schedule:run`) para `tickets:expire`.
- [ ] Rate limiting acorde al tráfico (revisar `throttle` en `routes/api.php`).
- [ ] Monitoreo/logs centralizados; alertar sobre firmas de webhook inválidas y logins fallidos.

---

## 4. Variables de entorno clave

| Variable | Descripción |
|---|---|
| `APP_KEY` | clave de la app (`key:generate`) |
| `JWT_SECRET` | clave JWT (`jwt:secret`) |
| `QR_SECRET` | **clave HMAC del QR** (generar; secreto) |
| `QR_KEY_VERSION` | versión de clave QR activa (rotación) |
| `QR_SCAN_REBOUNCE_SECONDS` | ventana anti-rebote en validación (def. 5) |
| `DB_*` | conexión PostgreSQL |

Plantilla completa en [`backend/.env.example`](../backend/.env.example).
