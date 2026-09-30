# HISTORY — Project Memory

> Keep under ~500 lines. Edit affected lines only. Every entry references a milestone ID.

## Project Summary
- Wonderpool Garden Resort booking system: public booking site + admin panel. (M0)
- Scope and roadmap: PLAN.md (§12 = roadmap). Tracking: CHANGELOG.md, MILESTONES.md.

## Stack & Versions
| Component | Version | Milestone |
|---|---|---|
| PHP | 8.2 (local XAMPP 8.2.12; CI 8.2) | M0 |
| Laravel | 12.x (12.69) | M0 |
| MySQL | MariaDB 10.4 (XAMPP); tests use SQLite :memory: | M0 |
| Tailwind CSS | 4.x (Vite plugin) + @tailwindcss/forms 0.5 | M0 |
| Alpine.js | 3.x + @alpinejs/focus (x-trap) | M0 |
| blade-heroicons | 2.7 | M0 |
| Font | Poppins 400/500/600/700 via @fontsource/poppins 5 (font-display: swap) | M0 |
| Pest | 3.x + pest-plugin-laravel | M0 |
| Larastan | 3.x, level 6 | M0 |
| Pint | 1.x, preset psr12 | M0 |
| Vite | 7.x (laravel-vite-plugin 2) | M0 |

## Conventions & Decisions
| ID | Date | Decision | Reason | Milestone |
|---|---|---|---|---|
| D-000 | 2026-10-01 | Repo `https://github.com/rhondelp/wonderpool.git` (PUBLIC), default branch `main`. Bootstrap commit only on main; then one branch per phase `phase/M<id>-<slug>`, post-launch `change/<slug>`; Conventional Commits with milestone ID; PR into main, reviewed/merged by owner only; no force-push/history rewrite. | Reviewable phases; public repo requires strict secret hygiene. | M0 |
| D-001 | 2026-10-01 | Money = integer centavos (`unsignedBigInteger`, column suffix `_cents`, e.g. `total_cents`); format as ₱ only at display time. | Exact arithmetic (nights × rate, discounts, deposits) with no float/rounding drift; ints are cheap to SUM in reports; no string-decimal casts. | M0 |
| D-002 | 2026-10-01 | Tailwind v4 CSS-first config: tokens live in `resources/css/app.css` `@theme` (no tailwind.config.js); forms plugin via `@plugin`. This file IS "the tailwind config". | Laravel 12 ships Tailwind v4; avoids a legacy JS config. | M0 |
| D-003 | 2026-10-01 | PROVISIONAL palette `pool-*` (aqua/blue, 50–950, primary = pool-600) and `garden-*` (green, 50–950). Neutrals = slate; warning = amber; danger = rose. | PLAN.md §7 was empty at M0; reconcile when PLAN.md is available. | M0 |
| D-004 | 2026-10-01 | PROVISIONAL badge colors (x-ui.badge): pending/awaiting_payment/partial=amber, confirmed=pool, checked_in/paid=garden, completed/no_show/refunded=slate, cancelled/rejected/unpaid=rose. | PLAN.md status list unavailable at M0; final cases come from enums in M1/M4. | M0 |
| D-005 | 2026-10-01 | Layouts use `@extends`/`@yield` (`layouts.public`, `layouts.admin`) with shared `partials.head`; stacks `styles` and `scripts`; flash via `x-ui.flash` reading session keys success/error/warning/info. | Simple, familiar Blade inheritance; one place for meta tags. | M0 |
| D-006 | 2026-10-01 | Tests: Pest, in-memory SQLite (phpunit.xml); `Tests\TestCase::setUp` calls `withoutVite()`. | Fast, no MySQL needed in CI or locally. | M0 |
| D-007 | 2026-10-01 | `/design-preview` route registered only when `APP_ENV=local`. REMOVE IN M9. | Visual QA of theme/components without exposing it in prod. | M0 |

## Folder Map
| Path | Purpose | Milestone |
|---|---|---|
| `/` | CLAUDE.md, PLAN.md, HISTORY.md, CHANGELOG.md, MILESTONES.md, README.md, pint.json, phpstan.neon | M0 |
| `.github/workflows/ci.yml` | CI: Pint, Larastan, npm build, Pest | M0 |
| `app/` | Laravel app code (Services/Enums/Requests added from M1) | M0 |
| `config/app.php` | timezone = env APP_TIMEZONE (Asia/Manila) | M0 |
| `docs/` | architecture, database, booking-flow, admin-guide, deployment (stubs) | M0 |
| `resources/css/app.css` | Tailwind v4 entry + `@theme` tokens + Poppins imports (D-002) | M0 |
| `resources/js/app.js` | Alpine + focus plugin bootstrap | M0 |
| `resources/views/layouts/` | `public.blade.php` (guest site), `admin.blade.php` (sidebar/drawer) | M0 |
| `resources/views/partials/` | `head` (meta, vite, styles stack), `admin-sidebar` (nav list) | M0 |
| `resources/views/components/ui/` | Shared UI components (x-ui.*) | M0 |
| `resources/views/components/admin/` | Admin-only components (x-admin.*) | M0 |
| `resources/views/home.blade.php` | Temporary landing page (replace in M5) | M0 |
| `resources/views/design-preview.blade.php` | Component gallery, local only (remove M9) | M0 |
| `routes/web.php` | Web routes | M0 |
| `tests/Feature/SmokeTest.php` | Boot/home/design-preview guard tests | M0 |

