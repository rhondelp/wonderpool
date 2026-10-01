# Deployment

> Purpose: how to deploy, configure and maintain the application in production.

## Server requirements

- PHP 8.2+ with `pdo_pgsql` and `pgsql` extensions; PostgreSQL 15+ (D-014); Nginx; SSL via Let's Encrypt.
- Create role `wonderpool_user` and database `wonderpool` (owner `wonderpool_user`); set `DB_CONNECTION=pgsql` and the other `DB_*` values in `.env`.

## First deployment
## Environment configuration
## Scheduler & queue workers
## Updating / releases
## Backups & recovery

- Nightly `pg_dump -Fc wonderpool > wonderpool-YYYYMMDD.dump` to off-server storage; restore with `pg_restore -d wonderpool <file>`. Dumps are git-ignored (`*.dump`, `*.backup`) and must never be committed.
