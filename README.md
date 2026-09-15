# CivicPulse

CivicPulse is a civic issue reporting and urban operations platform. Citizens report local
problems (potholes, waterlogging, garbage, broken streetlights, and similar issues); operators
triage, assign, and resolve them. See `CLAUDE.md` for full product and architecture context.

This milestone is backend-only: a Laravel API booting locally against PostgreSQL/PostGIS with a
health endpoint. No authentication, domain models, or frontend yet.

## Stack

- PHP 8.x / Laravel, run via [Laravel Sail](https://laravel.com/docs/sail)
- PostgreSQL 17 + PostGIS 3.5 (`postgis/postgis:17-3.5`)
- PHPUnit for tests

## Local setup

1. Install PHP dependencies (requires PHP and Composer locally; `vendor/` is not committed):
   ```bash
   composer install
   ```
2. Copy the environment file (skip if `.env` already exists):
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
3. Start the containers:
   ```bash
   docker compose up -d
   ```
   This brings up the Laravel app container and a PostgreSQL/PostGIS container. The PostGIS
   extension is enabled automatically on both the application database (`civicpulse`) and the
   `testing` database on first boot (see `docker/pgsql/initdb/10-init-postgis.sql`).
4. Run migrations:
   ```bash
   ./vendor/bin/sail artisan migrate
   ```
5. Verify the health endpoint:
   ```bash
   curl http://localhost:8000/api/health
   ```
   Expect a `200` response with `{"status":"ok","database":"ok",...}`.

## Running tests

```bash
./vendor/bin/sail artisan test
```

Tests run against a real PostgreSQL/PostGIS `testing` database (not SQLite), per this project's
testing conventions.
