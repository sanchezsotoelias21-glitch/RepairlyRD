FROM php:8.2-cli

RUN apt-get update \
  && apt-get install -y --no-install-recommends libicu-dev \
  && docker-php-ext-install intl mysqli \
  && apt-get purge -y --auto-remove libicu-dev \
  && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . /app

# Railway should set $PORT at runtime. Default to 8000 for Railway/local if not set.
ENV PORT=8000
EXPOSE 8000
CMD ["sh", "-c", "php -m | grep -qi '^intl$' || (echo 'FATAL: PHP extension intl is not enabled' >&2; php -m >&2; exit 1); php -S 0.0.0.0:${PORT} -t /app"]
