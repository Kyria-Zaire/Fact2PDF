# ============================================================
# Fact2PDF - PHP 8.3 + FPM
# ============================================================
FROM php:8.3-fpm-alpine

# Métadonnées
LABEL maintainer="Fact2PDF Team"
LABEL description="PHP 8.3 FPM pour Fact2PDF"

# Variables de build
ARG UID=1000
ARG GID=1000

# Dépendances système + extensions PHP
RUN apk add --no-cache \
        # Outils de base
        git \
        curl \
        unzip \
        bash \
        # Dépendances pour extensions PHP
        libpng-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        freetype-dev \
        libzip-dev \
        oniguruma-dev \
        icu-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        gd \
        zip \
        mbstring \
        intl \
        opcache \
    && rm -rf /var/cache/apk/*

# Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Configuration PHP pour production/dev
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Créer un utilisateur non-root
RUN addgroup -g ${GID} appgroup && \
    adduser -u ${UID} -G appgroup -s /bin/bash -D appuser

# Répertoire de travail
WORKDIR /var/www/html

# Permissions
RUN chown -R appuser:appgroup /var/www/html

USER appuser

EXPOSE 9000

CMD ["php-fpm"]
