# Immobilier Laravel Backend

Full-stack Laravel backend for the Swiss real-estate platform, inspired by [immobilier.ch](https://www.immobilier.ch/en/).
Serves the Inertia.js SSR frontend alongside the REST API.

## Stack

| Area | Technology |
|------|-----------|
| Framework | Laravel 12 |
| Language | PHP 8.2 |
| Frontend | Inertia.js + Vue 3 + Tailwind |
| Database | PostgreSQL 16 |
| Cache | Redis |
| Search | Meilisearch |
| Auth | Sanctum + Fortify |
| Queue | Laravel Horizon (Redis) |
| Email | Mailpit (dev) |
| Images | Cloudinary |
| Translation | DeepL + LibreTranslate |
| Testing | Pest + PHPUnit |

## Quick Start

```bash
# Start everything
docker compose up -d --build

# Run migrations + seed
docker compose exec app php artisan migrate --seed

# Stop
docker compose down
```

## Services

| Service | Port | URL |
|---------|------|-----|
| App | 8000 | http://localhost:8000 |
| PostgreSQL | 5432 | — |
| Redis | 6379 | — |
| Meilisearch | 7700 | http://localhost:7700 |
| Mailpit | 8025 | http://localhost:8025 |
| Horizon | — | http://localhost:8000/horizon |

## Environment

Copy `.env.example` to `.env`:
```bash
cp .env.example .env
php artisan key:generate
```

## License

MIT
