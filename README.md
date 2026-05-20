# RepairlyRD

## Deploy en Vercel

1. Sube el repo a GitHub.
2. En Vercel: **New Project** → importa el repo.
3. En **Settings → Environment Variables** configura:
   - `DB_HOST`
   - `DB_USER`
   - `DB_PASS`
   - `DB_NAME`
   - `DB_PORT` (opcional, por defecto `3306`)
   - `SESSION_HANDLER` = `mysql` (recomendado en Vercel para evitar cierres de sesión/CSRF)
4. Deploy y abre el dominio.

Nota: Vercel no incluye MySQL. Necesitas una base de datos externa (p. ej. PlanetScale, Aiven, DigitalOcean, etc.).

## Deploy en Railway

Este repo también se puede desplegar en Railway con `Dockerfile`.

1. En Railway, crea un **New Project** → **Deploy from GitHub Repo**.
2. Railway detectará el `Dockerfile` y hará el build automáticamente.
3. Abre el dominio en **Service → Domains** (Generate Domain si no aparece).

### Variables (Railway)

- `PORT`: Railway la inyecta automáticamente (no hace falta configurarla).

## Correr local (Docker)

```bash
docker build -t repairlyrd .
docker run --rm -p 8000:8000 -e PORT=8000 repairlyrd
```

Luego abre `http://localhost:8000`.
