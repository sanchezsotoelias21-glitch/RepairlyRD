FROM php:8.2-cli

WORKDIR /app
COPY . /app

# Railway sets $PORT at runtime. Default to 8080 for local runs.
EXPOSE 8080
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t /app"]

