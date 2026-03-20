ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-cli

RUN pecl install opentelemetry && \
    docker-php-ext-enable opentelemetry

RUN apt-get update && apt-get install git unzip -y

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
ENV PATH="$PATH:/usr/local/bin"

# Copy PHP integration since we are using a path repository
COPY composer.json /package/composer.json
COPY src/ /package/src/
COPY _autoload.php /package/_autoload.php
COPY bin/ /package/bin/
COPY config-stubs/ /package/config-stubs/

WORKDIR /app

# Copy composer files first to cache dependencies
COPY tests/Integration/symfony/composer.json .
COPY tests/Integration/symfony/symfony.lock .

# Install dependencies
RUN composer update --no-interaction --prefer-dist --no-scripts

# Copy the rest of the Symfony app
COPY tests/Integration/symfony/ .

# Clear cache now that the full app is present
RUN bin/console cache:clear

# Allow _autoload.php to initialize AppSignal in CLI mode
# This is here because Composer autoload files run
# before PHPUnit parses XML config
ENV _APPSIGNAL_TEST=true

CMD ["vendor/bin/phpunit"]
