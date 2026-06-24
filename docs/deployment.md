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
5. Generacion de `QR_SECRET`
```
php -r "echo bin2hex(random_bytes(32));"
```
Una vez que tengas el valor (ej. a3f8c2...), lo pegas en backend/.env.
```
QR_SECRET=a3f8c2d1...el_valor_generado...
```
6. Dominio virtual apuntando a `backend/public`.
   Laragon genera el virtual host automáticamente en:
   ```
   C:\laragon\etc\apache2\sites-enabled\auto.JaviTickets.test.conf
   ```
   El `DocumentRoot` generado apunta a la raíz del repo en lugar de `backend/public`. Corregirlo:
   ```apache
   # Cambiar las dos líneas con la ruta:
   DocumentRoot "C:/laragon/www/JaviTickets/backend/public"
   <Directory "C:/laragon/www/JaviTickets/backend/public">
   ```
   Después, actualizar `APP_URL` en `backend/.env`:
   ```env
   APP_URL=http://javitickets.test
   ```
   Reiniciar Apache (Laragon → Apache → Reload).

7. Programar el scheduler (Tarea de Windows cada minuto):
   `php C:\ruta\backend\artisan schedule:run`

### Swagger (documentación interactiva)

La UI de Swagger es estática (`public/docs/index.html` + `public/docs/openapi.json`).
No requiere configuración extra **siempre que el `DocumentRoot` sea correcto** (paso 5).

Una vez hecho el paso 5:

- **API health:** `http://javitickets.test/api/v1/up` → `{"status":"ok",...}`
- **Swagger UI:** `http://javitickets.test/docs/index.html`

Para que el botón **"Try it out"** apunte directamente al dominio Laragon, agregar el servidor al array `servers` en `backend/public/docs/openapi.json`:
```json
"servers": [
  { "url": "http://javitickets.test/api/v1", "description": "Local (Laragon)" },
  { "url": "http://127.0.0.1:8000/api/v1",  "description": "Local (artisan serve)" },
  { "url": "/api/v1",                        "description": "Relativo al host actual" }
]
```

> **Docker:** el Swagger funciona sin configuración adicional en `http://localhost:8090/docs/index.html` porque nginx ya sirve `backend/public` como raíz.

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