## Routes Table
| Method | URI | Name | Controller@action | Middleware | Milestone |
|---|---|---|---|---|---|
| GET | `/` | home | `Route::view` → `home` | web | M0 |
| GET | `/design-preview` | design-preview | closure → `design-preview` (local env only; REMOVE IN M9) | web | M0 |
| GET | `/up` | — | Laravel health check | — | M0 |

## Database Tables
| Table | Key columns | Relations | Milestone |
|---|---|---|---|

## Models & Relationships
- _none yet_

## Enums
| Name | Cases | Milestone |
|---|---|---|

## Services Index
| Class | Public methods (purpose) | Milestone |
|---|---|---|

## Blade Components
| Tag | Props | Used in | Milestone |
|---|---|---|---|
| `x-ui.button` | variant(primary/secondary/danger/ghost), size(sm/md/lg), type, href, icon | layouts, design-preview | M0 |
| `x-ui.input` | name*, label, type, id, value, hint, required | design-preview | M0 |
| `x-ui.select` | name*, options[value=>label], label, id, selected, placeholder, hint, required | design-preview | M0 |
| `x-ui.textarea` | name*, label, id, value, rows, hint, required | design-preview | M0 |
| `x-ui.card` | title, padded; slots actions, footer | design-preview | M0 |
| `x-ui.badge` | status (booking/payment, D-004), color(pool/garden/amber/rose/slate) | design-preview | M0 |
| `x-ui.modal` | name*, title, maxWidth(sm/md/lg/xl), show; slot footer; events open-modal/close-modal | design-preview | M0 |
| `x-ui.alert` | type(success/error/warning/info), title, dismissible | x-ui.flash, design-preview | M0 |
| `x-ui.flash` | — (reads session success/error/warning/info) | both layouts | M0 |
| `x-ui.empty-state` | title, description, icon; default slot = CTA | design-preview | M0 |
| `x-admin.stat-card` | label*, value*, icon, color(pool/garden/amber/rose), hint | design-preview | M0 |
| `x-admin.page-header` | title*, description; slot actions | design-preview | M0 |

## Settings Keys
| Key | Group | Default | Meaning | Milestone |
|---|---|---|---|---|

## Scheduled Commands & Jobs
| Command/Job | Schedule | Purpose | Milestone |
|---|---|---|---|

## Notifications & Mail Templates
| Class | Channel(s) | Trigger | Template | Milestone |
|---|---|---|---|---|

## Env Variables
| Name | Purpose | Milestone |
|---|---|---|
| APP_NAME | "Wonderpool Garden Resort" | M0 |
| APP_ENV | local/production; `local` enables /design-preview | M0 |
| APP_DEBUG | false in production | M0 |
| APP_URL | Base URL (default http://localhost:8000) | M0 |
| APP_TIMEZONE | App timezone, read by config/app.php (Asia/Manila) | M0 |
| DB_CONNECTION/HOST/PORT/DATABASE/USERNAME/PASSWORD | MySQL connection (db `wonderpool`) | M0 |
| SESSION_DRIVER, CACHE_STORE, QUEUE_CONNECTION | `database` (needs migrations) | M0 |
| MAIL_* | Mailer; `log` in dev | M0 |

## Business Flow Summaries
### Booking flow
- _tbd (M4/M5)_
### Status lifecycle
- _tbd (M4)_
### Pricing
- _tbd (M4)_
### Availability
- _tbd (M4)_

## Known Issues / TODO
- PLAN.md is still empty (0 bytes) at end of M0 and was NOT committed; D-003/D-004 are provisional — reconcile palette & statuses with PLAN.md §7/§8. (M0)
- Local XAMPP MariaDB fails to start (InnoDB "log sequence number is in the future" — data dir damaged). `php artisan migrate` on MySQL not yet run; app verified with SESSION_DRIVER=file. (M0)
- Admin/public nav links are `#` placeholders until routes exist (M2/M5). (M0)
- Remove `/design-preview` route + view in M9 (D-007). (M0)
