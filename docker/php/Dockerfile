# ============================================================
# Fact2PDF - PHP 8.3 + FPM
# ============================================================
FROM php:8.3-fpm-alpine

LABEL maintainer="Fact2PDF Team"
LABEL description="PHP 8.3 FPM pour Fact2PDF"

ARG UID=1000
ARG GID=1000

# ---- Dépendances système + extensions PHP ----
RUN apk add --no-cache \
        git curl unzip bash \
        # GD (images / TCPDF)
        libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev \
        # Zip, mbstring, intl
        libzip-dev oniguruma-dev icu-dev \
        # Intervention/Image (vips backend, optionnel)
        libgomp \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install \
        pdo pdo_mysql \
        gd zip mbstring intl opcache \
        exif \
    && rm -rf /var/cache/apk/*

# ---- Composer 2 ----
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# ---- Configuration PHP ----
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# ---- Utilisateur non-root ----
RUN addgroup -g ${GID} appgroup && \
    adduser -u ${UID} -G appgroup -s /bin/bash -D appuser

WORKDIR /var/www/html

# ---- Installer les dépendances Composer (layer séparé pour cache Docker) ----
# Copier uniquement les fichiers composer d'abord (optimise le cache)
COPY --chown=appuser:appgroup composer.json composer.lock* ./

RUN composer install \
        --no-interaction \
        --no-scripts \
        --no-dev \
        --optimize-autoloader \
        --prefer-dist \
    && composer clear-cache

# ---- Copier le code source ----
COPY --chown=appuser:appgroup . .

# ---- Créer les dossiers de stockage ----
RUN mkdir -p storage/logs storage/uploads/logos storage/cache \
    && chmod -R 775 storage

USER appuser

EXPOSE 9000

CMD ["php-fpm"]
