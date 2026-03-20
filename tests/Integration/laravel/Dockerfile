ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-cli

RUN pecl install opentelemetry && \
    docker-php-ext-enable opentelemetry

RUN apt-get update && apt-get install git unzip -y

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
ENV PATH="$PATH:/usr/local/bin"

# Copy PHP integration so it can be used as a path repository
COPY composer.json /package/composer.json
COPY src/ /package/src/
COPY _autoload.php /package/_autoload.php
COPY bin/ /package/bin/
COPY config-stubs/ /package/config-stubs/

WORKDIR /app

# Copy composer files first to cache dependency installation
COPY tests/Integration/laravel/composer.json .

# Install dependencies (no-scripts: artisan isn't available yet)
RUN composer update --no-interaction --prefer-dist --no-scripts

# Copy the rest of the Laravel app
COPY tests/Integration/laravel/ .

# Run post-install scripts now that the full app is present
RUN composer run-script post-update-cmd --no-interaction

# Allow _autoload.php to initialize AppSignal in CLI mode.
# This must be a process-level env var (not phpunit.xml <env>) because
# Composer autoload files run before PHPUnit processes its XML config.
ENV _APPSIGNAL_TEST=true

CMD ["vendor/bin/phpunit"]
