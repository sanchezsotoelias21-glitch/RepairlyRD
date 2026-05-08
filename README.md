# RepairlyRD

## Deploy en Railway

Este repo está preparado para Railway con `Dockerfile`.

1. En Railway, crea un **New Project** → **Deploy from GitHub Repo**.
2. Railway detectará el `Dockerfile` y hará el build automáticamente.
3. Abre el dominio en **Service → Domains** (Generate Domain si no aparece).

### Variables
- `PORT`: Railway la inyecta automáticamente (no hace falta configurarla).

## Correr local (Docker)

```bash
docker build -t repairlyrd .
docker run --rm -p 8080:8080 -e PORT=8080 repairlyrd
```

Luego abre `http://localhost:8080`.

