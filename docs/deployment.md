# Deployment

> Purpose: how to deploy, configure and maintain the application in production.

## Server requirements

- PHP 8.2+ with `pdo_pgsql`, `pgsql` and `gd` (WebP support) extensions; PostgreSQL 15+ (D-014); Nginx; SSL via Let's Encrypt.
- `php artisan storage:link` on every new server; back up `storage/app/public/{gallery,amenities}` with the database (D-018). Nginx `client_max_body_size` ≥ 100M for 20 × 5 MB gallery uploads (also PHP `upload_max_filesize` 5M+, `post_max_size` 100M+, `max_file_uploads` 20+).
- Create role `wonderpool_user` and database `wonderpool` (owner `wonderpool_user`); set `DB_CONNECTION=pgsql` and the other `DB_*` values in `.env`.

## First deployment
## Environment configuration
- Optional bot protection: set `TURNSTILE_ENABLED=true`, `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` (Cloudflare dashboard) in `.env` (D-028). Keys never go in git.
## Scheduler & queue workers
- Cron (required since M4, booking expiry): `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`. Check with `php artisan schedule:list`.
## Updating / releases
## Backups & recovery
- Back up `storage/app/private/payment-proofs/` (guest payment proofs, never public) together with the database and `storage/app/public/` images.

- Nightly `pg_dump -Fc wonderpool > wonderpool-YYYYMMDD.dump` to off-server storage; restore with `pg_restore -d wonderpool <file>`. Dumps are git-ignored (`*.dump`, `*.backup`) and must never be committed.
