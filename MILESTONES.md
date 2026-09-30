# Milestones

| ID | Phase | Title | Status | Date done | Commit hash |
|---|---|---|---|---|---|
| M0 | 0 | Setup | Done | 2026-10-01 | 7c42f4f |
| M1 | 1 | Data layer | Done | 2026-10-01 | HASH_PLACEHOLDER |
| M2 | 2 | Admin foundation | Not started | | |
| M3 | 3 | Content modules | Not started | | |
| M4 | 4 | Booking engine | Not started | | |
| M5 | 5 | Public site | Not started | | |
| M6 | 6 | Admin bookings | Not started | | |
| M7 | 7 | Reports & logs | Not started | | |
| M8 | 8 | Notifications | Not started | | |
| M9 | 9 | Hardening | Not started | | |
| M10 | 10 | Deploy & handover | Not started | | |

<!--
REPORT TEMPLATE (one per finished milestone, newest first below this comment)
## M<id> <Title> (Done <date>, commit <hash>)
**Goal:** one line
**Delivered:** bullet list of features
**Files created / modified:** grouped list
**DB changes:** migrations and tables
**Routes:** list
**How to verify:** manual steps + test names
**Key decisions:** reference D-xxx IDs from HISTORY.md
**Edit entry points (where to change things later):** "To change X, edit Y (file:method)"
**Known limitations / follow-ups**
-->

# Reports

