# Wonderpool Garden Resort — Booking System

Online booking system for Wonderpool Garden Resort: a public website where guests browse rooms, cottages and amenities, check availability and send booking requests, plus an admin panel for managing content, bookings, reports and notifications.

- **Stack:** Laravel 12 · Blade · PostgreSQL · Tailwind CSS v4 · Alpine.js · blade-heroicons · Poppins
- **Project docs:** [`docs/`](docs/) · scope in `PLAN.md` · progress in [`MILESTONES.md`](MILESTONES.md) · changes in [`CHANGELOG.md`](CHANGELOG.md)

## Requirements

- PHP 8.2+ with `pdo_pgsql` and `pgsql` enabled (in `php.ini`: `extension=pdo_pgsql`, `extension=pgsql`)
- Composer 2
- Node.js 20+ and npm
- PostgreSQL 15+

## Installation

Create the database role and databases once (in `psql` as the `postgres` superuser, or pgAdmin), choosing your own password:

```sql
CREATE ROLE wonderpool_user LOGIN PASSWORD '<choose a password>';
CREATE DATABASE wonderpool OWNER wonderpool_user;
CREATE DATABASE wonderpool_test OWNER wonderpool_user;
```

Then put that password in `DB_PASSWORD` in your local `.env` (never in a committed file).

```bash
git clone https://github.com/rhondelp/wonderpool.git
cd wonderpool
composer install
npm install
cp .env.example .env
php artisan key:generate
# set DB_PASSWORD (and OWNER_EMAIL/OWNER_PASSWORD) in .env, then:
php artisan migrate --seed
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
| `DB_*` | PostgreSQL connection: `DB_CONNECTION=pgsql`, `DB_HOST=127.0.0.1`, `DB_PORT=5432`, `DB_DATABASE=wonderpool`, `DB_USERNAME=wonderpool_user`, `DB_PASSWORD` |
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

Tests run on the PostgreSQL database `wonderpool_test` (see `phpunit.xml`) using the same host/user/password as `.env`; it is wiped and re-migrated by the test run.

## Code style & quality

```bash
vendor/bin/pint                 # format (PSR-12 preset, pint.json)
vendor/bin/pint --test          # check only
vendor/bin/phpstan analyse      # Larastan level 6 (phpstan.neon)
```

CI (`.github/workflows/ci.yml`) runs Pint, Larastan, the asset build and Pest on every push to `main` and every pull request.

Conventions (thin controllers, services, Form Requests, enums, Conventional Commits, branch-per-phase) are listed in `CLAUDE.md` and `HISTORY.md`.
