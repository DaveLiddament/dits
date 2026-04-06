ARG PHP_VERSION=8.5
FROM php:${PHP_VERSION}-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-install \
    bcmath \
    intl \
    zip \
    && rm -rf /var/lib/apt/lists/*

ARG XDEBUG_ENABLED=1
RUN if [ "$XDEBUG_ENABLED" = "1" ]; then \
        pecl install xdebug && docker-php-ext-enable xdebug; \
    fi

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV XDEBUG_MODE=debug

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

CMD ["tail", "-f", "/dev/null"]
