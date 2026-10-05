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
- All emails are queued (M8). `QUEUE_CONNECTION=database` (jobs table, already migrated) in dev and on a
  single server; switch to redis only if volume grows. Without a running worker no email is sent.
- Dev: `php artisan queue:work --tries=3` in a second terminal (or `composer run dev` if configured);
  `MAIL_MAILER=log` writes emails to `storage/logs/laravel.log`.
- Production worker with Supervisor, e.g. `/etc/supervisor/conf.d/wonderpool-worker.conf`:

```ini
[program:wonderpool-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/wonderpool/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/wonderpool/storage/logs/worker.log
stopwaitsecs=3600
```

  Then `sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start "wonderpool-worker:*"`.
  After every deploy run `php artisan queue:restart` so workers load the new code.
- Failed jobs land in `failed_jobs` (`php artisan queue:failed`, `queue:retry all`); final email failures
  are also in Admin → Activity log (area "Email"). Use Settings → Notifications → "Send test email" to check mail + worker.
- Scheduled tasks (`routes/console.php`): `bookings:expire-stale` every 15 min (M4), `bookings:send-reminders`
  daily at `REMINDER_TIME` (default 09:00 Asia/Manila, M8).
- Cron (required since M4, booking expiry): `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`. Check with `php artisan schedule:list`.
## Updating / releases
## Backups & recovery
- Back up `storage/app/private/payment-proofs/` (guest payment proofs, never public) together with the database and `storage/app/public/` images.

- Nightly `pg_dump -Fc wonderpool > wonderpool-YYYYMMDD.dump` to off-server storage; restore with `pg_restore -d wonderpool <file>`. Dumps are git-ignored (`*.dump`, `*.backup`) and must never be committed.
