ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-cli

ARG INSTALL_OTEL_EXTENSION=true

RUN if [ "$INSTALL_OTEL_EXTENSION" = "true" ]; then \
		pecl install opentelemetry && \
		docker-php-ext-enable opentelemetry; \
	fi

RUN apt-get update && apt-get install git unzip -y

RUN   curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
ENV PATH="$PATH:/usr/local/bin"

WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --prefer-dist
COPY . .