## M1 Data layer (Done 2026-10-01, commit HASH_PLACEHOLDER)
**Goal:** Full schema, models, enums, factories and seeders per PLAN.md §4, ready for services.
**Delivered:**
- 7 string-backed enums with label()/color(); Money helper (centavos, D-001)
- 13 migrations (users alter + 12 tables) with explicit FK delete rules and indexes; reversible
- 13 models with casts, relationships, PHPDoc @property blocks, scopes (`Booking::active()`, `Booking::overlapping()`, etc.) and ₱ accessors
- Factories for every model with PH names and +639 mobiles; booking states (approved/rejected/cancelled/completed/forPackageOn/window)
- Seeders: owner from .env, 3 PLAN packages, 8 amenities, 12 settings, 6 FAQs (all idempotent)
- docs/database.md ERD (Mermaid) + table dictionary
- Theme palette and badge statuses aligned with PLAN.md §7 (AA-safe primary)
**Files created / modified:**
- Enums/helpers: app/Enums/*.php, app/Support/Money.php, config/wonderpool.php
- Models: app/Models/{User,Package,AddOn,BookingAddOn,PricingRule,Booking,Payment,BlockedDate,Amenity,GalleryImage,Faq,Setting,ActivityLog}.php
- DB: database/migrations/2026_10_01_*.php, database/factories/*.php, database/factories/Concerns/PhilippineData.php, database/seeders/*.php
- Tests: tests/Feature/Models/{FactoriesTest,BookingScopesTest}.php, tests/Feature/SeederTest.php, tests/Unit/{MoneyTest,EnumsTest}.php, tests/Pest.php
- UI alignment: resources/css/app.css, components/ui/{button,badge}.blade.php, partials/head.blade.php, home.blade.php, layouts/public.blade.php, design-preview.blade.php
- Docs/env: docs/database.md, .env.example
**DB changes:** users (+role, +is_active); new packages, add_ons, pricing_rules, bookings, booking_add_ons, payments, blocked_dates, amenities, gallery_images, faqs, settings, activity_logs.
**Routes:** none
**How to verify:** set OWNER_EMAIL/OWNER_PASSWORD in .env → `php artisan migrate:fresh --seed`; `vendor/bin/pest` (57 tests: FactoriesTest, BookingScopesTest incl. "does not treat touching boundaries as overlaps", SeederTest, MoneyTest, EnumsTest); `vendor/bin/phpstan analyse`; /design-preview shows enum badges. Verified on MariaDB 10.4: migrate:fresh --seed, full rollback, re-migrate.
**Key decisions:** D-001 (updated), D-003, D-004 (finalized), D-008 (AA 700 shades), D-009 (local time, half-open windows), D-010 (string enums), D-011 (FK delete rules), D-012 (settings/owner env), D-013 (E.164 phones).
**Edit entry points (where to change things later):**
- To change what counts as "occupying" the resort, edit `app/Enums/BookingStatus.php` (`blocking()`).
- To change overlap semantics, edit `app/Models/Booking.php` (`scopeOverlapping`) and `app/Models/BlockedDate.php` (`scopeOverlapping`).
- To change status labels/badge colors, edit `app/Enums/*Status.php` (`label()`, `color()`).
- To change money formatting, edit `app/Support/Money.php` (`format`).
- To change default packages/settings/FAQs/amenities, edit `database/seeders/{Package,Setting,Faq,Amenity}Seeder.php`.
- To change sample data, edit `database/factories/Concerns/PhilippineData.php`.
**Known limitations / follow-ups:**
- Local XAMPP MariaDB still broken; run `php artisan migrate --seed` on the real DB once fixed.
- Contact/payment/social settings and Day package hours/pax are placeholders pending owner answers (PLAN.md §14).
- Transition rules, availability service, pricing and reference-code generator are M4.

## M0 Setup (Done 2026-10-01, commit 7c42f4f)
**Goal:** Laravel project, tooling, design tokens and base layouts ready for feature work.
**Delivered:**
- Laravel 12 in repo root; MySQL env (db `wonderpool`), APP_NAME, APP_TIMEZONE=Asia/Manila
- Tailwind v4 theme: `pool-*` / `garden-*` palettes, Poppins 400–700 (swap), forms plugin; Alpine + focus
- Layouts `layouts.public` (mobile menu) and `layouts.admin` (desktop sidebar, mobile drawer), shared meta head, flash messages
- Components: x-ui.button/input/select/textarea/card/badge/modal/alert/flash/empty-state, x-admin.stat-card/page-header
- `/design-preview` (local only) gallery of every component
- Pint (psr12), Larastan level 6, Pest smoke tests, GitHub Actions CI; README + docs stubs
**Files created / modified:**
- Config/tooling: .env.example, config/app.php, .gitignore, pint.json, phpstan.neon, .github/workflows/ci.yml, composer.json, package.json
- Front-end: resources/css/app.css, resources/js/app.js
- Views: resources/views/layouts/{public,admin}.blade.php, partials/{head,admin-sidebar}.blade.php, components/ui/*.blade.php, components/admin/*.blade.php, home.blade.php, design-preview.blade.php
- Routes: routes/web.php
- Tests: tests/Pest.php, tests/TestCase.php, tests/Feature/SmokeTest.php
- Docs: README.md, docs/*.md
**DB changes:** none beyond Laravel defaults (users, cache, jobs); not yet migrated on MySQL.
**Routes:** GET / (home), GET /design-preview (local only), GET /up
**How to verify:** `npm run build`; `php artisan serve` → open /design-preview; `vendor/bin/pint --test`; `vendor/bin/phpstan analyse`; `vendor/bin/pest` (SmokeTest: renders home, hides design preview outside local).
**Key decisions:** D-001 (money = integer centavos), D-002 (Tailwind v4 @theme), D-003/D-004 (provisional palette & badge colors), D-005 (layouts), D-006 (tests on SQLite), D-007 (design preview local only).
**Edit entry points (where to change things later):**
- To change colors/fonts, edit `resources/css/app.css` (`@theme` block).
- To change status badge colors, edit `resources/views/components/ui/badge.blade.php` (`$statusColors`).
- To change admin sidebar items, edit `resources/views/layouts/admin.blade.php` (`$nav`).
- To change public nav/footer, edit `resources/views/layouts/public.blade.php`.
- To change meta tags/SEO defaults, edit `resources/views/partials/head.blade.php`.
**Known limitations / follow-ups:**
- PLAN.md was empty during M0 (not committed) → palette/status colors provisional; reconcile.
- Local MariaDB fails to start (damaged InnoDB data dir); run `php artisan migrate` once fixed.
- Nav links are placeholders; `/design-preview` must be removed in M9.
