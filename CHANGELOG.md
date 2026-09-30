# Changelog

All notable changes, NEWEST FIRST (Keep a Changelog style).
Rule: every bullet lists the files touched so the agent never has to diff or search.

<!--
ENTRY TEMPLATE
## [M<id>] <Title> - <YYYY-MM-DD>
### Added / Changed / Fixed / Removed
- <what> (files: path1, path2)
### DB: migrations added/changed (or "none")
### Routes: added/changed (or "none")
### Breaking / Notes
-->

## Unreleased

## [M1] Data layer - 2026-10-01
### Added
- Enums with label()/color(): BookingStatus, PaymentStatus, PaymentType, UserRole, PricingRuleType, PricingAdjustmentType, GalleryCategory (files: app/Enums/*.php)
- Money centavo helper: fromPesos, toPesos, format (files: app/Support/Money.php)
- Migrations: users role/is_active; packages, add_ons, pricing_rules, bookings (soft deletes, window/status/phone indexes), booking_add_ons, payments, blocked_dates, amenities, gallery_images, faqs, settings, activity_logs (files: database/migrations/2026_10_01_000100…001300_*.php)
- Models with casts, relationships, PHPDoc, scopes (Booking::active/overlapping/status, BlockedDate::overlapping, Package/Amenity/Faq active+ordered, GalleryImage visible, PricingRule forPackage) and money accessors (files: app/Models/*.php)
- Factories for every model with PH sample data and states (files: database/factories/*.php, database/factories/Concerns/PhilippineData.php)
- Seeders: Owner (from .env), Package (DAY-A/NIGHT-D/24H), Amenity (8), Setting, Faq (files: database/seeders/*.php, config/wonderpool.php, .env.example)
- Tests: factories, overlap edge cases, seeders, enums, money (files: tests/Feature/Models/FactoriesTest.php, tests/Feature/Models/BookingScopesTest.php, tests/Feature/SeederTest.php, tests/Unit/MoneyTest.php, tests/Unit/EnumsTest.php, tests/Pest.php)
- ER diagram + table dictionary (files: docs/database.md)
### Changed
- Palette aligned with PLAN.md §7 (cyan/green); primary button and white-text surfaces moved to 700 shades for AA (files: resources/css/app.css, resources/views/components/ui/button.blade.php, resources/views/partials/head.blade.php, resources/views/home.blade.php, resources/views/layouts/public.blade.php)
- x-ui.badge takes a status enum (label/color from enum) instead of a string map (files: resources/views/components/ui/badge.blade.php, resources/views/design-preview.blade.php)
- User model: role/is_active casts, relationships, active scope; UserFactory owner/inactive states (files: app/Models/User.php, database/factories/UserFactory.php)
### Removed
- tests/Unit/.gitkeep (files: tests/Unit/.gitkeep)
### DB: added 2026_10_01_000100_add_role_and_is_active_to_users_table + 12 create_* migrations (packages, add_ons, pricing_rules, bookings, booking_add_ons, payments, blocked_dates, amenities, gallery_images, faqs, settings, activity_logs)
### Routes: none
### Breaking / Notes
- `x-ui.badge status="..."` strings no longer work; pass an enum (`:status="$booking->status"`).
- New env vars OWNER_NAME / OWNER_EMAIL / OWNER_PASSWORD (seeder skips owner when unset).

## [M0] Setup - 2026-10-01
### Added
- Laravel 12 skeleton merged into repo root; Laravel ignore rules merged (files: app/, bootstrap/, config/, database/, public/, routes/, storage/, artisan, composer.json, composer.lock, package.json, package-lock.json, vite.config.js, phpunit.xml, .editorconfig, .gitattributes, .gitignore)
- Env: app name, APP_TIMEZONE=Asia/Manila, MySQL `wonderpool` placeholders (files: .env.example, config/app.php)
- Packages: blade-heroicons, Pest + laravel plugin, Larastan; npm @tailwindcss/forms, @fontsource/poppins, alpinejs, @alpinejs/focus (files: composer.json, composer.lock, package.json, package-lock.json)
- Theme tokens pool-*/garden-*, Poppins 400–700, forms plugin (files: resources/css/app.css)
- Alpine bootstrap with focus plugin (files: resources/js/app.js)
- Layouts + partials: public (mobile menu), admin (sidebar + mobile drawer), shared head/meta, flash (files: resources/views/layouts/public.blade.php, resources/views/layouts/admin.blade.php, resources/views/partials/head.blade.php, resources/views/partials/admin-sidebar.blade.php)
- Components x-ui.button/input/select/textarea/card/badge/modal/alert/flash/empty-state, x-admin.stat-card/page-header (files: resources/views/components/ui/*.blade.php, resources/views/components/admin/*.blade.php)
- Temporary home page and local-only design preview (files: resources/views/home.blade.php, resources/views/design-preview.blade.php, routes/web.php)
- Tooling: Pint psr12, Larastan level 6, GitHub Actions CI (files: pint.json, phpstan.neon, .github/workflows/ci.yml)
- Pest setup + smoke tests (files: tests/Pest.php, tests/TestCase.php, tests/Feature/SmokeTest.php, tests/Unit/.gitkeep)
- README and docs stubs (files: README.md, docs/architecture.md, docs/database.md, docs/booking-flow.md, docs/admin-guide.md, docs/deployment.md)
### Changed
- Skeleton files reformatted by Pint psr12 (files: app/Models/User.php, database/migrations/0001_01_01_00000*_*.php)
### Removed
- Default welcome view and example tests (files: resources/views/welcome.blade.php, tests/Feature/ExampleTest.php, tests/Unit/ExampleTest.php)
### DB: none (Laravel default users/cache/jobs migrations only; not yet run on MySQL)
### Routes: added GET / (home), GET /design-preview (local only)
### Breaking / Notes
- PLAN.md empty at M0 → palette/badge colors provisional (HISTORY D-003, D-004).
- Local MariaDB won't start (damaged InnoDB data); see HISTORY Known Issues.

## [bootstrap] Project tracking system - 2026-10-01
### Added
- Project tracking system and git bootstrap (files: CLAUDE.md, HISTORY.md, CHANGELOG.md, MILESTONES.md, .gitignore)
### DB: none
### Routes: none
