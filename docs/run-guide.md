# Guía completa — Levantar todo el stack (backend + panel)

Cómo poner en marcha **el sistema entero** desde cero y comprobar que funciona: la API
(Laravel 10 / PHP 8.1.34 / PostgreSQL 16) y el **panel admin** (Vue 3 + Vuetify + Vite).

> Resumen rápido: levantas el **backend** (Docker), generas claves + siembras datos, levantas
> el **frontend** (`npm run dev`) y entras en `http://localhost:5173` con `admin@qrtickets.test` / `password`.

---

## 0. Requisitos

| Para | Necesitas |
|---|---|
| Backend (Docker) | **Docker Desktop**. No hace falta PHP/Composer/PostgreSQL en el host. |
| Frontend | **Node.js 20+** y npm. |
| Backend (alternativa sin Docker) | **Laragon** con PHP 8.1.34 + PostgreSQL (ver [`deployment.md`](deployment.md)). |

---

## 1. Backend (API) con Docker

```bash
cd docker
docker compose up -d --build        # app (php-fpm) + web (nginx :8090) + db (postgres :55432)

# Solo la primera vez:
docker exec qr_app composer install
docker exec qr_app php artisan key:generate
docker exec qr_app php artisan jwt:secret
docker exec qr_app php artisan migrate --seed
```

Comprobación:
```bash
curl http://localhost:8090/api/v1/up      # -> {"status":"ok",...}
```

- **API:** `http://localhost:8090/api/v1`
- **PostgreSQL (host):** `localhost:55432` · db `qr_ticketing` · user `qr_user` / `qr_secret`

### Secreto del QR (obligatorio antes de emitir tickets reales)
```bash
docker exec qr_app php -r "echo bin2hex(random_bytes(32));"
# pega el valor en backend/.env -> QR_SECRET=...   y luego:
docker exec qr_app php artisan config:clear
```

---

## 2. Frontend (panel admin)

```bash
cd frontend
npm install
cp .env.example .env        # VITE_API_URL ya apunta a http://localhost:8090/api/v1 (Docker)
npm run dev                 # -> http://localhost:5173
```

Abre **http://localhost:5173** e inicia sesión.

> Si el puerto 5173 está ocupado, Vite usa el siguiente (5174…). Mira la línea `Local:` en la consola.

---

## 3. Entrar

| Rol | Email | Contraseña | Aterriza en |
|---|---|---|---|
| **Admin** | `admin@qrtickets.test` | `password` | `/dashboard` (panel completo) |
| **Gate** | `gate@qrtickets.test` | `password` | `/validate` (validación en puerta) |

Cliente API de máquina (para la tienda/integradores, **no** para el panel):
`client_id=wp-store-demo`, `client_secret=dev-client-secret`, `webhook_secret=dev-webhook-secret`.

> ⚠️ Todas son credenciales **de desarrollo**. Cambiar antes de producción.

---

## 4. Probar que todo sirve

### Backend
```bash
docker exec qr_db psql -U qr_user -d qr_ticketing -c "CREATE DATABASE qr_ticketing_test;"  # una vez
docker exec qr_app php artisan test            # 82 tests
```

### Frontend
```bash
cd frontend
npm run lint            # ESLint
npm run typecheck       # vue-tsc
npm run test            # unitarios (Vitest)
npm run test:coverage   # unitarios + cobertura (~91% en composables/stores)
npm run test:e2e        # E2E Playwright (requiere backend arriba); navegador headless
npm run test:e2e:headed # E2E con navegador VISIBLE en pantalla
npx playwright show-report   # interfaz visual de la última corrida E2E
```

> La primera vez para E2E: `npx playwright install chromium`.

---

## 5. Recorrido funcional sugerido (humo manual)

1. **Login** como admin → Dashboard (elige un evento y mira las métricas).
2. **Tours** → "Nuevo tour" (nombre, slug, artista) → guardar → editar → eliminar → "Mostrar eliminados" → restaurar.
3. **Eventos** → crear (tour, slug, ciudad, capacidad, tipo general/numerado); si es numerado, entra a **Asientos** y usa "Generar rango".
4. **Tipos de ticket** → crear (tour, slug, precio, moneda).
5. **Órdenes** → abre una orden pendiente → "Verificar pago" → aparecen los tickets emitidos; descarga el desprendible.
6. **Tickets** → "Exportar CSV"; anula/reemite un ticket.
7. **API Clients** → crea uno → copia el `client_secret` del modal (solo se muestra una vez) → "Rotar secret".
8. **Validación en puerta** (`/validate`): elige evento, pega un `qr_token` y pulsa "Validar".

---

## 6. Problemas comunes

| Síntoma | Causa | Solución |
|---|---|---|
| `docker compose up` falla al enlazar el puerto **55432** | En Windows ese puerto puede caer en un rango reservado por WinNAT | Liberar el rango (`net stop winnat` / `net start winnat` como admin) o remapear el puerto del servicio `db` en `docker/docker-compose.yml` |
| El **login no responde / 429** tras correr muchas pruebas | Rate-limit del login (`throttle:10,1`) | Esperar ~1 min; vuelve a funcionar |
| El panel no carga datos | Backend caído o `VITE_API_URL` mal | Verifica `curl http://localhost:8090/api/v1/up` y el `.env` del frontend |
| El primer request es lento (Windows) | Arranque en frío + bind-mount | Es normal; los siguientes son rápidos |
| `php artisan test` no encuentra la DB de test | Falta `qr_ticketing_test` | Crearla con el comando de la sección 4 |

---

## 7. Producción

No usar las credenciales DEV. Seguir el **checklist de producción** en
[`deployment.md`](deployment.md): TLS, `APP_DEBUG=false`, `QR_SECRET` real, CORS restringido,
`config:cache`/`route:cache`, backups y scheduler (`tickets:expire`). El frontend se despliega
como archivos estáticos (`npm run build` → `frontend/dist/`) apuntando `VITE_API_URL` a la API real.
