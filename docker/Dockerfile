# PHP 8.1.34 FPM para Laravel 10 + PostgreSQL + generación de QR (gd).
# Imagen SOLO de entorno: el código vive en el host y se monta como volumen.
FROM php:8.1.34-fpm

# Dependencias de sistema
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libpq-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

# Extensiones PHP: pgsql (Postgres), gd (QR a PNG), zip, bcmath
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo pdo_pgsql gd zip bcmath

# Composer (desde imagen oficial)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Nota: en dev local con bind-mount de Windows corremos como root para evitar
# problemas de permisos de escritura (storage/, bootstrap/cache, composer.json).
# En despliegue real (Laragon u otro) se ejecuta bajo el usuario del sistema.

EXPOSE 9000
CMD ["php-fpm"]
