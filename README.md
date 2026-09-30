# Wonderpool Garden Resort — Booking System

Online booking system for Wonderpool Garden Resort: a public website where guests browse rooms, cottages and amenities, check availability and send booking requests, plus an admin panel for managing content, bookings, reports and notifications.

- **Stack:** Laravel 12 · Blade · MySQL · Tailwind CSS v4 · Alpine.js · blade-heroicons · Poppins
- **Project docs:** [`docs/`](docs/) · scope in `PLAN.md` · progress in [`MILESTONES.md`](MILESTONES.md) · changes in [`CHANGELOG.md`](CHANGELOG.md)

## Requirements

- PHP 8.2+ with `pdo_mysql` (and `pdo_sqlite` for tests)
- Composer 2
- Node.js 20+ and npm
- MySQL 8 / MariaDB 10.4+ (XAMPP works)

## Installation

```bash
git clone https://github.com/rhondelp/wonderpool.git
cd wonderpool
composer install
npm install
cp .env.example .env
php artisan key:generate
# create an empty database (default name: wonderpool), then:
php artisan migrate
npm run build
```

## Environment guide

Edit `.env` (never commit it — only `.env.example` is tracked, with placeholders).

| Variable | Purpose |
|---|---|
| `APP_NAME` | Displayed site name ("Wonderpool Garden Resort") |
| `APP_ENV` | `local` in development (enables `/design-preview`), `production` live |
| `APP_DEBUG` | `true` locally, **always `false` in production** |
| `APP_URL` | Base URL, e.g. `http://localhost:8000` |
| `APP_TIMEZONE` | Application timezone (default `Asia/Manila`) |
| `DB_*` | MySQL connection (host, port, database, username, password) |
| `MAIL_*` | Outgoing mail; `log` mailer is fine for development |
| `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` | `database` by default (run migrations first) |

## Running in development

```bash
composer run dev        # serve + queue + logs + vite together
# or separately:
php artisan serve
npm run dev
```

Open http://localhost:8000. While `APP_ENV=local`, a component/theme preview is available at `/design-preview` (temporary; removed in M9).

## Testing

```bash
vendor/bin/pest                 # or: php artisan test
```

Tests use an in-memory SQLite database (see `phpunit.xml`), so no MySQL is needed.

## Code style & quality

```bash
vendor/bin/pint                 # format (PSR-12 preset, pint.json)
vendor/bin/pint --test          # check only
vendor/bin/phpstan analyse      # Larastan level 6 (phpstan.neon)
```

CI (`.github/workflows/ci.yml`) runs Pint, Larastan, the asset build and Pest on every push to `main` and every pull request.

Conventions (thin controllers, services, Form Requests, enums, Conventional Commits, branch-per-phase) are listed in `CLAUDE.md` and `HISTORY.md`.
