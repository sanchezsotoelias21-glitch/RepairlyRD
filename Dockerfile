FROM php:8.2-cli

WORKDIR /app
COPY . /app

# Railway should set $PORT at runtime. Default to 8000 for Railway/local if not set.
ENV PORT=8000
EXPOSE 8000
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT} -t /app"]
