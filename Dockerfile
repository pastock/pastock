FROM ghcr.io/mileschou/composer:8.0 AS builder

WORKDIR /app
COPY composer.* ./

RUN composer install --no-suggest --no-progress

COPY . .
RUN set -xe && \
        php pastock app:build && \
        php /app/builds/pastock

FROM php:8.0-alpine

COPY --from=builder /app/builds/pastock /usr/local/bin/pastock

ENTRYPOINT ["/usr/local/bin/pastock"]
CMD ["--help"]
